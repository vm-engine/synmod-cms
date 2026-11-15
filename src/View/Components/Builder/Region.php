<?php

declare(strict_types=1);

namespace VmEngine\Cms\View\Components\Builder;

use Illuminate\View\Component;
use Illuminate\View\View;
use VmEngine\Cms\Support\CmsBuilderClassPalette;
use VmEngine\Cms\Support\CmsWidgetRegistry;

/**
 * One named region of a page builder template. Public mode renders the widgets
 * (and nothing when none render); edit mode adds the canvas chrome: wire:sort
 * hooks, a toolbar per widget, an error card for failing widgets, an add button.
 */
class Region extends Component
{
    public string $regionClass;

    public string $label;

    /** @var list<array{id: string, type: string, label: string, icon: string, class: string, heading: string, html: string|null, empty: bool}> */
    public array $items = [];

    /**
     * @param  array<string, mixed>  $layout
     * @param  array<string, string>  $regions  Region key => label (translation key or text).
     */
    public function __construct(
        CmsWidgetRegistry $registry,
        CmsBuilderClassPalette $palette,
        public string $name,
        public array $layout = [],
        public array $regions = [],
        public bool $editing = false,
    ) {
        /** @var array<string, mixed> $allRegions */
        $allRegions = is_array($layout['regions'] ?? null) ? $layout['regions'] : [];
        /** @var array{class?: array<int, mixed>, custom_class?: mixed, widgets?: array<int, array<string, mixed>>} $region */
        $region = is_array($allRegions[$name] ?? null) ? $allRegions[$name] : [];

        $this->regionClass = trim(implode(' ', $palette->filter($region['class'] ?? [])).' '.$palette->sanitizeCustom($region['custom_class'] ?? ''));
        $this->label = (string) __($regions[$name] ?? $name);

        foreach ($region['widgets'] ?? [] as $widget) {
            $type = is_string($widget['type'] ?? null) ? $widget['type'] : '';
            if (! $registry->has($type) || ! is_string($widget['id'] ?? null)) {
                continue;
            }

            /** @var array<string, mixed> $props */
            $props = is_array($widget['props'] ?? null) ? $widget['props'] : [];
            /** @var array<int, mixed> $classes */
            $classes = is_array($widget['class'] ?? null) ? $widget['class'] : [];
            $definition = $registry->get($type);
            $html = $registry->renderSafely($type, $props);

            $this->items[] = [
                'id' => $widget['id'],
                'type' => $type,
                'label' => (string) __($definition['label']),
                'icon' => $definition['icon'],
                'class' => trim(implode(' ', $palette->filter($classes)).' '.$palette->sanitizeCustom($widget['custom_class'] ?? '')),
                // Empty when unset or switched off.
                'heading' => ($widget['show_heading'] ?? true) && is_string($widget['heading'] ?? null) ? trim($widget['heading']) : '',
                'html' => $html,
                // An unconfigured widget (e.g. no album picked) renders nothing: hidden publicly,
                // a placeholder on the canvas so it stays selectable and draggable.
                'empty' => $html !== null && trim((string) preg_replace('/<!--.*?-->/s', '', $html)) === '',
            ];
        }
    }

    public function shouldRender(): bool
    {
        return $this->editing || $this->visibleItems() !== [];
    }

    /**
     * Items that produce output on the public page.
     *
     * @return list<array{id: string, type: string, label: string, icon: string, class: string, heading: string, html: string|null, empty: bool}>
     */
    public function visibleItems(): array
    {
        return array_values(array_filter($this->items, fn (array $item): bool => $item['html'] !== null && ! $item['empty']));
    }

    public function render(): View
    {
        return view('cms::components.builder.region');
    }
}
