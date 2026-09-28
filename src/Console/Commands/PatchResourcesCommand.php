<?php

declare(strict_types=1);

namespace VmEngine\Cms\Console\Commands;

use Illuminate\Console\Command;

/**
 * @deprecated Use `cms:setup`.
 */
class PatchResourcesCommand extends Command
{
    protected $signature = 'cms:patch-resources';

    /**
     * Deprecated pre-3.x name, kept so existing scripts keep working.
     *
     * @var array<int, string>
     */
    protected $aliases = ['mod-cms:patch-resources'];

    protected $description = '[Deprecated] Alias of cms:setup';

    public function handle(): int
    {
        $this->warn('cms:patch-resources is deprecated — use cms:setup.');

        return $this->call('cms:setup');
    }
}
