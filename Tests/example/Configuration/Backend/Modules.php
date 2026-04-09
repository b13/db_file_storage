<?php

declare(strict_types=1);

use DbStore\Example\Controller\ExtbaseFileController;
use DbStore\Example\Controller\NativeFileController;

return [
    'example_extbase' => [
        'parent' => 'file',
        'access' => 'user',
        'path' => '/module/file/example-extbase',
        'iconIdentifier' => 'module-filelist',
        'labels' => 'LLL:EXT:example/Resources/Private/Language/locallang_mod_extbase.xlf',
        'extensionName' => 'Example',
        'inheritNavigationComponentFromMainModule' => false,
        'navigationComponent' => false,
        'controllerActions' => [
            ExtbaseFileController::class => ['list', 'upload', 'download', 'delete'],
        ],
    ],
    'example_native' => [
        'parent' => 'file',
        'access' => 'user',
        'path' => '/module/file/example-native',
        'iconIdentifier' => 'module-filelist',
        'labels' => 'LLL:EXT:example/Resources/Private/Language/locallang_mod_native.xlf',
        'navigationComponent' => false,
        'inheritNavigationComponentFromMainModule' => false,
        'routes' => [
            '_default' => [
                'target' => NativeFileController::class . '::listAction',
            ],
            'upload' => [
                'target' => NativeFileController::class . '::uploadAction',
                'methods' => ['POST'],
            ],
            'download' => [
                'target' => NativeFileController::class . '::downloadAction',
            ],
            'delete' => [
                'target' => NativeFileController::class . '::deleteAction',
                'methods' => ['POST'],
            ],
        ],
    ],
];
