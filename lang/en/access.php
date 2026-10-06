<?php

declare(strict_types=1);

return [
    'panel' => 'Access',
    'visibility' => 'Visibility',
    'visibility_public' => 'Public (everyone)',
    'visibility_protected' => 'Protected (logged-in users)',
    'visibility_private' => 'Private (selected users / roles)',
    'visibility_inherit' => 'Inherit from parent',
    'inherited_value' => 'Currently inherits: :value',
    'viewers' => 'Who can view',
    'viewers_help' => 'Only used when the effective visibility is Private. Adds to the parent\'s list.',
    'managers' => 'Who can manage',
    'managers_help' => 'Leave empty to allow everyone with the module permission. Applies to all child content.',
    'principals_placeholder' => 'Search users or roles…',
    'locked' => 'Set by developer',
    'inherited' => 'Inherited',
    'read_only' => 'You cannot change access settings for this item.',
    'principal_user' => 'User: :name',
    'principal_role' => 'Role: :name',
];
