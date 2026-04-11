<?php

declare(strict_types=1);

namespace VmEngine\Cms\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use VmEngine\SynAuth\Facades\SynAuth;

class CmsActivityLogger
{
    /**
     * Log a create action on a CMS entity.
     *
     * @param  array<string, mixed>  $data
     */
    public function logCreate(string $entity, Model $model, array $data = []): void
    {
        $this->write('create', $entity, $model, 'User '.$this->userName().' created new '.$entity.'. Data: '.json_encode($data));
    }

    /**
     * Log an update action, including only changed fields in the message.
     *
     * @param  array<string, mixed>  $oldData
     */
    public function logUpdate(string $entity, Model $model, array $oldData): void
    {
        $changes = $this->diffChanges($oldData, $model->fresh()?->toArray() ?? $model->toArray());
        $this->write('update', $entity, $model, 'User '.$this->userName().' updated '.$entity.' data. Changes: '.json_encode($changes));
    }

    /**
     * Log a delete action on a CMS entity.
     */
    public function logDelete(string $entity, Model $model): void
    {
        $this->write('delete', $entity, $model, 'User '.$this->userName().' deleted '.$entity.'. Data: '.json_encode($model->toArray()));
    }

    private function write(string $action, string $entity, Model $model, string $message): void
    {
        $id = (string) ($model->getKey() ?? '0');

        SynAuth::logActivity(
            Auth::id(),
            'cms.'.$entity.'.'.$id.'.'.$action,
            $message,
            $entity.'.'.$id,
            'cms',
        );
    }

    private function userName(): string
    {
        $user = Auth::user();

        return $user !== null ? (string) $user->name : 'System';
    }

    /**
     * Compute the set of changed fields between old and new data.
     *
     * @param  array<string, mixed>  $old
     * @param  array<string, mixed>  $new
     * @return array<string, array{old: mixed, new: mixed}>
     */
    private function diffChanges(array $old, array $new): array
    {
        $ignore = ['updated_at', 'created_at'];
        $changes = [];

        foreach ($new as $key => $value) {
            if (in_array($key, $ignore, true)) {
                continue;
            }
            $oldValue = $old[$key] ?? null;
            if ($oldValue != $value) {
                $changes[$key] = ['old' => $oldValue, 'new' => $value];
            }
        }

        return $changes;
    }
}
