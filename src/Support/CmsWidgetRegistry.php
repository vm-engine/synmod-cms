<?php

declare(strict_types=1);

namespace VmEngine\Cms\Support;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Throwable;

/**
 * Widgets the page builder can place. Each definition maps a type key to a Blade
 * component tag and a prop schema that drives the settings form and sanitizing.
 * The widgets' template/card/with/bare/wrapper-class props are deliberately not
 * exposed: builder styling goes through the class palette wrapper instead.
 *
 * @phpstan-type PropSchema array{type: string, label: string, default: mixed, options?: array<string, string>, source?: string}
 * @phpstan-type WidgetDefinition array{label: string, icon: string, component: string, props: array<string, PropSchema>}
 */
final class CmsWidgetRegistry
{
    private const SORTS = [
        'latest' => 'cms::builder.sort_latest',
        'oldest' => 'cms::builder.sort_oldest',
        'featured' => 'cms::builder.sort_featured',
        'az' => 'cms::builder.sort_az',
        'za' => 'cms::builder.sort_za',
    ];

    private const LAYOUTS = ['grid' => 'cms::builder.layout_grid', 'list' => 'cms::builder.layout_list'];

    /** @var array<string, WidgetDefinition> */
    private array $widgets = [];

    public function __construct()
    {
        $this->registerDefaults();
    }

    /**
     * @param  WidgetDefinition  $definition
     */
    public function register(string $type, array $definition): void
    {
        $this->widgets[$type] = $definition;
    }

    /**
     * @return array<string, WidgetDefinition>
     */
    public function all(): array
    {
        return $this->widgets;
    }

    public function has(string $type): bool
    {
        return isset($this->widgets[$type]);
    }

    /**
     * @return WidgetDefinition
     */
    public function get(string $type): array
    {
        return $this->widgets[$type] ?? throw new InvalidArgumentException("Unknown builder widget [{$type}].");
    }

    /**
     * Keep only schema props, cast to their type, falling back to the default.
     *
     * @param  array<string, mixed>  $props
     * @return array<string, mixed>
     */
    public function sanitizeProps(string $type, array $props): array
    {
        $clean = [];

        foreach ($this->get($type)['props'] as $key => $schema) {
            $value = $props[$key] ?? $schema['default'];

            $clean[$key] = match ($schema['type']) {
                'int' => max(0, (int) (is_scalar($value) ? $value : 0)),
                'bool' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
                'select' => is_scalar($value) && array_key_exists((string) $value, $schema['options'] ?? []) ? (string) $value : $schema['default'],
                default => Str::limit(trim(is_scalar($value) ? (string) $value : ''), 255, ''),
            };
        }

        return $clean;
    }

    /**
     * @param  array<string, mixed>  $props
     */
    public function render(string $type, array $props): string
    {
        $props = $this->sanitizeProps($type, $props);
        // Keys come from the schema, never from user input, so they are safe inside the tag.
        $bindings = collect(array_keys($props))
            ->map(fn (string $key): string => ':'.Str::kebab($key).'="$props[\''.$key.'\']"')
            ->implode(' ');

        return Blade::render('<x-'.$this->get($type)['component'].' '.$bindings.' />', ['props' => $props]);
    }

    /**
     * Render for display; a failing widget is logged and skipped instead of breaking the page.
     *
     * @param  array<string, mixed>  $props
     */
    public function renderSafely(string $type, array $props): ?string
    {
        try {
            return $this->render($type, $props);
        } catch (Throwable $e) {
            Log::warning('CMS page builder widget failed to render', ['type' => $type, 'error' => $e->getMessage()]);

            return null;
        }
    }

    private function registerDefaults(): void
    {
        $bool = fn (string $label, bool $default = true): array => ['type' => 'bool', 'label' => $label, 'default' => $default];
        $int = fn (string $label, int $default): array => ['type' => 'int', 'label' => $label, 'default' => $default];
        $source = fn (string $label, string $source): array => ['type' => 'source', 'label' => $label, 'default' => '', 'source' => $source];
        $sort = ['type' => 'select', 'label' => 'cms::builder.prop_sort', 'default' => 'latest', 'options' => self::SORTS];
        $layout = ['type' => 'select', 'label' => 'cms::builder.prop_layout', 'default' => 'grid', 'options' => self::LAYOUTS];

        $this->register('page', [
            'label' => 'cms::builder.widget_page',
            'icon' => 'ph ph-file-text',
            'component' => 'cms::widget.page',
            'props' => [
                'slug' => $source('cms::builder.prop_page', 'pages'),
                'showTitle' => $bool('cms::builder.prop_show_title'),
                'showCover' => $bool('cms::builder.prop_show_cover'),
            ],
        ]);

        $this->register('page-children', [
            'label' => 'cms::builder.widget_page_children',
            'icon' => 'ph ph-tree-structure',
            'component' => 'cms::widget.page-children',
            'props' => [
                'slug' => $source('cms::builder.prop_page', 'pages'),
                'layout' => $layout,
                'limit' => $int('cms::builder.prop_limit', 12),
                'showTitle' => $bool('cms::builder.prop_show_title'),
                'showCover' => $bool('cms::builder.prop_show_cover'),
                'showExcerpt' => $bool('cms::builder.prop_show_excerpt'),
                'recursive' => $bool('cms::builder.prop_recursive', false),
            ],
        ]);

        $this->register('articles', [
            'label' => 'cms::builder.widget_articles',
            'icon' => 'ph ph-newspaper',
            'component' => 'cms::widget.articles',
            'props' => [
                'limit' => $int('cms::builder.prop_limit', 6),
                'sort' => $sort,
                'layout' => $layout,
                'showSection' => $bool('cms::builder.prop_show_section'),
                'showExcerpt' => $bool('cms::builder.prop_show_excerpt'),
                'showCover' => $bool('cms::builder.prop_show_cover'),
                'showDate' => $bool('cms::builder.prop_show_date'),
                'showFeaturedBadge' => $bool('cms::builder.prop_show_featured_badge'),
            ],
        ]);

        $this->register('section-articles', [
            'label' => 'cms::builder.widget_section_articles',
            'icon' => 'ph ph-stack',
            'component' => 'cms::widget.section-articles',
            'props' => [
                'section' => $source('cms::builder.prop_section', 'sections'),
                'limit' => $int('cms::builder.prop_limit', 6),
                'sort' => $sort,
                'layout' => $layout,
                'showCategory' => $bool('cms::builder.prop_show_category'),
                'showExcerpt' => $bool('cms::builder.prop_show_excerpt'),
                'showCover' => $bool('cms::builder.prop_show_cover'),
                'showDate' => $bool('cms::builder.prop_show_date'),
            ],
        ]);

        $this->register('category-articles', [
            'label' => 'cms::builder.widget_category_articles',
            'icon' => 'ph ph-folder',
            'component' => 'cms::widget.category-articles',
            'props' => [
                'section' => $source('cms::builder.prop_section', 'sections'),
                'category' => $source('cms::builder.prop_category', 'categories'),
                'limit' => $int('cms::builder.prop_limit', 6),
                'sort' => $sort,
                'layout' => $layout,
                'showExcerpt' => $bool('cms::builder.prop_show_excerpt'),
                'showCover' => $bool('cms::builder.prop_show_cover'),
                'showDate' => $bool('cms::builder.prop_show_date'),
            ],
        ]);

        $this->register('tag-articles', [
            'label' => 'cms::builder.widget_tag_articles',
            'icon' => 'ph ph-tag',
            'component' => 'cms::widget.tag-articles',
            'props' => [
                'tags' => ['type' => 'text', 'label' => 'cms::builder.prop_tags', 'default' => ''],
                'match' => ['type' => 'select', 'label' => 'cms::builder.prop_match', 'default' => 'any', 'options' => ['any' => 'cms::builder.match_any', 'all' => 'cms::builder.match_all']],
                'limit' => $int('cms::builder.prop_limit', 6),
                'sort' => $sort,
                'layout' => $layout,
                'showSection' => $bool('cms::builder.prop_show_section'),
                'showExcerpt' => $bool('cms::builder.prop_show_excerpt'),
                'showCover' => $bool('cms::builder.prop_show_cover'),
                'showDate' => $bool('cms::builder.prop_show_date'),
            ],
        ]);

        $this->register('banner-slider', [
            'label' => 'cms::builder.widget_banner_slider',
            'icon' => 'ph ph-slideshow',
            'component' => 'cms::widget.banner-slider',
            'props' => [
                'album' => $source('cms::builder.prop_banner', 'banners'),
                'autoplay' => $bool('cms::builder.prop_autoplay'),
                'interval' => $int('cms::builder.prop_interval', 5000),
                'showCaption' => $bool('cms::builder.prop_show_caption'),
                'showControls' => $bool('cms::builder.prop_show_controls'),
                'showIndicators' => $bool('cms::builder.prop_show_indicators'),
                // A select, not free text: the value lands in an inline style.
                'height' => ['type' => 'select', 'label' => 'cms::builder.prop_height', 'default' => '480px', 'options' => ['320px' => '320px', '480px' => '480px', '640px' => '640px', '100vh' => 'cms::builder.height_screen']],
            ],
        ]);

        $this->register('gallery', [
            'label' => 'cms::builder.widget_gallery',
            'icon' => 'ph ph-images',
            'component' => 'cms::widget.gallery',
            'props' => [
                'album' => $source('cms::builder.prop_album', 'albums'),
                'limit' => $int('cms::builder.prop_limit', 0),
                'showAlbumTitle' => $bool('cms::builder.prop_show_album_title'),
                'showTitle' => $bool('cms::builder.prop_show_image_titles'),
                'showCaption' => $bool('cms::builder.prop_show_caption'),
            ],
        ]);
    }
}
