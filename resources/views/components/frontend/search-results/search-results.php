<?php

declare(strict_types=1);

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use VmEngine\Cms\Models\CmsSearchable;
use VmEngine\Cms\Services\CmsSearchService;
use VmEngine\Cms\Services\CmsSearchTermService;
use VmEngine\Cms\Support\FrontendViewResolver;

new class extends Component
{
    use WithPagination;

    #[Url]
    public string $q = '';

    #[Url]
    public string $type = '';

    public bool $rateLimited = false;

    public function updated(string $property): void
    {
        if (in_array($property, ['q', 'type'], true)) {
            $this->resetPage();
        }
    }

    public function term(): string
    {
        return mb_substr(trim($this->q), 0, 100);
    }

    /**
     * @return LengthAwarePaginator<int, CmsSearchable>|null
     */
    #[Computed]
    public function results(): ?LengthAwarePaginator
    {
        $term = $this->term();

        if (mb_strlen($term) < 2 || ! $this->withinRateLimit()) {
            return null;
        }

        $types = CmsSearchService::FILTERS[$this->type] ?? null;
        $results = app(CmsSearchService::class)->search(
            $term,
            $types,
            (int) config('synapps.modules.cms.search.per_page', 10),
        );

        if ($results->total() > 0) {
            app(CmsSearchTermService::class)->record($term, app()->getLocale());
        }

        return $results;
    }

    /**
     * @return array<string, int>
     */
    #[Computed]
    public function counts(): array
    {
        return $this->results() === null ? [] : app(CmsSearchService::class)->typeCounts($this->term());
    }

    /**
     * @return list<string>
     */
    #[Computed]
    public function popular(): array
    {
        return app(CmsSearchTermService::class)->popular(app()->getLocale());
    }

    private function withinRateLimit(): bool
    {
        $key = 'cms-search:'.request()->ip();

        if (RateLimiter::tooManyAttempts($key, (int) config('synapps.modules.cms.search.rate_limit', 30))) {
            $this->rateLimited = true;

            return false;
        }

        RateLimiter::hit($key, 60);
        $this->rateLimited = false;

        return true;
    }

    public function render()
    {
        return $this->view()
            ->title(__('cms::labels.search_results'))
            ->layout(FrontendViewResolver::layout());
    }
};
