<?php

declare(strict_types=1);

use Livewire\Attributes\Modelable;
use Livewire\Component;

new class extends Component
{
    #[Modelable]
    public array $value = [];

    public bool $allowAdd = false;

    public bool $canManageSchema = false;

    public function mount(bool $allowAdd = false): void
    {
        $this->canManageSchema = can_access('cms.settings.update');
        $this->allowAdd = $allowAdd;
    }

    public function updatedAllowAdd(): void
    {
        $this->dispatch('custom-var-allow-add-changed', value: $this->allowAdd);
    }

    public function addVar(): void
    {
        if (! $this->canManageSchema && ! $this->allowAdd) {
            return;
        }

        $this->value[] = [
            'key' => '',
            'label' => '',
            'value' => '',
            'editable' => true,
        ];
    }

    public function removeVar(int $index): void
    {
        if (! $this->canManageSchema) {
            return;
        }

        array_splice($this->value, $index, 1);
        $this->value = array_values($this->value);
    }

    public function render()
    {
        return $this->view();
    }
};
