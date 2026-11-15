<?php

declare(strict_types=1);

namespace VmEngine\Cms\Services;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Mews\Purifier\Facades\Purifier;
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
        $page = CmsPage::with($with)->published()->where('full_slug', $fullSlug)->first();

        return $page !== null && app(CmsAccessService::class)->canView($page, auth()->user()) ? $page : null;
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
                ->with('translations')
                ->when($status, fn (Builder $b, string $s) => $b->where('status', $s))
                ->orderBy('full_slug')
                ->get();

            return $results;
        }

        /** @var Collection<int, CmsPage> $tree */
        $tree = CmsPage::roots()
            ->with('translations')
            ->when($status, fn (Builder $b, string $s) => $b->where('status', $s))
            ->with([
                'children' => fn ($q) => $q->orderBy('sort_order'),
                'children.translations',
                'children.children' => fn ($q) => $q->orderBy('sort_order'),
                'children.children.translations',
            ])
            ->orderBy('sort_order')
            ->get();

        return $tree;
    }

    /**
     * Direct children of $parentId (null = root pages), in display order.
     *
     * @return Collection<int, CmsPage>
     */
    public function siblingsOf(?int $parentId): Collection
    {
        return $this->childrenOfQuery($parentId)
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Persist a new sibling order under $parentId (null = root pages). Ids that are not
     * direct children of $parentId are ignored, so a page can never change parent here.
     *
     * @param  array<int, int>  $ids
     */
    public function reorderChildren(?int $parentId, array $ids): void
    {
        DB::transaction(function () use ($parentId, $ids): void {
            foreach (array_values($ids) as $order => $id) {
                $this->childrenOfQuery($parentId)
                    ->whereKey($id)
                    ->update(['sort_order' => $order]);
            }
        });

        $this->logger->logReorder('page', 'children', ($parentId !== null ? CmsPage::find($parentId) : null) ?? new CmsPage, $ids);
    }

    /**
     * @return Builder<CmsPage>
     */
    private function childrenOfQuery(?int $parentId): Builder
    {
        return $parentId === null
            ? CmsPage::query()->whereNull('parent_id')
            : CmsPage::query()->where('parent_id', $parentId);
    }

    /**
     * Parent dropdown for the page form: root pages and their direct children, indented.
     *
     * @return array<int, array{value: int|string, label: string, depth?: int}>
     */
    public function getParentOptions(?int $excludeId = null): array
    {
        $options = [['value' => '', 'label' => '-- No Parent --']];

        $pages = CmsPage::whereNull('parent_id')
            ->when($excludeId, fn (Builder $q, int $id) => $q->where('id', '!=', $id))
            ->with('children')
            ->orderBy('title')
            ->get();

        foreach ($pages as $page) {
            $options[] = ['value' => $page->id, 'label' => $page->title, 'depth' => 0];
            foreach ($page->children->where('id', '!=', $excludeId) as $child) {
                $options[] = ['value' => $child->id, 'label' => $child->title, 'depth' => 1];
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

        // ponytail: filtered after LIMIT, so hidden pages can shorten the list
        return app(CmsAccessService::class)->filterVisiblePages($children);
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

        return app(CmsAccessService::class)->filterVisiblePages($descendants);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, int $userId): CmsPage
    {
        if (isset($data['body'])) {
            $data['body'] = Purifier::clean($data['body'], 'cms_rte');
        }

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

        if (isset($data['body'])) {
            $data['body'] = Purifier::clean($data['body'], 'cms_rte');
        }

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

    public function deleteByToken(string $token, ?Closure $authorize = null): CmsPage
    {
        $id = CmsPage::validateDeleteToken($token);
        if (! $id) {
            throw new InvalidArgumentException(__('cms::labels.invalid_delete_token'));
        }

        $page = CmsPage::findOrFail($id);

        $authorize?->__invoke($page);
        $this->logger->logDelete('page', $page);
        $page->delete();

        return $page;
    }
}
