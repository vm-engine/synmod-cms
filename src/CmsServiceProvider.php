<?php

declare(strict_types=1);

namespace VmEngine\Cms;

use Illuminate\Support\ServiceProvider;
use VmEngine\Cms\Console\Commands\PatchResourcesCommand;
use VmEngine\Cms\Console\Commands\SanitizeContentCommand;
use VmEngine\Cms\Models\CmsAlbum;
use VmEngine\Cms\Models\CmsAlbumImage;
use VmEngine\Cms\Models\CmsArticle;
use VmEngine\Cms\Models\CmsCategory;
use VmEngine\Cms\Models\CmsDownload;
use VmEngine\Cms\Models\CmsMenuItem;
use VmEngine\Cms\Models\CmsPage;
use VmEngine\Cms\Models\CmsSection;
use VmEngine\Cms\Observers\CmsAlbumImageObserver;
use VmEngine\Cms\Observers\CmsAlbumObserver;
use VmEngine\Cms\Observers\CmsArticleObserver;
use VmEngine\Cms\Observers\CmsCategoryObserver;
use VmEngine\Cms\Observers\CmsDownloadObserver;
use VmEngine\Cms\Observers\CmsMenuItemObserver;
use VmEngine\Cms\Observers\CmsPageObserver;
use VmEngine\Cms\Observers\CmsSectionObserver;
use VmEngine\Synapse\Traits\AutoRegistersComponents;

class CmsServiceProvider extends ServiceProvider
{
    use AutoRegistersComponents;

    public function register(): void
    {
        $this->registerComponents();

        $this->mergeConfigFrom(__DIR__.'/../config/cms-purifier.php', 'purifier.settings.cms_rte');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                PatchResourcesCommand::class,
                SanitizeContentCommand::class,
            ]);
        }

        CmsPage::observe(CmsPageObserver::class);
        CmsSection::observe(CmsSectionObserver::class);
        CmsCategory::observe(CmsCategoryObserver::class);
        CmsArticle::observe(CmsArticleObserver::class);
        CmsAlbum::observe(CmsAlbumObserver::class);
        CmsAlbumImage::observe(CmsAlbumImageObserver::class);
        CmsDownload::observe(CmsDownloadObserver::class);
        CmsMenuItem::observe(CmsMenuItemObserver::class);
    }
}
