<?php

declare(strict_types=1);

namespace VmEngine\Cms;

use Illuminate\Support\ServiceProvider;
use VmEngine\Cms\Console\Commands\PatchResourcesCommand;
use VmEngine\Cms\Console\Commands\PruneMediaGalleriesCommand;
use VmEngine\Cms\Console\Commands\SanitizeContentCommand;
use VmEngine\Cms\Console\Commands\SearchReindexCommand;
use VmEngine\Cms\Console\Commands\SetupCommand;
use VmEngine\Cms\Models\CmsAlbum;
use VmEngine\Cms\Models\CmsAlbumImage;
use VmEngine\Cms\Models\CmsArticle;
use VmEngine\Cms\Models\CmsCategory;
use VmEngine\Cms\Models\CmsDownload;
use VmEngine\Cms\Models\CmsDownloadFile;
use VmEngine\Cms\Models\CmsMenuItem;
use VmEngine\Cms\Models\CmsPage;
use VmEngine\Cms\Models\CmsSection;
use VmEngine\Cms\Observers\CmsAlbumImageObserver;
use VmEngine\Cms\Observers\CmsAlbumObserver;
use VmEngine\Cms\Observers\CmsArticleObserver;
use VmEngine\Cms\Observers\CmsCategoryObserver;
use VmEngine\Cms\Observers\CmsDownloadFileObserver;
use VmEngine\Cms\Observers\CmsDownloadObserver;
use VmEngine\Cms\Observers\CmsMenuItemObserver;
use VmEngine\Cms\Observers\CmsPageObserver;
use VmEngine\Cms\Observers\CmsSectionObserver;
use VmEngine\Cms\Services\Analytics\FakeGoogleApiClient;
use VmEngine\Cms\Services\Analytics\GoogleApiClient;
use VmEngine\Cms\Services\CmsAccessService;
use VmEngine\Cms\Services\CmsCustomFieldService;
use VmEngine\Synapse\Traits\AutoRegistersComponents;

class CmsServiceProvider extends ServiceProvider
{
    use AutoRegistersComponents;

    public function register(): void
    {
        $this->registerComponents();

        $this->app->scoped(CmsAccessService::class);
        $this->app->scoped(CmsCustomFieldService::class);

        // Dev-only dummy Google data for the Analytics dashboard. Never in production,
        // and never under tests, so a host's local flag can't leak into the suite.
        if (config('synapps.modules.cms.analytics.fake') && ! $this->app->environment(['production', 'testing'])) {
            $this->app->bind(GoogleApiClient::class, FakeGoogleApiClient::class);
        }

        $this->mergeConfigFrom(__DIR__.'/../config/cms-purifier.php', 'purifier.settings.cms_rte');

        // Pin the primary content locale before any middleware runs:
        // app()->setLocale() rewrites config('app.locale') per request.
        config(['synapps.modules.cms.primary_locale' => config('synapps.modules.cms.primary_locale') ?: config('app.locale')]);
    }

    public function boot(): void
    {
        $this->embedHtml5MediaSources();

        if ($this->app->runningInConsole()) {
            $this->commands([
                SetupCommand::class,
                PatchResourcesCommand::class,
                SanitizeContentCommand::class,
                SearchReindexCommand::class,
                PruneMediaGalleriesCommand::class,
            ]);
        }

        CmsPage::observe(CmsPageObserver::class);
        CmsSection::observe(CmsSectionObserver::class);
        CmsCategory::observe(CmsCategoryObserver::class);
        CmsArticle::observe(CmsArticleObserver::class);
        CmsAlbum::observe(CmsAlbumObserver::class);
        CmsAlbumImage::observe(CmsAlbumImageObserver::class);
        CmsDownload::observe(CmsDownloadObserver::class);
        CmsDownloadFile::observe(CmsDownloadFileObserver::class);
        CmsMenuItem::observe(CmsMenuItemObserver::class);
    }

    /**
     * mews/purifier's html5 definition types <video>/<source> `src` and `poster`
     * as plain link URIs, so URI.DisableExternalResources never applies to them.
     * Retype them as embedded resources (like <img src>) so cms_rte's local-only
     * media rule holds. The rev bump invalidates HTMLPurifier's cached definition.
     */
    private function embedHtml5MediaSources(): void
    {
        $key = 'purifier.settings.custom_definition';
        $definition = config($key);

        if (! is_array($definition) || empty($definition['elements'])) {
            return;
        }

        foreach ($definition['elements'] as $index => $element) {
            if (! in_array($element[0] ?? null, ['video', 'source'], true)) {
                continue;
            }

            foreach (['src', 'poster'] as $attribute) {
                if (isset($element[4][$attribute])) {
                    $definition['elements'][$index][4][$attribute] = 'URI#embedded';
                }
            }
        }

        $definition['rev'] = (int) ($definition['rev'] ?? 1) + 1;

        config([$key => $definition]);
    }
}
