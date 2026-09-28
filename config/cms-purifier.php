<?php

declare(strict_types=1);

// Merged into config('purifier.settings.cms_rte') by CmsServiceProvider.
// Strict allowlist for CMS rich-text content: no event handlers, no
// <script>/<iframe>, http/https/mailto URIs only. The only CSS allowed is
// text-align on blocks. Embedded media (<img>, <video>) must be local —
// relative or on the app's own host — i.e. inserted from the File Manager.
// <video>/<source> come from mews/purifier's html5 custom_definition.
return [
    'HTML.Doctype' => 'HTML 4.01 Transitional',
    'HTML.Allowed' => 'p[style],br,strong,em,b,i,u,ul,ol,li,a[href|title],blockquote,'
        .'h1[style],h2[style],h3[style],h4[style],h5[style],h6[style],'
        .'img[src|alt|width|height],span,div[style],'
        .'video[src|controls|width|height|poster],source[src|type]',
    'CSS.AllowedProperties' => 'text-align',
    'URI.AllowedSchemes' => ['http' => true, 'https' => true, 'mailto' => true],
    'URI.DisableExternalResources' => true,
    'URI.Host' => parse_url((string) config('app.url'), PHP_URL_HOST) ?: null,
    'AutoFormat.RemoveEmpty' => true,
    // Keep src-bearing <video>/<source> (they have no text content); the rest
    // mirrors HTMLPurifier's default predicate.
    'AutoFormat.RemoveEmpty.Predicate' => [
        'colgroup' => [],
        'th' => [],
        'td' => [],
        'iframe' => ['src'],
        'video' => ['src'],
        'source' => ['src'],
    ],
];
