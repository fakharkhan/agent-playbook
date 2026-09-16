<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Canonical Host
    |--------------------------------------------------------------------------
    |
    | The primary domain for SEO canonical URLs and legacy redirects.
    |
    */

    'canonical_host' => env('SEO_CANONICAL_HOST', 'agent-playbook.fakhar-khan.com'),

    /*
    |--------------------------------------------------------------------------
    | Legacy Hosts
    |--------------------------------------------------------------------------
    |
    | Hostnames that should 301 redirect to the canonical host.
    |
    */

    'legacy_hosts' => [
        'agent-playbook.on-forge.com',
        'www.agent-playbook.on-forge.com',
        'agent-playbook.fakharkhan.com',
        'www.agent-playbook.fakharkhan.com',
    ],

];
