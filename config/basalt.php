<?php

/**
 * Project facts the site quotes. Kept in one place so copy cannot drift from
 * what the repositories actually say.
 */
return [

    'github' => 'https://github.com/BasaltProject',

    'repos' => [
        'server' => 'https://github.com/BasaltProject/Server',
        'proxy' => 'https://github.com/BasaltProject/Proxy',
        'wire' => 'https://github.com/BasaltProject/Wire',
        'documentation' => 'https://github.com/BasaltProject/Documentation',
        'website' => 'https://github.com/BasaltProject/Website',
        'deploy' => 'https://github.com/BasaltProject/Deploy',
    ],

    // Pterodactyl eggs, as paths inside the deploy repository's main branch.
    'eggs' => [
        'server' => 'pterodactyl/egg-basalt-server.json',
        'proxy' => 'pterodactyl/egg-basalt-proxy.json',
    ],

    // Container images. Hidden on the site until they are published: set
    // BASALT_DOCKER=true to announce them.
    'docker' => [
        'published' => env('BASALT_DOCKER', false),
        // First release that has an image; earlier ones were never published.
        'since' => '26.3.1',
        'images' => [
            'server' => 'ghcr.io/basaltproject/server',
            'proxy' => 'ghcr.io/basaltproject/proxy',
        ],
    ],

    // Basalt tracks the newest release: `current` is what the core speaks today,
    // and every other client reaches it through translation at the edge.
    'minecraft' => [
        'current' => '26.3',
        'protocol' => '777',
    ],

    // `icon` names a brand in resources/views/components/icon/brand.blade.php.
    'platforms' => [
        ['target' => 'macOS-arm64', 'label' => 'macOS', 'arch' => 'Apple silicon', 'icon' => 'apple'],
        ['target' => 'macOS-x64', 'label' => 'macOS', 'arch' => 'Intel', 'icon' => 'apple'],
        ['target' => 'Linux-x64', 'label' => 'Linux', 'arch' => 'x86_64', 'icon' => 'linux'],
        ['target' => 'Linux-arm64', 'label' => 'Linux', 'arch' => 'aarch64', 'icon' => 'linux'],
        ['target' => 'Windows-x64', 'label' => 'Windows', 'arch' => 'x86_64', 'icon' => 'windows'],
    ],

    // `url` is where every button on the donate page sends people.
    'donate' => [
        'url' => 'https://github.com/sponsors/BasaltProject',
        'currency' => '€',
        'monthly' => [5, 10, 25, 50, 100, 250],
        'once' => [10, 25, 50, 100, 250, 500],
        // One tier per amount, and one hue per tier: greys were indistinguishable
        // on a dark page, so every name is a mineral with a colour of its own.
        'tiers' => [
            [
                'name' => 'Ash',
                'from' => 5,
                'gradient' => ['#d2e2f2', '#6f88a5'],
                'perks' => ['Your name in the supporters list'],
            ],
            [
                'name' => 'Pumice',
                'from' => 10,
                'gradient' => ['#f4e6cd', '#b99a6d'],
                'perks' => ['Your name in the supporters list', 'Linked to wherever you want'],
            ],
            [
                'name' => 'Olivine',
                'from' => 25,
                'gradient' => ['#bce8a0', '#57993c'],
                'perks' => ['Everything in Pumice', 'Your avatar beside your name'],
            ],
            [
                'name' => 'Sulfur',
                'from' => 50,
                'gradient' => ['#ffeb96', '#d8a41c'],
                'perks' => ['Everything in Olivine', 'Your logo in the supporters list'],
            ],
            [
                'name' => 'Obsidian',
                'from' => 100,
                'gradient' => ['#b9a9e0', '#6f6394'],
                'perks' => ['Everything in Sulfur', 'Your logo on this page, above the list'],
            ],
            [
                'name' => 'Magma',
                'from' => 250,
                'gradient' => ['#ffc46b', '#f0662f'],
                'perks' => ['Everything in Obsidian', 'Your logo on the home page', 'A direct line to the maintainers'],
            ],
        ],
    ],

    // Its own repository, pulled in as a submodule so it can be edited alone.
    'docs' => [
        'path' => base_path('documentation'),
        'edit_base' => 'https://github.com/BasaltProject/Documentation/edit/main',
    ],

];
