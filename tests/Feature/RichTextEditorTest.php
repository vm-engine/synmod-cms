<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use VmEngine\Cms\Enums\RteDriver;
use VmEngine\Cms\View\Components\Rte;
use VmEngine\Cms\View\Components\Tinymce;
use VmEngine\Synapse\Rte\RteSetup;

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

    it('renders the Synapse editor with the file-manager extension by default', function () {
        config(['synapps.apps.rte.enabled' => true]);

        $this->blade('<x-cms::rte name="form.body" value="<p>Hello</p>" />')
            ->assertSee('x-data="synapseRte(', false)
            ->assertSee('&quot;field&quot;:&quot;form.body&quot;', false)
            ->assertSee('&quot;extension&quot;:&quot;cms-fm&quot;', false)
            ->assertSee('&quot;pickerKey&quot;:&quot;jodit-form.body&quot;', false)
            ->assertSee('cms-jodit-fm-form-body', false)
            ->assertSee('&lt;p&gt;Hello&lt;/p&gt;', false)
            ->assertDontSee('underline', false)
            ->assertDontSee('cmsTinymce', false);
    });

    it('shows the disabled notice above a plain textarea when the Synapse editor is off', function () {
        config(['synapps.apps.rte.enabled' => false]);

        $this->blade('<x-cms::rte name="form.body" value="<p>Hello</p>" />')
            ->assertSee(RteSetup::DISABLED_MESSAGE)
            ->assertSee('wire:model="form.body"', false)
            ->assertSee('&lt;p&gt;Hello&lt;/p&gt;', false)
            ->assertDontSee('synapseRte(', false);
    });

    it('passes the video button label to the Jodit editor', function () {
        config(['synapps.apps.rte.enabled' => true]);

        $this->blade('<x-cms::rte name="form.body" value="" />')
            ->assertSee(e(json_encode(__('cms::labels.rte_insert_video'))), false);
    });

    it('renders TinyMCE when configured', function () {
        dbconf(['cms.editor.driver' => 'tinymce']);

        $this->blade('<x-cms::rte name="form.body" value="" />')
            ->assertSee('cmsTinymce(', false)
            ->assertDontSee('synapseRte(', false);
    });
});

it('uses the TinyMCE 7 blocks dropdown in the default toolbar', function () {
    $toolbar = (new Tinymce('form.body'))->toolbar;

    expect($toolbar)->toContain('blocks')->not->toContain('formatselect');
});
