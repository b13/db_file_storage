<?php

declare(strict_types=1);

/*
 * This file is part of TYPO3 CMS-based extension "db_file_storage" by b13.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 */

namespace B13\DbFileStorage\Domain\Repository;

use B13\DbFileStorage\Domain\Model\StoredFileReference;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * @extends Repository<StoredFileReference>
 */
class StoredFileReferenceRepository extends Repository {}
