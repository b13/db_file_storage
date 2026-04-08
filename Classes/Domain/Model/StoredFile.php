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

final class StoredFile
{
    public function __construct(
        public readonly int $uid,
        public readonly string $filename,
        public readonly string $mimeType,
        public readonly int $size,
        public readonly string $sha1,
        public readonly string $contents,
        public readonly \DateTimeImmutable $createdAt,
    ) {}
}
