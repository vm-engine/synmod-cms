<?php

declare(strict_types=1);

namespace VmEngine\Cms\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use InvalidArgumentException;
use VmEngine\Cms\Enums\AlbumType;
use VmEngine\Cms\Models\CmsAlbum;
use VmEngine\Cms\Models\CmsBuilderPage;
use VmEngine\Cms\Models\CmsCategory;
use VmEngine\Cms\Models\CmsPage;
use VmEngine\Cms\Models\CmsSection;
use VmEngine\Cms\Support\CmsBuilderClassPalette;
use VmEngine\Cms\Support\CmsBuilderTemplates;
use VmEngine\Cms\Support\CmsWidgetRegistry;
use VmEngine\SynFallback\Facades\Fallback;

/**
 * Every read and write for page builder pages. Layout edits only ever touch
 * `layout_draft`; publish() copies it to `layout`. Every draft write runs
 * through normalize(), so stored layouts always match their template.
 *
 * @phpstan-type Widget array{id: string, type: string, props: array<string, mixed>, class: list<string>, custom_class: string, heading: string, show_heading: bool}
 * @phpstan-type Layout array{regions: array<string, array{class: list<string>, custom_class: string, widgets: list<Widget>}>}
 */
class CmsBuilderPageService
{
    public function __construct(
        private readonly CmsActivityLogger $logger,
        private readonly CmsWidgetRegistry $registry,
        private readonly CmsBuilderTemplates $templates,
        private readonly CmsBuilderClassPalette $palette,
    ) {}

    /**
     * @param  array{q?: string}  $filters
     * @return LengthAwarePaginator<int, CmsBuilderPage>
     */
    public function paginate(array $filters, int $perPage): LengthAwarePaginator
    {
        $query = CmsBuilderPage::query();

        if (! empty($filters['q']) && strlen($filters['q']) > 1) {
            $query->search($filters['q']);
        }

        return $query->latest('updated_at')->paginate($perPage)->onEachSide(1);
    }

    public function findOrFailById(int $id): CmsBuilderPage
    {
        return CmsBuilderPage::findOrFail($id);
    }

    public function findPublishedOrFailById(int $id): CmsBuilderPage
    {
        return CmsBuilderPage::published()->findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?int $userId = null): CmsBuilderPage
    {
        $template = is_string($data['template'] ?? null) ? $data['template'] : CmsBuilderTemplates::DEFAULT;

        $page = CmsBuilderPage::create([
            ...$data,
            'template' => $template,
            'status' => 'draft',
            'created_by' => $userId,
            'layout_draft' => $this->normalize(['regions' => []], $template),
        ]);
        $this->logger->logCreate('builder_page', $page, $page->toArray());

        return $page;
    }

    /**
     * Save attributes; a template change re-normalizes the draft for the new regions.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(CmsBuilderPage $page, array $data): CmsBuilderPage
    {
        $oldData = $page->toArray();
        $page->fill($data);

        if ($page->isDirty('template')) {
            $page->layout_draft = $this->normalize($page->layout_draft ?? [], $page->template);
        }

        $page->save();
        $this->logger->logUpdate('builder_page', $page, $oldData);

        return $page;
    }

    public function deleteByToken(string $token): CmsBuilderPage
    {
        $id = CmsBuilderPage::validateDeleteToken($token);
        if (! $id) {
            throw new InvalidArgumentException(__('cms::labels.invalid_delete_token'));
        }

        $page = CmsBuilderPage::findOrFail($id);
        $this->logger->logDelete('builder_page', $page);
        $page->delete();

        return $page;
    }

    public function publish(CmsBuilderPage $page): void
    {
        $this->update($page, [
            'layout' => $page->layout_draft,
            'status' => 'published',
            'published_at' => $page->published_at ?? now(),
        ]);
    }

    public function unpublish(CmsBuilderPage $page): void
    {
        $this->update($page, ['status' => 'draft']);
    }

    public function switchTemplate(CmsBuilderPage $page, string $template): void
    {
        if (! array_key_exists($template, $this->templates->all())) {
            throw new InvalidArgumentException("Unknown builder template [{$template}].");
        }

        $this->update($page, ['template' => $template]);
    }

    /** @return string The new widget's id. */
    public function addWidget(CmsBuilderPage $page, string $region, string $type): string
    {
        $this->assertRegion($page, $region);
        $this->registry->get($type);
        $id = (string) Str::uuid();

        $this->mutateDraft($page, function (array $layout) use ($region, $type, $id): array {
            $layout['regions'][$region]['widgets'][] = [
                'id' => $id,
                'type' => $type,
                'props' => $this->registry->sanitizeProps($type, []),
                'class' => [],
                'custom_class' => '',
                'heading' => '',
                'show_heading' => true,
            ];

            return $layout;
        });

        return $id;
    }

    public function moveWidget(CmsBuilderPage $page, string $id, int $position, string $region): void
    {
        $this->assertRegion($page, $region);

        $this->mutateDraft($page, function (array $layout) use ($id, $position, $region): array {
            [$layout, $widget] = $this->pull($layout, $id);
            array_splice($layout['regions'][$region]['widgets'], max(0, $position), 0, [$widget]);

            return $layout;
        });
    }

    /** @return string The copy's id. */
    public function duplicateWidget(CmsBuilderPage $page, string $id): string
    {
        $copyId = (string) Str::uuid();

        $this->mutateDraft($page, function (array $layout) use ($id, $copyId): array {
            [$key, $index] = $this->locate($layout, $id);
            $copy = [...$layout['regions'][$key]['widgets'][$index], 'id' => $copyId];
            array_splice($layout['regions'][$key]['widgets'], $index + 1, 0, [$copy]);

            return $layout;
        });

        return $copyId;
    }

    public function removeWidget(CmsBuilderPage $page, string $id): void
    {
        $this->mutateDraft($page, fn (array $layout): array => $this->pull($layout, $id)[0]);
    }

    /**
     * @param  array<string, mixed>  $props
     * @param  array<int|string, mixed>  $classes
     */
    public function updateWidget(
        CmsBuilderPage $page,
        string $id,
        array $props,
        array $classes,
        string $customClass = '',
        string $heading = '',
        bool $showHeading = true,
    ): void {
        $this->mutateDraft($page, function (array $layout) use ($id, $props, $classes, $customClass, $heading, $showHeading): array {
            [$key, $index] = $this->locate($layout, $id);
            $layout['regions'][$key]['widgets'][$index]['props'] = $props;
            $layout['regions'][$key]['widgets'][$index]['class'] = $classes;
            $layout['regions'][$key]['widgets'][$index]['custom_class'] = $customClass;
            $layout['regions'][$key]['widgets'][$index]['heading'] = $heading;
            $layout['regions'][$key]['widgets'][$index]['show_heading'] = $showHeading;

            return $layout;
        });
    }

    /**
     * @param  array<int|string, mixed>  $classes
     */
    public function updateRegionClasses(CmsBuilderPage $page, string $region, array $classes, string $customClass = ''): void
    {
        $this->assertRegion($page, $region);

        $this->mutateDraft($page, function (array $layout) use ($region, $classes, $customClass): array {
            $layout['regions'][$region]['class'] = $classes;
            $layout['regions'][$region]['custom_class'] = $customClass;

            return $layout;
        });
    }

    /**
     * @return Widget|null
     */
    public function findDraftWidget(CmsBuilderPage $page, string $id): ?array
    {
        $layout = $this->normalize($page->layout_draft ?? [], $page->template);

        foreach ($layout['regions'] as $region) {
            foreach ($region['widgets'] as $widget) {
                if ($widget['id'] === $id) {
                    return $widget;
                }
            }
        }

        return null;
    }

    /**
     * adv-select options for a `source` widget prop; the value is the slug the widget expects.
     * Pages come in tree order with a `depth`, which adv-select indents.
     * ponytail: loads every row; switch to remote adv-select search if a site grows to thousands.
     *
     * @return list<array{value: string, label: string, depth?: int}>
     */
    public function sourceOptions(string $source): array
    {
        if ($source === 'pages') {
            return $this->pageTreeOptions();
        }

        $pairs = match ($source) {
            'sections' => CmsSection::orderBy('title')->pluck('title', 'slug')->all(),
            'categories' => CmsCategory::with('section')->orderBy('title')->get()
                ->mapWithKeys(fn (CmsCategory $category): array => [$category->slug => ($category->section->title ?? '').' › '.$category->title])
                ->all(),
            'albums' => CmsAlbum::where('type', AlbumType::Normal)->orderBy('title')->pluck('title', 'slug')->all(),
            'banners' => CmsAlbum::where('type', AlbumType::Banner)->orderBy('title')->pluck('title', 'slug')->all(),
            default => [],
        };

        $options = [];
        foreach ($pairs as $value => $label) {
            $options[] = ['value' => (string) $value, 'label' => (string) $label];
        }

        return $options;
    }

    /**
     * Every page, depth-first by sort_order then title.
     *
     * @return list<array{value: string, label: string, depth: int}>
     */
    private function pageTreeOptions(): array
    {
        $byParent = CmsPage::orderBy('sort_order')->orderBy('title')->get(['id', 'parent_id', 'title', 'full_slug'])->groupBy('parent_id');
        $options = [];

        $walk = function (?int $parentId, int $depth) use (&$walk, &$options, $byParent): void {
            foreach ($byParent->get($parentId ?? '', collect()) as $page) {
                $options[] = ['value' => (string) $page->full_slug, 'label' => (string) $page->title, 'depth' => $depth];
                $walk($page->id, $depth + 1);
            }
        };
        $walk(null, 0);

        return $options;
    }

    /**
     * adv-select search (menu item targets): published pages only, since drafts have no URL.
     *
     * @return array<int, array{value: int, label: string}>
     */
    public function searchOptions(string $query, int $limit = 20): array
    {
        return CmsBuilderPage::published()
            ->where('title', 'like', "%{$query}%")
            ->limit($limit)
            ->get(['id', 'title', 'slug'])
            ->map(fn (CmsBuilderPage $page): array => ['value' => $page->id, 'label' => $page->title.' (/'.$page->slug.')'])
            ->all();
    }

    /**
     * @param  array<int, int>|int  $ids
     * @return array<int, array{value: int, label: string}>
     */
    public function loadOptions(int|array $ids): array
    {
        return CmsBuilderPage::whereIn('id', (array) $ids)
            ->get(['id', 'title'])
            ->map(fn (CmsBuilderPage $page): array => ['value' => $page->id, 'label' => $page->title])
            ->all();
    }

    /** True when the path is used by a CMS page, another builder page or any other fallback route. */
    public function slugIsTaken(string $slug, ?int $ignoreId = null): bool
    {
        if (CmsPage::where('full_slug', $slug)->exists()) {
            return true;
        }

        $others = CmsBuilderPage::where('slug', $slug);
        if ($ignoreId !== null) {
            $others->whereKeyNot($ignoreId);
        }

        if ($others->exists()) {
            return true;
        }

        $currentSlug = $ignoreId !== null ? CmsBuilderPage::whereKey($ignoreId)->value('slug') : null;

        return $currentSlug !== $slug && Fallback::exists($slug);
    }

    /**
     * Shape a layout for $template: every template region present, widgets in unknown
     * regions moved to the first region, unknown widget types dropped, props and
     * classes sanitized.
     *
     * @param  array<string, mixed>  $layout
     * @return Layout
     */
    public function normalize(array $layout, string $template): array
    {
        $regionKeys = array_keys($this->templates->find($template)['regions']);
        $first = $regionKeys[0];
        $classes = array_fill_keys($regionKeys, []);
        $customClasses = array_fill_keys($regionKeys, '');
        $widgets = array_fill_keys($regionKeys, []);
        $regions = is_array($layout['regions'] ?? null) ? $layout['regions'] : [];

        foreach ($regions as $key => $region) {
            if (! is_array($region)) {
                continue;
            }

            $target = in_array($key, $regionKeys, true) ? (string) $key : $first;

            if ($target === $key) {
                $classes[$target] = $this->palette->filter(is_array($region['class'] ?? null) ? $region['class'] : []);
                $customClasses[$target] = $this->palette->sanitizeCustom($region['custom_class'] ?? '');
            }

            foreach (is_array($region['widgets'] ?? null) ? $region['widgets'] : [] as $widget) {
                if (! is_array($widget) || ! is_string($widget['type'] ?? null) || ! $this->registry->has($widget['type'])) {
                    continue;
                }

                $widgets[$target][] = [
                    'id' => is_string($widget['id'] ?? null) ? $widget['id'] : (string) Str::uuid(),
                    'type' => $widget['type'],
                    'props' => $this->registry->sanitizeProps($widget['type'], is_array($widget['props'] ?? null) ? $widget['props'] : []),
                    'class' => $this->palette->filter(is_array($widget['class'] ?? null) ? $widget['class'] : []),
                    'custom_class' => $this->palette->sanitizeCustom($widget['custom_class'] ?? ''),
                    // Plain text, printed escaped above the widget by the region.
                    'heading' => mb_substr(trim(strip_tags(is_string($widget['heading'] ?? null) ? $widget['heading'] : '')), 0, 120),
                    'show_heading' => (bool) ($widget['show_heading'] ?? true),
                ];
            }
        }

        $result = ['regions' => []];
        foreach ($regionKeys as $key) {
            $result['regions'][$key] = ['class' => $classes[$key], 'custom_class' => $customClasses[$key], 'widgets' => $widgets[$key]];
        }

        return $result;
    }

    /**
     * @param  callable(Layout): array<string, mixed>  $change
     */
    private function mutateDraft(CmsBuilderPage $page, callable $change): void
    {
        $layout = $this->normalize($page->layout_draft ?? [], $page->template);

        $this->update($page, ['layout_draft' => $this->normalize($change($layout), $page->template)]);
    }

    /**
     * @param  Layout  $layout
     * @return array{0: string, 1: int} Region key and index of the widget.
     */
    private function locate(array $layout, string $id): array
    {
        foreach ($layout['regions'] as $key => $region) {
            foreach ($region['widgets'] as $index => $widget) {
                if ($widget['id'] === $id) {
                    return [$key, $index];
                }
            }
        }

        throw new InvalidArgumentException(__('cms::builder.widget_missing'));
    }

    /**
     * @param  Layout  $layout
     * @return array{0: Layout, 1: Widget}
     */
    private function pull(array $layout, string $id): array
    {
        [$key, $index] = $this->locate($layout, $id);
        $widgets = $layout['regions'][$key]['widgets'];
        $widget = $widgets[$index];
        array_splice($widgets, $index, 1);
        $layout['regions'][$key] = [...$layout['regions'][$key], 'widgets' => $widgets];

        return [$layout, $widget];
    }

    private function assertRegion(CmsBuilderPage $page, string $region): void
    {
        if (! array_key_exists($region, $this->templates->find($page->template)['regions'])) {
            throw new InvalidArgumentException(__('cms::builder.region_missing'));
        }
    }
}
