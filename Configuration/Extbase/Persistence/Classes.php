<?php

declare(strict_types=1);

/*
 * Extbase persistence mapping for db_file_storage.
 *
 * TYPO3 merges Configuration/Extbase/Persistence/Classes.php from every
 * loaded extension, so this mapping is automatically picked up by every
 * consumer — no per-consumer wiring required. Once db_file_storage is
 * installed, StoredFileReference can be used directly in any other extension's
 * Extbase models.
 */
return [
    \B13\DbFileStorage\Domain\Model\StoredFileReference::class => [
        'tableName' => 'tx_dbfilestorage_domain_model_file',
        'properties' => [
            'mimeType' => [
                'fieldName' => 'mime_type',
            ],
        ],
    ],
];
