<?php

declare(strict_types=1);

/*
 * This file is part of TYPO3 CMS-based extension "db_file_storage" by b13.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 */

namespace B13\DbFileStorage\Service;

use B13\DbFileStorage\Domain\Model\StoredFile;
use B13\DbFileStorage\Exception\FileNotFoundException;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileInterface;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Resource\MimeTypeDetector;

/**
 * Stores and retrieves files in a single database table.
 *
 * Minimal public API:
 *
 *     $stored = $storage->store($request->getUploadedFiles()['file']);
 *     $file = $storage->get($stored->uid);
 *     $response = $storage->createResponse($stored->uid);
 *     $storage->delete($stored->uid); // soft-delete
 */
final class DatabaseFileStorage
{
    public const TABLE_NAME = 'tx_dbfilestorage_domain_model_file';

    public function __construct(
        private readonly ConnectionPool $connectionPool,
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly StreamFactoryInterface $streamFactory,
    ) {}

    /**
     * Persist an uploaded PSR-7 file.
     */
    public function store(UploadedFileInterface $uploadedFile): StoredFile
    {
        $stream = $uploadedFile->getStream();
        $stream->rewind();
        $contents = $stream->getContents();

        return $this->storeContents(
            $uploadedFile->getClientFilename() ?? 'no-title',
            $contents,
            $uploadedFile->getClientMediaType(),
        );
    }

    /**
     * Persist raw file contents. Useful when the source is not a PSR-7 upload
     */
    public function storeContents(string $filename, string $contents, ?string $mimeType = null): StoredFile
    {
        $mimeType ??= $this->guessMimeType($filename, $contents);
        $size = strlen($contents);
        $sha1 = sha1($contents);
        $now = new \DateTimeImmutable();

        $connection = $this->connectionPool->getConnectionForTable(self::TABLE_NAME);
        $connection->insert(
            self::TABLE_NAME,
            [
                'pid' => 0,
                'tstamp' => $now->getTimestamp(),
                'crdate' => $now->getTimestamp(),
                'filename' => $filename,
                'mime_type' => $mimeType,
                'size' => $size,
                'sha1' => $sha1,
                'content' => $contents,
            ],
        );

        return new StoredFile(
            uid: (int)$connection->lastInsertId(),
            filename: $filename,
            mimeType: $mimeType,
            size: $size,
            sha1: $sha1,
            contents: $contents,
            createdAt: $now,
        );
    }

    /**
     * Find and return a file
     */
    public function get(int $uid): ?StoredFile
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE_NAME);
        $row = $queryBuilder
            ->select('uid', 'filename', 'mime_type', 'size', 'sha1', 'content', 'crdate')
            ->from(self::TABLE_NAME)
            ->where($queryBuilder->expr()->eq(
                'uid',
                $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)
            ))
            ->executeQuery()
            ->fetchAssociative();

        return $this->hydrate($row);
    }

    public function require(int $uid): StoredFile
    {
        $file = $this->get($uid);
        if ($file === null) {
            throw new FileNotFoundException(
                sprintf('No file with uid %d found in database storage.', $uid),
                1_712_000_000
            );
        }
        return $file;
    }

    /**
     * Soft-delete a file by uid. Sets `deleted = 1` (and updates `tstamp`)
     */
    public function delete(int $uid): bool
    {
        $connection = $this->connectionPool->getConnectionForTable(self::TABLE_NAME);
        return $connection->update(
            self::TABLE_NAME,
            [
                'deleted' => 1,
                'tstamp' => time(),
            ],
            [
                'uid' => $uid,
                'deleted' => 0,
            ],
        ) > 0;
    }

    public function createResponse(int $uid, bool $forceDownload = false): ResponseInterface
    {
        $file = $this->require($uid);

        $disposition = $forceDownload ? 'attachment' : 'inline';
        $response = $this->responseFactory
            ->createResponse()
            ->withHeader('Content-Type', $file->mimeType)
            ->withHeader('Content-Length', (string)$file->size)
            ->withHeader('ETag', '"' . $file->sha1 . '"')
            ->withHeader(
                'Content-Disposition',
                sprintf('%s; filename="%s"', $disposition, $this->sanitizeHeaderFilename($file->filename))
            )
            ->withBody($this->streamFactory->createStream($file->contents));

        return $response;
    }

    /**
     * @param array<string, mixed>|false $row
     */
    private function hydrate(array|false $row): ?StoredFile
    {
        if ($row === false) {
            return null;
        }

        $content = $row['content'];
        if (is_resource($content)) {
            $content = stream_get_contents($content);
        }

        return new StoredFile(
            uid: (int)$row['uid'],
            filename: (string)$row['filename'],
            mimeType: (string)$row['mime_type'],
            size: (int)$row['size'],
            sha1: (string)$row['sha1'],
            contents: (string)$content,
            createdAt: (new \DateTimeImmutable())->setTimestamp((int)$row['crdate']),
        );
    }

    private function guessMimeType(string $filename, string $contents): string
    {
        $detector = new \finfo(FILEINFO_MIME_TYPE);
        $detected = $detector->buffer($contents);
        if (is_string($detected) && $detected !== '') {
            return $detected;
        }

        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $byExtension = (new MimeTypeDetector())->getMimeTypesForFileExtension($extension);
        return $byExtension[0] ?? 'application/octet-stream';
    }

    private function sanitizeHeaderFilename(string $filename): string
    {
        return str_replace(['"', "\r", "\n"], '', $filename);
    }
}
