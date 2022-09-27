<?php

use Dedoc\Scramble\Http\Middleware\RestrictedDocsAccess;

return [
    'middleware' => [
        'web',
        RestrictedDocsAccess::class,
    ],

    'extensions' => [],
    
    'path' => '/api',
    
    'version' => '0.0.1',
];
