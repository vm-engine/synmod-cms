<?php

declare(strict_types=1);

namespace VmEngine\Cms\Services;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Mews\Purifier\Facades\Purifier;
use VmEngine\Cms\Models\CmsArticle;
use VmEngine\Cms\Models\CmsCategory;
use VmEngine\Cms\Models\CmsSection;
use VmEngine\Cms\Models\CmsTag;

class CmsArticleService
{
    public function __construct(private readonly CmsActivityLogger $logger) {}

    // -------------------------------------------------------------- Articles

    /**
     * @param  array<int|string, mixed>  $with
     */
    public function findArticleOrFailById(int $id, array $with = []): CmsArticle
    {
        return CmsArticle::with($with)->findOrFail($id);
    }

    /**
     * @param  array<int|string, mixed>  $with
     */
    public function findPublishedArticleOrFailById(int $id, array $with = []): CmsArticle
    {
        return CmsArticle::with($with)->published()->findOrFail($id);
    }

    /**
     * Backend article-list with filters / sort / pagination.
     *
     * @param  array{q?: string, status?: string, sectionId?: int|null, categoryId?: int|null}  $filters
     * @return LengthAwarePaginator<int, CmsArticle>
     */
    public function paginateArticles(array $filters, string $sort, string $direction, int $perPage): LengthAwarePaginator
    {
        $query = CmsArticle::with(['section', 'category', 'translations']);

        if (! empty($filters['q']) && strlen($filters['q']) > 2) {
            $query->search($filters['q']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['sectionId'])) {
            $query->where('section_id', $filters['sectionId']);
        }

        if (! empty($filters['categoryId'])) {
            $query->where('category_id', $filters['categoryId']);
        }

        return $query->orderBy($sort, $this->normalizeDirection($direction))->paginate($perPage)->onEachSide(1);
    }

    /**
     * @return LengthAwarePaginator<int, CmsArticle>
     */
    public function paginatePublishedArticlesInSection(int $sectionId, string $orderBy, string $orderDir, int $perPage): LengthAwarePaginator
    {
        return CmsArticle::published()
            ->visibleTo()
            ->where('section_id', $sectionId)
            ->with(['category', 'tags'])
            ->orderBy($orderBy, $this->normalizeDirection($orderDir))
            ->paginate($perPage)
            ->onEachSide(1);
    }

    /**
     * @return LengthAwarePaginator<int, CmsArticle>
     */
    public function paginatePublishedArticlesInCategory(int $categoryId, string $orderBy, string $orderDir, int $perPage): LengthAwarePaginator
    {
        return CmsArticle::published()
            ->visibleTo()
            ->where('category_id', $categoryId)
            ->with(['section', 'tags'])
            ->orderBy($orderBy, $this->normalizeDirection($orderDir))
            ->paginate($perPage)
            ->onEachSide(1);
    }

    /**
     * For the generic Articles widget — most-recent / featured / alphabetical.
     *
     * @return Collection<int, CmsArticle>
     */
    public function getPublishedArticlesForWidget(string $sort, int $limit): Collection
    {
        $query = CmsArticle::with(['section', 'category'])->published()->visibleTo();
        $this->applyWidgetSort($query, $sort);

        /** @var Collection<int, CmsArticle> $articles */
        $articles = $query->limit($limit)->get();

        return $articles;
    }

    /**
     * @return Collection<int, CmsArticle>
     */
    public function getPublishedArticlesInSectionForWidget(?int $sectionId, string $sort, int $limit): Collection
    {
        $query = CmsArticle::with(['category'])->published()->visibleTo();

        if ($sectionId !== null) {
            $query->where('section_id', $sectionId);
        }

        $this->applyWidgetSort($query, $sort);

        /** @var Collection<int, CmsArticle> $articles */
        $articles = $query->limit($limit)->get();

        return $articles;
    }

    /**
     * @return Collection<int, CmsArticle>
     */
    public function getPublishedArticlesInCategoryForWidget(?int $sectionId, ?int $categoryId, string $sort, int $limit): Collection
    {
        $query = CmsArticle::published()->visibleTo();

        if ($sectionId !== null) {
            $query->where('section_id', $sectionId);
        }

        if ($categoryId !== null) {
            $query->where('category_id', $categoryId);
        }

        $this->applyWidgetSort($query, $sort);

        /** @var Collection<int, CmsArticle> $articles */
        $articles = $query->limit($limit)->get();

        return $articles;
    }

    /**
     * @param  array<int, int>  $tagIds
     * @return Collection<int, CmsArticle>
     */
    public function getPublishedArticlesByTagsForWidget(array $tagIds, string $match, string $sort, int $limit): Collection
    {
        $query = CmsArticle::with(['section', 'category'])->published()->visibleTo();

        if (! empty($tagIds)) {
            if ($match === 'all') {
                foreach ($tagIds as $tagId) {
                    $query->whereHas('tags', fn ($q) => $q->where('cms_tags.id', $tagId));
                }
            } else {
                $query->whereHas('tags', fn ($q) => $q->whereIn('cms_tags.id', $tagIds));
            }
        }

        $this->applyWidgetSort($query, $sort);

        /** @var Collection<int, CmsArticle> $articles */
        $articles = $query->limit($limit)->get();

        return $articles;
    }

    /**
     * @param  Builder<CmsArticle>  $query
     */
    private function applyWidgetSort(Builder $query, string $sort): void
    {
        match ($sort) {
            'featured' => $query->featured()->orderBy('published_at', 'desc'),
            'oldest' => $query->orderBy('published_at', 'asc'),
            'az' => $query->orderBy('title', 'asc'),
            'za' => $query->orderBy('title', 'desc'),
            default => $query->orderBy('published_at', 'desc'),
        };
    }

    /**
     * @return array<int, array{value: int, label: string}>
     */
    public function searchArticleOptions(string $query, int $limit = 20): array
    {
        return CmsArticle::with(['section', 'category'])
            ->where('title', 'like', "%{$query}%")
            ->limit($limit)
            ->get()
            ->map(fn (CmsArticle $a) => [
                'value' => $a->id,
                'label' => $a->section->title.($a->category ? ' › '.$a->category->title : '').' › '.$a->title,
            ])
            ->all();
    }

    /**
     * @param  array<int, int>|int  $ids
     * @return array<int, array{value: int, label: string}>
     */
    public function loadArticleOptions(int|array $ids): array
    {
        return CmsArticle::with(['section', 'category'])
            ->whereIn('id', (array) $ids)
            ->get()
            ->map(fn (CmsArticle $a) => [
                'value' => $a->id,
                'label' => $a->section->title.($a->category ? ' › '.$a->category->title : '').' › '.$a->title,
            ])
            ->all();
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, int|string>  $tagInputs  Mix of numeric IDs (existing tags) and strings (new tag names to find-or-create).
     */
    public function createArticle(array $data, array $tagInputs, int $userId): CmsArticle
    {
        return DB::transaction(function () use ($data, $tagInputs, $userId): CmsArticle {
            if (isset($data['body'])) {
                $data['body'] = Purifier::clean($data['body'], 'cms_rte');
            }

            $data['read_time'] = CmsArticle::computeReadTime($data['body'] ?? null);
            $data['created_by'] = $userId;
            $data['updated_by'] = $userId;

            $article = CmsArticle::create($data);
            $article->tags()->sync($this->resolveTagIds($tagInputs));

            $this->logger->logCreate('article', $article, $article->toArray());

            return $article;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, int|string>  $tagInputs
     */
    public function updateArticle(CmsArticle $article, array $data, array $tagInputs, int $userId): CmsArticle
    {
        return DB::transaction(function () use ($article, $data, $tagInputs, $userId): CmsArticle {
            $oldData = $article->toArray();

            if (isset($data['body'])) {
                $data['body'] = Purifier::clean($data['body'], 'cms_rte');
            }

            $data['read_time'] = CmsArticle::computeReadTime($data['body'] ?? null);
            $data['updated_by'] = $userId;

            $article->update($data);
            $article->tags()->sync($this->resolveTagIds($tagInputs));

            $this->logger->logUpdate('article', $article, $oldData);

            return $article;
        });
    }

    public function deleteArticleByToken(string $token, ?Closure $authorize = null): CmsArticle
    {
        $id = CmsArticle::validateDeleteToken($token);
        if (! $id) {
            throw new InvalidArgumentException(__('cms::labels.invalid_delete_token'));
        }

        $article = CmsArticle::findOrFail($id);

        $authorize?->__invoke($article);
        $this->logger->logDelete('article', $article);
        $article->delete();

        return $article;
    }

    // -------------------------------------------------------------- Sections

    /**
     * @param  array<int|string, mixed>  $with
     */
    public function findSectionById(int $id, array $with = []): ?CmsSection
    {
        return CmsSection::with($with)->find($id);
    }

    /**
     * @param  array<int|string, mixed>  $with
     */
    public function findSectionOrFailById(int $id, array $with = []): CmsSection
    {
        return CmsSection::with($with)->findOrFail($id);
    }

    /**
     * @param  array<int|string, mixed>  $with
     */
    public function findPublishedSectionOrFailById(int $id, array $with = []): CmsSection
    {
        return CmsSection::with($with)->published()->findOrFail($id);
    }

    /**
     * @param  array<int|string, mixed>  $with
     */
    public function findPublishedSectionBySlug(string $slug, array $with = []): ?CmsSection
    {
        return CmsSection::with($with)->published()->visibleTo()->where('slug', $slug)->first();
    }

    /**
     * @param  array{q?: string, status?: string}  $filters
     * @return LengthAwarePaginator<int, CmsSection>
     */
    public function paginateSections(array $filters, string $sort, string $direction, int $perPage): LengthAwarePaginator
    {
        $query = CmsSection::withCount(['categories', 'articles']);

        if (! empty($filters['q']) && strlen($filters['q']) > 2) {
            $query->search($filters['q']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderBy($sort, $this->normalizeDirection($direction))->paginate($perPage)->onEachSide(1);
    }

    /**
     * @return array<int, array{value: int, label: string}>
     */
    public function searchSectionOptions(string $query, int $limit = 20): array
    {
        return CmsSection::where('title', 'like', "%{$query}%")
            ->limit($limit)
            ->get(['id', 'title'])
            ->map(fn (CmsSection $s) => ['value' => $s->id, 'label' => $s->title])
            ->all();
    }

    /**
     * @param  array<int, int>|int  $ids
     * @return array<int, array{value: int, label: string}>
     */
    public function loadSectionOptions(int|array $ids): array
    {
        return CmsSection::whereIn('id', (array) $ids)
            ->get(['id', 'title'])
            ->map(fn (CmsSection $s) => ['value' => $s->id, 'label' => $s->title])
            ->all();
    }

    /**
     * Section dropdown for filters.
     *
     * @return array<int, array{value: int|string, label: string}>
     */
    public function getSectionOptions(string $emptyLabel = '-- All Sections --'): array
    {
        $options = [['value' => '', 'label' => $emptyLabel]];
        foreach (CmsSection::orderBy('title')->get() as $section) {
            $options[] = ['value' => $section->id, 'label' => $section->title];
        }

        return $options;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createSection(array $data, int $userId): CmsSection
    {
        if (isset($data['description'])) {
            $data['description'] = Purifier::clean($data['description'], 'cms_rte');
        }

        $data['created_by'] = $userId;
        $section = CmsSection::create($data);
        $this->logger->logCreate('section', $section, $section->toArray());

        return $section;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateSection(CmsSection $section, array $data): CmsSection
    {
        $oldData = $section->toArray();

        if (isset($data['description'])) {
            $data['description'] = Purifier::clean($data['description'], 'cms_rte');
        }

        $section->update($data);
        $this->logger->logUpdate('section', $section, $oldData);

        return $section;
    }

    public function deleteSectionByToken(string $token, ?Closure $authorize = null): CmsSection
    {
        $id = CmsSection::validateDeleteToken($token);
        if (! $id) {
            throw new InvalidArgumentException(__('cms::labels.invalid_delete_token'));
        }

        $section = CmsSection::findOrFail($id);

        $authorize?->__invoke($section);
        $this->logger->logDelete('section', $section);
        $section->delete();

        return $section;
    }

    // ------------------------------------------------------------ Categories

    /**
     * @param  array<int|string, mixed>  $with
     */
    public function findCategoryOrFailById(int $id, array $with = []): CmsCategory
    {
        return CmsCategory::with($with)->findOrFail($id);
    }

    /**
     * @param  array<int|string, mixed>  $with
     */
    public function findPublishedCategoryOrFailById(int $id, array $with = []): CmsCategory
    {
        return CmsCategory::with($with)->published()->findOrFail($id);
    }

    public function findPublishedCategoryBySlugInSection(string $slug, int $sectionId): ?CmsCategory
    {
        return CmsCategory::where('slug', $slug)
            ->where('section_id', $sectionId)
            ->published()
            ->first();
    }

    /**
     * @param  array{q?: string, status?: string}  $filters
     * @return LengthAwarePaginator<int, CmsCategory>
     */
    public function paginateCategoriesInSection(int $sectionId, array $filters, string $sort, string $direction, int $perPage): LengthAwarePaginator
    {
        $query = CmsCategory::with('section')
            ->withCount('articles')
            ->where('section_id', $sectionId);

        if (! empty($filters['q']) && strlen($filters['q']) > 2) {
            $query->search($filters['q']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderBy($sort, $this->normalizeDirection($direction))->paginate($perPage)->onEachSide(1);
    }

    /**
     * Category dropdown filter, optionally scoped by section.
     *
     * @return array<int, array{value: int|string, label: string}>
     */
    public function getCategoryOptions(?int $sectionId = null, string $emptyLabel = '-- All Categories --'): array
    {
        $options = [['value' => '', 'label' => $emptyLabel]];
        $query = CmsCategory::orderBy('title');
        if ($sectionId !== null) {
            $query->where('section_id', $sectionId);
        }
        foreach ($query->get() as $category) {
            $options[] = ['value' => $category->id, 'label' => $category->title];
        }

        return $options;
    }

    /**
     * @return array<int, array{value: int, label: string}>
     */
    public function searchCategoryOptions(string $query, int $limit = 20): array
    {
        return CmsCategory::with('section')
            ->where('title', 'like', "%{$query}%")
            ->limit($limit)
            ->get()
            ->map(fn (CmsCategory $c) => ['value' => $c->id, 'label' => $c->section->title.' › '.$c->title])
            ->all();
    }

    /**
     * @param  array<int, int>|int  $ids
     * @return array<int, array{value: int, label: string}>
     */
    public function loadCategoryOptions(int|array $ids): array
    {
        return CmsCategory::with('section')
            ->whereIn('id', (array) $ids)
            ->get()
            ->map(fn (CmsCategory $c) => ['value' => $c->id, 'label' => $c->section->title.' › '.$c->title])
            ->all();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createCategory(array $data, int $userId): CmsCategory
    {
        if (isset($data['description'])) {
            $data['description'] = Purifier::clean($data['description'], 'cms_rte');
        }

        $data['created_by'] = $userId;
        $category = CmsCategory::create($data);
        $this->logger->logCreate('category', $category, $category->toArray());

        return $category;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateCategory(CmsCategory $category, array $data): CmsCategory
    {
        $oldData = $category->toArray();

        if (isset($data['description'])) {
            $data['description'] = Purifier::clean($data['description'], 'cms_rte');
        }

        $category->update($data);
        $this->logger->logUpdate('category', $category, $oldData);

        return $category;
    }

    public function deleteCategoryByToken(string $token, ?Closure $authorize = null): CmsCategory
    {
        $id = CmsCategory::validateDeleteToken($token);
        if (! $id) {
            throw new InvalidArgumentException(__('cms::labels.invalid_delete_token'));
        }

        $category = CmsCategory::findOrFail($id);

        $authorize?->__invoke($category);
        $this->logger->logDelete('category', $category);
        $category->delete();

        return $category;
    }

    // ------------------------------------------------------------------ Tags

    /**
     * @param  array<int|string, mixed>  $with
     */
    public function findTagOrFailById(int $id, array $with = []): CmsTag
    {
        return CmsTag::with($with)->findOrFail($id);
    }

    /**
     * @param  array{q?: string}  $filters
     * @return LengthAwarePaginator<int, CmsTag>
     */
    public function paginateTags(array $filters, int $perPage): LengthAwarePaginator
    {
        $query = CmsTag::withCount('articles');

        if (! empty($filters['q']) && strlen($filters['q']) > 1) {
            $q = $filters['q'];
            $query->where(function ($qq) use ($q): void {
                $qq->where('name', 'like', "%{$q}%")
                    ->orWhere('slug', 'like', "%{$q}%");
            });
        }

        return $query->orderBy('name')->paginate($perPage)->onEachSide(1);
    }

    /**
     * @param  array<int, string>  $slugs
     * @return Collection<int, CmsTag>
     */
    public function getTagsBySlugs(array $slugs): Collection
    {
        /** @var Collection<int, CmsTag> $tags */
        $tags = CmsTag::whereIn('slug', $slugs)->get();

        return $tags;
    }

    /**
     * @return array<int, array{value: int, label: string}>
     */
    public function searchTagOptions(string $query, int $limit = 20): array
    {
        return CmsTag::where('name', 'like', "%{$query}%")
            ->orderBy('name')
            ->limit($limit)
            ->get()
            ->map(fn (CmsTag $t) => ['value' => $t->id, 'label' => $t->name])
            ->all();
    }

    /**
     * @param  array<int, int|string>  $ids
     * @return array<int, array{value: int, label: string}>
     */
    public function loadTagOptions(array $ids): array
    {
        $intIds = array_filter($ids, 'is_numeric');

        return CmsTag::whereIn('id', $intIds)
            ->get()
            ->map(fn (CmsTag $t) => ['value' => $t->id, 'label' => $t->name])
            ->all();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createTag(array $data): CmsTag
    {
        $tag = CmsTag::create($data);
        $this->logger->logCreate('tag', $tag, $tag->toArray());

        return $tag;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateTag(CmsTag $tag, array $data): CmsTag
    {
        $oldData = $tag->toArray();
        $tag->update($data);
        $this->logger->logUpdate('tag', $tag, $oldData);

        return $tag;
    }

    public function deleteTagByToken(string $token): CmsTag
    {
        $id = CmsTag::validateDeleteToken($token);
        if (! $id) {
            throw new InvalidArgumentException(__('cms::labels.invalid_delete_token'));
        }

        $tag = CmsTag::findOrFail($id);
        $this->logger->logDelete('tag', $tag);
        $tag->delete();

        return $tag;
    }

    private function findOrCreateTagByName(string $name): CmsTag
    {
        $slug = str($name)->lower()->slug()->toString();

        return CmsTag::firstOrCreate(['slug' => $slug], ['name' => $name]);
    }

    /**
     * Resolve tag inputs (mix of numeric IDs and string names) into a list of tag IDs,
     * creating new tags for string entries that don't yet exist.
     *
     * @param  array<int, int|string>  $tagInputs
     * @return array<int, int>
     */
    private function resolveTagIds(array $tagInputs): array
    {
        $ids = [];
        foreach ($tagInputs as $value) {
            if (is_numeric($value)) {
                $ids[] = (int) $value;

                continue;
            }
            $name = trim((string) $value);
            if ($name === '') {
                continue;
            }
            $ids[] = $this->findOrCreateTagByName($name)->id;
        }

        return $ids;
    }

    /**
     * Tag suggestion list for the article form.
     *
     * @return array<int, string>
     */
    public function getTagSuggestionMap(): array
    {
        return CmsTag::orderBy('name')->pluck('name', 'id')->all();
    }

    /**
     * @return 'asc'|'desc'
     */
    private function normalizeDirection(string $direction): string
    {
        $normalized = strtolower($direction);

        return in_array($normalized, ['asc', 'desc'], true) ? $normalized : 'asc';
    }
}
