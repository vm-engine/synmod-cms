<?php

declare(strict_types=1);

namespace VmEngine\Cms\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use InvalidArgumentException;
use VmEngine\Cms\Models\CmsPage;

class CmsPageService
{
    public function __construct(private readonly CmsActivityLogger $logger) {}

    /**
     * @param  array<int, string>  $with
     */
    public function findById(int $id, array $with = []): ?CmsPage
    {
        return CmsPage::with($with)->find($id);
    }

    /**
     * @param  array<int, string>  $with
     */
    public function findOrFailById(int $id, array $with = []): CmsPage
    {
        return CmsPage::with($with)->findOrFail($id);
    }

    /**
     * @param  array<int|string, mixed>  $with
     */
    public function findPublishedOrFailById(int $id, array $with = []): CmsPage
    {
        return CmsPage::with($with)->published()->findOrFail($id);
    }

    /**
     * @param  array<int|string, mixed>  $with
     */
    public function findPublishedByFullSlug(string $fullSlug, array $with = []): ?CmsPage
    {
        return CmsPage::with($with)->published()->where('full_slug', $fullSlug)->first();
    }

    /**
     * Find a published root-level page (no parent) by its own slug — used by landing pages.
     *
     * @param  array<int|string, mixed>  $with
     */
    public function findPublishedRootBySlug(string $slug, array $with = []): ?CmsPage
    {
        return CmsPage::with($with)->published()->where('slug', $slug)->whereNull('parent_id')->first();
    }

    /**
     * Find a published page by its own slug, regardless of nesting level.
     *
     * @param  array<int|string, mixed>  $with
     */
    public function findPublishedBySlug(string $slug, array $with = []): ?CmsPage
    {
        return CmsPage::with($with)->published()->where('slug', $slug)->first();
    }

    /**
     * Backend list — flat search results when $q is given, otherwise a tree of root pages
     * with two levels of eager-loaded children.
     *
     * @return Collection<int, CmsPage>
     */
    public function listForBackend(?string $q, ?string $status): Collection
    {
        if ($q !== null && $q !== '' && strlen($q) > 2) {
            /** @var Collection<int, CmsPage> $results */
            $results = CmsPage::search($q)
                ->when($status, fn (Builder $b, string $s) => $b->where('status', $s))
                ->orderBy('full_slug')
                ->get();

            return $results;
        }

        /** @var Collection<int, CmsPage> $tree */
        $tree = CmsPage::roots()
            ->when($status, fn (Builder $b, string $s) => $b->where('status', $s))
            ->with([
                'children' => fn ($q) => $q->orderBy('sort_order'),
                'children.children' => fn ($q) => $q->orderBy('sort_order'),
            ])
            ->orderBy('sort_order')
            ->get();

        return $tree;
    }

    /**
     * Parent dropdown for the page form: root pages and their direct children, indented.
     *
     * @return array<int|string, string>
     */
    public function getParentOptions(?int $excludeId = null): array
    {
        $options = ['' => '-- No Parent --'];

        $pages = CmsPage::whereNull('parent_id')
            ->when($excludeId, fn (Builder $q, int $id) => $q->where('id', '!=', $id))
            ->with('children')
            ->orderBy('title')
            ->get();

        foreach ($pages as $page) {
            $options[$page->id] = $page->title;
            foreach ($page->children->where('id', '!=', $excludeId) as $child) {
                $options[$child->id] = '— '.$child->title;
            }
        }

        return $options;
    }

    /**
     * @return Collection<int, CmsPage>
     */
    public function getPublishedChildren(CmsPage $parent, int $limit): Collection
    {
        /** @var Collection<int, CmsPage> $children */
        $children = CmsPage::where('parent_id', $parent->id)
            ->published()
            ->orderBy('sort_order')
            ->limit($limit)
            ->get();

        return $children;
    }

    /**
     * @return Collection<int, CmsPage>
     */
    public function getPublishedDescendants(CmsPage $parent, int $limit): Collection
    {
        /** @var Collection<int, CmsPage> $descendants */
        $descendants = CmsPage::where('full_slug', 'like', $parent->full_slug.'/%')
            ->published()
            ->orderBy('sort_order')
            ->limit($limit)
            ->get();

        return $descendants;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, int $userId): CmsPage
    {
        $data['created_by'] = $userId;
        $data['full_slug'] = (new CmsPage($data))->computeFullSlug();

        $page = CmsPage::create($data);

        $this->logger->logCreate('page', $page, $page->toArray());

        return $page;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(CmsPage $page, array $data): CmsPage
    {
        $oldData = $page->toArray();

        $data['full_slug'] = (new CmsPage($data))->computeFullSlug();

        $page->update($data);

        $this->logger->logUpdate('page', $page, $oldData);

        return $page;
    }

    /**
     * Used by menu-item picker autocomplete.
     *
     * @return array<int, array{value: int, label: string}>
     */
    public function searchOptions(string $query, int $limit = 20): array
    {
        return CmsPage::where('title', 'like', "%{$query}%")
            ->limit($limit)
            ->get(['id', 'title', 'full_slug'])
            ->map(fn (CmsPage $p) => ['value' => $p->id, 'label' => $p->title.' (/'.$p->full_slug.')'])
            ->all();
    }

    /**
     * @param  array<int, int>|int  $ids
     * @return array<int, array{value: int, label: string}>
     */
    public function loadOptions(int|array $ids): array
    {
        return CmsPage::whereIn('id', (array) $ids)
            ->get(['id', 'title'])
            ->map(fn (CmsPage $p) => ['value' => $p->id, 'label' => $p->title])
            ->all();
    }

    public function deleteByToken(string $token): CmsPage
    {
        $id = CmsPage::validateDeleteToken($token);
        if (! $id) {
            throw new InvalidArgumentException(__('cms::labels.invalid_delete_token'));
        }

        $page = CmsPage::findOrFail($id);
        $this->logger->logDelete('page', $page);
        $page->delete();

        return $page;
    }
}
