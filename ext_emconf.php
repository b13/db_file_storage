<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Database File Storage',
    'description' => 'Store uploaded files directly in the TYPO3 database via a simple, injectable service.',
    'category' => 'services',
    'author' => 'b13 GmbH',
    'author_email' => 'typo3@b13.com',
    'author_company' => 'b13 GmbH',
    'state' => 'stable',
    'version' => '0.1.0',
    'constraints' => [
        'depends' => [
            'typo3' => '13.4.0-14.99.99',
            'php' => '8.2.0-8.5.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
