<?php

declare(strict_types=1);

use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;
use VmEngine\Cms\Enums\RteDriver;
use VmEngine\Cms\Support\AnalyticsCache;
use VmEngine\Cms\Support\AnalyticsSettings;
use VmEngine\Synapse\Services\Helper\Breadcrumbs;
use VmEngine\SynAuth\Facades\SynAuth;

new class extends Component
{
    public string $editorDriver = '';

    public string $measurementId = '';

    public string $gscVerification = '';

    public bool $requireConsent = true;

    public string $propertyId = '';

    public string $gscSite = '';

    /** Write-only: never filled from storage, cleared after save. */
    public string $serviceAccountJson = '';

    public function mount(): void
    {
        $this->editorDriver = RteDriver::current()->value;

        $analytics = AnalyticsSettings::all();
        $this->measurementId = $analytics['measurement_id'];
        $this->gscVerification = $analytics['gsc_verification'];
        $this->requireConsent = $analytics['require_consent'];
        $this->propertyId = $analytics['property_id'];
        $this->gscSite = $analytics['gsc_site'];

        synav()->setActiveMenu('cms.settings');
    }

    public function title(): string
    {
        return __('cms::labels.settings');
    }

    public function save(): void
    {
        if (! $this->canUpdate()) {
            return;
        }

        $this->validate([
            'editorDriver' => ['required', Rule::enum(RteDriver::class)],
            'measurementId' => ['nullable', 'regex:/^G-[A-Z0-9]+$/'],
            'gscVerification' => ['nullable', 'regex:/^[A-Za-z0-9_-]+$/'],
            'requireConsent' => ['boolean'],
            'propertyId' => ['nullable', 'regex:/^\d+$/'],
            'gscSite' => ['nullable', 'regex:#^(https?://\S+/|sc-domain:\S+)$#'],
            'serviceAccountJson' => ['nullable', function (string $attribute, mixed $value, Closure $fail): void {
                if (! AnalyticsSettings::isServiceAccount(json_decode((string) $value, true))) {
                    $fail(__('cms::labels.analytics_key_invalid'));
                }
            }],
        ]);

        dbconf(['cms.editor.driver' => $this->editorDriver]);

        AnalyticsSettings::save([
            'measurement_id' => $this->measurementId,
            'gsc_verification' => $this->gscVerification,
            'require_consent' => $this->requireConsent,
            'property_id' => $this->propertyId,
            'gsc_site' => $this->gscSite,
        ]);

        $keyReplaced = $this->serviceAccountJson !== '';
        if ($keyReplaced) {
            AnalyticsSettings::saveServiceAccount($this->serviceAccountJson);
            $this->serviceAccountJson = '';
            unset($this->serviceAccountEmail);
        }

        AnalyticsCache::flush();

        $this->logActivity('updated CMS settings'.($keyReplaced ? ' and replaced the Google service account key' : ''));

        $this->dispatch('notify', variant: 'success', title: 'Success', message: __('cms::labels.settings_saved'));
    }

    public function removeServiceAccount(): void
    {
        if (! $this->canUpdate()) {
            return;
        }

        AnalyticsSettings::saveServiceAccount(null);
        AnalyticsCache::flush();
        unset($this->serviceAccountEmail);

        $this->logActivity('removed the Google service account key');

        $this->dispatch('notify', variant: 'success', title: 'Success', message: __('cms::labels.analytics_key_removed'));
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    #[Computed]
    public function editorOptions(): array
    {
        return RteDriver::options();
    }

    #[Computed]
    public function serviceAccountEmail(): ?string
    {
        return AnalyticsSettings::serviceAccount()['client_email'] ?? null;
    }

    #[Computed]
    public function breadcrumbs(): Breadcrumbs
    {
        return Breadcrumbs::make(label: __('cms::labels.settings'), icon: 'ph ph-gear');
    }

    public function render()
    {
        return $this->view()->title(page_title($this->title()));
    }

    private function canUpdate(): bool
    {
        $user = Auth::user();

        if ($user instanceof Authorizable && $user->can('cms.settings.update')) {
            return true;
        }

        $this->dispatch('notify', variant: 'danger', title: 'Error', message: __('cms::labels.no_permission'));

        return false;
    }

    private function logActivity(string $action): void
    {
        SynAuth::logActivity(
            (int) Auth::id(),
            'cms.settings.update',
            'User '.Auth::user()->name.' '.$action,
            'settings',
            'cms',
        );
    }
};
