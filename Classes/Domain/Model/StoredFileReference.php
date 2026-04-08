<?php

declare(strict_types=1);

/*
 * This file is part of TYPO3 CMS-based extension "db_file_storage" by b13.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 */

namespace B13\DbFileStorage\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/**
 * Extbase entity that maps a row of `tx_dbfilestorage_domain_model_file`.
 *
 * Use this on the consumer side whenever you want to relate domain models to
 * stored files via Extbase MM relations or ObjectStorage<StoredFileReference>
 * properties — typical pattern:
 *
 *     // Domain\Model\Model.php
 *     use B13\DbFileStorage\Domain\Model\StoredFileReference;
 *
 *     class Task extends AbstractEntity
 *     {
 *         // @param ObjectStorage<StoredFileReference> $attachments
 *         public function __construct(
 *             protected ObjectStorage $attachments,
 *             // ...
 *         ) {}
 *     }

 * Note:
 *  - StoredFile: full data including bytes; what services hand back.
 *  - StoredFileReference: metadata-only handle; what Extbase relations point at.
 */
class StoredFileReference extends AbstractEntity implements \JsonSerializable
{
    protected string $filename = '';
    protected string $mimeType = '';
    protected int $size = 0;
    protected string $sha1 = '';

    public function getFilename(): string
    {
        return $this->filename;
    }

    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function getSha1(): string
    {
        return $this->sha1;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'uid' => $this->getUid(),
            'filename' => $this->filename,
            'mimeType' => $this->mimeType,
            'size' => $this->size,
            'sha1' => $this->sha1,
        ];
    }
}
