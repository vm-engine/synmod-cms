<?php

declare(strict_types=1);

namespace VmEngine\Cms\Observers;

use VmEngine\Cms\Models\CmsDownloadFile;
use VmEngine\Cms\Services\CmsSearchIndexer;

class CmsDownloadFileObserver
{
    public function __construct(private readonly CmsSearchIndexer $search) {}

    public function saved(CmsDownloadFile $file): void
    {
        $this->search->indexItem($file);
    }

    public function deleted(CmsDownloadFile $file): void
    {
        $this->search->forgetItem($file);
    }
}
