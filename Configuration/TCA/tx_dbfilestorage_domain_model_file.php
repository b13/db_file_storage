<?php

declare(strict_types=1);

/*
 * Minimal TCA stub for the db_file_storage file table.
 *
 * Originally db_file_storage shipped without TCA — the byte-handling service
 * was the entire surface area. This stub was added so consumers can use the
 * sibling Extbase entity (B13\DbFileStorage\Domain\Model\StoredFileReference)
 * in MM relations and ObjectStorage<StoredFileReference> properties without
 * each one having to redeclare TCA for a table they do not own.
 *
 * The stub is intentionally minimal:
 *   - Provides metadata so Extbase's DataMapper can hydrate StoredFileReference.
 *   - Gives the `type => 'group'` record browser in consumer TCA a readable
 *     label (filename) instead of bare UIDs.
 *   - Stays hidden and read-only — bytes are written through
 *     B13\DbFileStorage\Service\DatabaseFileStorage, never via FormEngine.
 *   - Deliberately omits the `content` LONGBLOB so Extbase hydration never
 *     pulls file bytes into memory.
 */
return [
    'ctrl' => [
        'title' => 'db_file_storage file',
        'label' => 'filename',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'delete' => 'deleted',
        'hideTable' => true,
        'readOnly' => true,
        'default_sortby' => 'crdate DESC',
    ],
    'columns' => [
        'filename' => [
            'label' => 'Filename',
            'config' => [
                'type' => 'input',
                'readOnly' => true,
            ],
        ],
        'mime_type' => [
            'label' => 'MIME type',
            'config' => [
                'type' => 'input',
                'readOnly' => true,
            ],
        ],
        'size' => [
            'label' => 'Size (bytes)',
            'config' => [
                'type' => 'number',
                'readOnly' => true,
            ],
        ],
        'sha1' => [
            'label' => 'SHA-1',
            'config' => [
                'type' => 'input',
                'readOnly' => true,
            ],
        ],
    ],
    'types' => [
        '0' => [
            'showitem' => 'filename, mime_type, size, sha1',
        ],
    ],
];
