<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use VmEngine\Cms\Enums\RteDriver;
use VmEngine\Cms\View\Components\Rte;
use VmEngine\Cms\View\Components\Tinymce;

uses(RefreshDatabase::class);

describe('RteDriver', function () {
    it('defaults to Jodit when nothing is stored', function () {
        expect(RteDriver::current())->toBe(RteDriver::Jodit);
    });

    it('returns the stored driver', function () {
        dbconf(['cms.editor.driver' => 'tinymce']);

        expect(RteDriver::current())->toBe(RteDriver::Tinymce);
    });

    it('falls back to Jodit for an unknown stored value', function () {
        dbconf(['cms.editor.driver' => 'ckeditor']);

        expect(RteDriver::current())->toBe(RteDriver::Jodit);
    });

    it('exposes select options', function () {
        expect(RteDriver::options())->toBe([
            ['value' => 'jodit', 'label' => 'Jodit'],
            ['value' => 'tinymce', 'label' => 'TinyMCE'],
        ]);
    });
});

describe('<x-cms::rte>', function () {
    it('resolves the configured driver', function () {
        expect((new Rte('form.body'))->driver)->toBe('jodit');

        dbconf(['cms.editor.driver' => 'tinymce']);

        expect((new Rte('form.body'))->driver)->toBe('tinymce');
    });

    it('renders the Jodit editor by default', function () {
        $this->blade('<x-cms::rte name="form.body" value="<p>Hello</p>" />')
            ->assertSee('cmsJodit(', false)
            ->assertSee('&lt;p&gt;Hello&lt;/p&gt;', false)
            ->assertDontSee('cmsTinymce', false);
    });

    it('passes the video button label to the Jodit editor', function () {
        $this->blade('<x-cms::rte name="form.body" value="" />')
            ->assertSee(e(json_encode(__('cms::labels.rte_insert_video'))), false);
    });

    it('renders TinyMCE when configured', function () {
        dbconf(['cms.editor.driver' => 'tinymce']);

        $this->blade('<x-cms::rte name="form.body" value="" />')
            ->assertSee('cmsTinymce(', false)
            ->assertDontSee('cmsJodit', false);
    });
});

it('uses the TinyMCE 7 blocks dropdown in the default toolbar', function () {
    $toolbar = (new Tinymce('form.body'))->toolbar;

    expect($toolbar)->toContain('blocks')->not->toContain('formatselect');
});
