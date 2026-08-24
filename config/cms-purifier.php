<?php

declare(strict_types=1);

// Merged into config('purifier.settings.cms_rte') by CmsServiceProvider.
// Strict allowlist for CMS rich-text content: no inline styles, no event
// handlers, no <script>/<iframe>, http/https/mailto URIs only.
return [
    'HTML.Doctype' => 'HTML 4.01 Transitional',
    'HTML.Allowed' => 'p,br,strong,em,b,i,u,ul,ol,li,a[href|title],blockquote,'
        .'h1,h2,h3,h4,h5,h6,img[src|alt|width|height],span,div',
    'CSS.AllowedProperties' => '',
    'URI.AllowedSchemes' => ['http' => true, 'https' => true, 'mailto' => true],
    'AutoFormat.RemoveEmpty' => true,
];
