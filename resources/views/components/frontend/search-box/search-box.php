<?php

declare(strict_types=1);

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Computed;
use Livewire\Component;
use VmEngine\Cms\Models\CmsSearchable;
use VmEngine\Cms\Services\CmsSearchService;
use VmEngine\Cms\Services\CmsSearchTermService;
use VmEngine\Cms\Support\CmsUrl;

new class extends Component
{
    public string $q = '';

    public string $placeholder = '';

    public function searchUrl(?string $query = null): string
    {
        $path = CmsUrl::to((string) config('synapps.modules.cms.search.slug', 'search'));

        return $query === null ? $path : $path.'?q='.rawurlencode($query);
    }

    /**
     * @return Collection<int, CmsSearchable>
     */
    #[Computed]
    public function suggestions(): Collection
    {
        $term = mb_substr(trim($this->q), 0, 100);
        $key = 'cms-suggest:'.request()->ip();

        if (mb_strlen($term) < 2
            || RateLimiter::tooManyAttempts($key, (int) config('synapps.modules.cms.search.suggest_rate_limit', 120))) {
            return collect();
        }

        RateLimiter::hit($key, 60);

        return app(CmsSearchService::class)->suggest($term);
    }

    /**
     * @return list<string>
     */
    #[Computed]
    public function popular(): array
    {
        return app(CmsSearchTermService::class)->popular(app()->getLocale());
    }
};
