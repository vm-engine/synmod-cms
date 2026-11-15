<?php

declare(strict_types=1);

namespace VmEngine\Cms\Support;

/**
 * Page builder templates: the package defaults plus whatever the active theme
 * declares in theme.json under `page_builder.templates`. A theme entry with the
 * same key replaces the package one; entries with a missing view or no regions
 * are ignored.
 *
 * @phpstan-type Template array{name: string, view: string, regions: array<string, string>}
 */
final class CmsBuilderTemplates
{
    public const DEFAULT = 'full';

    /** @var array<string, Template> */
    private const PACKAGE = [
        'full' => [
            'name' => 'cms::builder.template_full',
            'view' => 'cms::builder.templates.full',
            'regions' => ['hero' => 'cms::builder.region_hero', 'main' => 'cms::builder.region_main'],
        ],
        'sidebar-right' => [
            'name' => 'cms::builder.template_sidebar_right',
            'view' => 'cms::builder.templates.sidebar-right',
            'regions' => [
                'hero' => 'cms::builder.region_hero',
                'main' => 'cms::builder.region_main',
                'sidebar' => 'cms::builder.region_sidebar',
                'footer-top' => 'cms::builder.region_footer_top',
            ],
        ],
    ];

    /**
     * @return array<string, Template>
     */
    public function all(): array
    {
        $path = theme_service()->getThemePath('theme.json');
        $manifest = is_file($path) ? json_decode((string) file_get_contents($path), true) : null;
        $declared = is_array($manifest) ? ($manifest['page_builder']['templates'] ?? []) : [];

        return $this->merge(is_array($declared) ? $declared : []);
    }

    /**
     * @param  array<mixed>  $declared
     * @return array<string, Template>
     */
    public function merge(array $declared): array
    {
        $templates = self::PACKAGE;

        foreach ($declared as $key => $template) {
            if (! is_string($key) || ! is_array($template) || ! $this->isValid($template)) {
                continue;
            }

            /** @var array<string, mixed> $regions */
            $regions = $template['regions'];
            $templates[$key] = [
                'name' => is_string($template['name'] ?? null) ? $template['name'] : $key,
                'view' => (string) $template['view'],
                'regions' => array_map(fn (mixed $label): string => is_scalar($label) ? (string) $label : '', $regions),
            ];
        }

        return $templates;
    }

    /**
     * @return Template
     */
    public function find(string $key): array
    {
        $templates = $this->all();

        return $templates[$key] ?? $templates[self::DEFAULT];
    }

    /**
     * @param  array<mixed>  $template
     */
    private function isValid(array $template): bool
    {
        return is_string($template['view'] ?? null)
            && view()->exists($template['view'])
            && is_array($template['regions'] ?? null)
            && $template['regions'] !== [];
    }
}
