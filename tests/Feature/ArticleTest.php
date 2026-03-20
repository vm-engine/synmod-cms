<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use VmEngine\Cms\Enums\ArticleStatus;
use VmEngine\Cms\Models\CmsArticle;
use VmEngine\Cms\Models\CmsCategory;
use VmEngine\Cms\Models\CmsSection;
use VmEngine\Cms\Models\CmsTag;
use VmEngine\SynAuth\Models\Role;
use VmEngine\SynAuth\Models\RolePermission;
use VmEngine\SynAuth\Models\User;

uses(RefreshDatabase::class);

beforeEach(function () {
    $permission = RolePermission::factory()
        ->forModule('cms', 'article')
        ->fullCrud();
    $this->adminRole = Role::factory()->has($permission)->create([
        'name' => 'Administrator',
        'slug' => 'admin',
        'level' => 5,
    ]);
    $this->user = User::factory()->create();
    $this->user->roles()->attach($this->adminRole);

    $this->section = CmsSection::factory()->create([
        'title' => 'Tech',
        'slug' => 'tech',
        'status' => 'published',
    ]);
});

describe('Model Factory', function () {
    it('can create an article using factory', function () {
        $article = CmsArticle::factory()->create([
            'section_id' => $this->section->id,
            'title' => 'Hello World',
            'slug' => 'hello-world',
        ]);

        expect($article->title)->toBe('Hello World')
            ->and($article->section_id)->toBe($this->section->id)
            ->and($article->status)->toBe(ArticleStatus::Published);

        $this->assertDatabaseHas('cms_articles', ['slug' => 'hello-world']);
    });

    it('computes read_time correctly', function () {
        $body = str_repeat('word ', 400); // 400 words → 2 minutes
        $readTime = CmsArticle::computeReadTime($body);
        expect($readTime)->toBe(2);
    });

    it('returns minimum 1 minute for very short content', function () {
        $readTime = CmsArticle::computeReadTime('Short content.');
        expect($readTime)->toBe(1);
    });
});

describe('Page Rendering', function () {
    it('renders the article list', function () {
        $this->actingAs($this->user)
            ->get(backend_route('cms.article.index'))
            ->assertStatus(200);
    });

    it('renders the add article form', function () {
        $this->actingAs($this->user)
            ->get(backend_route('cms.article.form'))
            ->assertStatus(200);
    });

    it('renders the edit article form', function () {
        $article = CmsArticle::factory()->create(['section_id' => $this->section->id]);

        $this->actingAs($this->user)
            ->get(backend_route('cms.article.form', ['id' => $article->id]))
            ->assertStatus(200);
    });
});

describe('Create Article', function () {
    it('can create an article via Livewire component', function () {
        Livewire::actingAs($this->user)
            ->test('cms::backend.article-form')
            ->set('form.section_id', $this->section->id)
            ->set('form.title', 'First Post')
            ->set('form.slug', 'first-post')
            ->set('form.status', 'published')
            ->call('save')
            ->assertRedirect(backend_route('cms.article.index'));

        $this->assertDatabaseHas('cms_articles', [
            'title' => 'First Post',
            'slug' => 'first-post',
            'section_id' => $this->section->id,
        ]);
    });

    it('validates required fields', function () {
        Livewire::actingAs($this->user)
            ->test('cms::backend.article-form')
            ->set('form.title', '')
            ->set('form.slug', '')
            ->call('save')
            ->assertHasErrors([
                'form.title' => 'required',
                'form.slug' => 'required',
                'form.section_id' => 'required',
            ]);
    });

    it('validates section_id exists', function () {
        Livewire::actingAs($this->user)
            ->test('cms::backend.article-form')
            ->set('form.section_id', 99999)
            ->set('form.title', 'Test')
            ->set('form.slug', 'test')
            ->set('form.status', 'draft')
            ->call('save')
            ->assertHasErrors(['form.section_id' => 'exists']);
    });

    it('can create article with tags', function () {
        $tag1 = CmsTag::factory()->create(['name' => 'PHP', 'slug' => 'php']);
        $tag2 = CmsTag::factory()->create(['name' => 'Laravel', 'slug' => 'laravel']);

        Livewire::actingAs($this->user)
            ->test('cms::backend.article-form')
            ->set('form.section_id', $this->section->id)
            ->set('form.title', 'Tagged Post')
            ->set('form.slug', 'tagged-post')
            ->set('form.status', 'published')
            ->set('form.tag_ids', [$tag1->id, $tag2->id])
            ->call('save');

        $article = CmsArticle::where('slug', 'tagged-post')->first();
        expect($article)->not->toBeNull();
        expect($article->tags()->count())->toBe(2);
    });
});

describe('Update Article', function () {
    it('can update an article via Livewire component', function () {
        $article = CmsArticle::factory()->create([
            'section_id' => $this->section->id,
            'title' => 'Old Title',
            'slug' => 'old-title',
        ]);

        Livewire::actingAs($this->user)
            ->test('cms::backend.article-form', ['id' => $article->id])
            ->assertSet('form.title', 'Old Title')
            ->set('form.title', 'Updated Title')
            ->set('form.slug', 'updated-title')
            ->call('save')
            ->assertRedirect(backend_route('cms.article.index'));

        $this->assertDatabaseHas('cms_articles', [
            'id' => $article->id,
            'title' => 'Updated Title',
        ]);
    });
});

describe('Delete Article', function () {
    it('can delete an article via Livewire component', function () {
        $article = CmsArticle::factory()->create([
            'section_id' => $this->section->id,
        ]);

        Livewire::actingAs($this->user)
            ->test('cms::backend.article-list')
            ->call('delete', $article->delete_token)
            ->assertDispatched('notify');

        $this->assertDatabaseMissing('cms_articles', ['id' => $article->id]);
    });
});

describe('Fallback Route Sync', function () {
    it('registers fallback route for published article under section', function () {
        CmsArticle::factory()->create([
            'section_id' => $this->section->id,
            'category_id' => null,
            'slug' => 'my-article',
            'status' => ArticleStatus::Published,
            'published_at' => now()->subDay(),
        ]);

        $this->assertDatabaseHas('fallback_routes', [
            'slug' => 'tech/my-article',
            'module' => 'cms',
        ]);
    });

    it('registers fallback route for article under category', function () {
        $category = CmsCategory::factory()->create([
            'section_id' => $this->section->id,
            'slug' => 'tutorials',
            'status' => 'published',
        ]);

        CmsArticle::factory()->create([
            'section_id' => $this->section->id,
            'category_id' => $category->id,
            'slug' => 'my-tutorial',
            'status' => ArticleStatus::Published,
            'published_at' => now()->subDay(),
        ]);

        $this->assertDatabaseHas('fallback_routes', [
            'slug' => 'tech/tutorials/my-tutorial',
            'module' => 'cms',
        ]);
    });

    it('does not register fallback route for draft articles', function () {
        CmsArticle::factory()->draft()->create([
            'section_id' => $this->section->id,
            'category_id' => null,
            'slug' => 'draft-article',
        ]);

        $this->assertDatabaseMissing('fallback_routes', [
            'slug' => 'tech/draft-article',
        ]);
    });

    it('removes fallback route when article is deleted', function () {
        $article = CmsArticle::factory()->create([
            'section_id' => $this->section->id,
            'category_id' => null,
            'slug' => 'removable-article',
            'status' => ArticleStatus::Published,
            'published_at' => now()->subDay(),
        ]);

        $this->assertDatabaseHas('fallback_routes', ['slug' => 'tech/removable-article']);

        $article->delete();

        $this->assertDatabaseMissing('fallback_routes', ['slug' => 'tech/removable-article']);
    });
});
