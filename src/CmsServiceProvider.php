<?php

declare(strict_types=1);

namespace VmEngine\Cms;

use Illuminate\Support\ServiceProvider;
use VmEngine\Cms\Console\Commands\PatchResourcesCommand;
use VmEngine\Cms\Models\CmsAlbum;
use VmEngine\Cms\Models\CmsArticle;
use VmEngine\Cms\Models\CmsCategory;
use VmEngine\Cms\Models\CmsPage;
use VmEngine\Cms\Models\CmsSection;
use VmEngine\Cms\Observers\CmsAlbumObserver;
use VmEngine\Cms\Observers\CmsArticleObserver;
use VmEngine\Cms\Observers\CmsCategoryObserver;
use VmEngine\Cms\Observers\CmsPageObserver;
use VmEngine\Cms\Observers\CmsSectionObserver;
use VmEngine\Synapse\Traits\AutoRegistersComponents;

class CmsServiceProvider extends ServiceProvider
{
    use AutoRegistersComponents;

    public function register(): void
    {
        $this->registerComponents();
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'cms');
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'cms');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        if ($this->app->runningInConsole()) {
            $this->commands([
                PatchResourcesCommand::class,
            ]);
        }

        CmsPage::observe(CmsPageObserver::class);
        CmsSection::observe(CmsSectionObserver::class);
        CmsCategory::observe(CmsCategoryObserver::class);
        CmsArticle::observe(CmsArticleObserver::class);
        CmsAlbum::observe(CmsAlbumObserver::class);
    }
}
