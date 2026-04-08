<?php

declare(strict_types=1);

/*
 * This file is part of TYPO3 CMS-based extension "db_file_storage" by b13.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 */

namespace B13\DbFileStorage\Tests\Functional\Service;

use B13\DbFileStorage\Domain\Model\StoredFile;
use B13\DbFileStorage\Exception\FileNotFoundException;
use B13\DbFileStorage\Service\DatabaseFileStorage;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\UploadedFileInterface;
use TYPO3\CMS\Core\Http\UploadedFile;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class DatabaseFileStorageTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'b13/db-file-storage',
    ];

    private DatabaseFileStorage $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = $this->get(DatabaseFileStorage::class);
    }

    #[Test]
    public function storeContentsPersistsFileAndMakesItRetrievable(): void
    {
        $stored = $this->subject->storeContents('hello.txt', 'Hello, world!', 'text/plain');

        self::assertGreaterThan(0, $stored->uid);
        self::assertSame('hello.txt', $stored->filename);
        self::assertSame('text/plain', $stored->mimeType);
        self::assertSame(13, $stored->size);
        self::assertSame(sha1('Hello, world!'), $stored->sha1);

        $fetched = $this->subject->get($stored->uid);
        self::assertInstanceOf(StoredFile::class, $fetched);
        self::assertSame($stored->uid, $fetched->uid);
        self::assertSame('Hello, world!', $fetched->contents);
        self::assertSame($stored->sha1, $fetched->sha1);
    }

    #[Test]
    public function storePersistsPsr7UploadedFile(): void
    {
        $upload = $this->createUploadedFile('%PDF-1.4 binary', 'report.pdf', 'application/pdf');

        $stored = $this->subject->store($upload);

        self::assertGreaterThan(0, $stored->uid);
        self::assertSame('report.pdf', $stored->filename);
        self::assertSame('application/pdf', $stored->mimeType);
        self::assertSame('%PDF-1.4 binary', $this->subject->require($stored->uid)->contents);
    }

    #[Test]
    public function binaryRoundTripPreservesBytes(): void
    {
        $bytes = random_bytes(4096);
        $stored = $this->subject->storeContents('payload.bin', $bytes, 'application/octet-stream');

        $fetched = $this->subject->require($stored->uid);

        self::assertSame(4096, $fetched->size);
        self::assertSame(sha1($bytes), $fetched->sha1);
        self::assertTrue(hash_equals($bytes, $fetched->contents));
    }

    #[Test]
    public function getReturnsNullForUnknownUid(): void
    {
        self::assertNull($this->subject->get(\PHP_INT_MAX));
    }

    #[Test]
    public function requireThrowsForUnknownUid(): void
    {
        $this->expectException(FileNotFoundException::class);
        $this->subject->require(\PHP_INT_MAX);
    }

    #[Test]
    public function deleteRemovesFile(): void
    {
        $stored = $this->subject->storeContents('temp.txt', 'bye');

        self::assertTrue($this->subject->delete($stored->uid));
        self::assertNull($this->subject->get($stored->uid));
        self::assertFalse($this->subject->delete($stored->uid));
    }

    #[Test]
    public function createResponseReturnsFileWithExpectedHeaders(): void
    {
        $stored = $this->subject->storeContents('image.png', "\x89PNG\r\n\x1a\n", 'image/png');

        $response = $this->subject->createResponse($stored->uid);

        self::assertSame('image/png', $response->getHeaderLine('Content-Type'));
        self::assertSame((string)$stored->size, $response->getHeaderLine('Content-Length'));
        self::assertStringContainsString('inline;', $response->getHeaderLine('Content-Disposition'));
        self::assertStringContainsString('image.png', $response->getHeaderLine('Content-Disposition'));
        self::assertSame("\x89PNG\r\n\x1a\n", (string)$response->getBody());
    }

    #[Test]
    public function createResponseCanForceDownload(): void
    {
        $stored = $this->subject->storeContents('doc.txt', 'content');

        $response = $this->subject->createResponse($stored->uid, forceDownload: true);

        self::assertStringStartsWith('attachment;', $response->getHeaderLine('Content-Disposition'));
    }

    private function createUploadedFile(string $contents, string $filename, string $mediaType): UploadedFileInterface
    {
        $tmp = tempnam(sys_get_temp_dir(), 'dbfs');
        self::assertIsString($tmp);
        file_put_contents($tmp, $contents);

        return new UploadedFile($tmp, strlen($contents), \UPLOAD_ERR_OK, $filename, $mediaType);
    }
}
