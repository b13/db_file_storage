<?php

declare(strict_types=1);

namespace DbStore\Example\Controller;

use B13\DbFileStorage\Service\DatabaseFileStorage;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;

#[AsController]
final class NativeFileController
{
    public function __construct(
        private readonly DatabaseFileStorage $storage,
        private readonly ModuleTemplateFactory $moduleTemplateFactory,
        private readonly ConnectionPool $connectionPool,
        private readonly UriBuilder $uriBuilder,
        private readonly FlashMessageService $flashMessageService,
    ) {}

    public function listAction(ServerRequestInterface $request): ResponseInterface
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(DatabaseFileStorage::TABLE_NAME);
        $rows = $queryBuilder
            ->select('uid', 'filename', 'mime_type', 'size', 'crdate')
            ->from(DatabaseFileStorage::TABLE_NAME)
            ->orderBy('uid', 'DESC')
            ->executeQuery()
            ->fetchAllAssociative();

        $view = $this->moduleTemplateFactory->create($request);
        $view->assign('files', $rows);
        $view->assign('uploadUri', (string)$this->uriBuilder->buildUriFromRoute('example_native.upload'));
        $view->assign('deleteUri', (string)$this->uriBuilder->buildUriFromRoute('example_native.delete'));

        return $view->renderResponse('NativeFile/List');
    }

    public function uploadAction(ServerRequestInterface $request): ResponseInterface
    {
        $uploadedFiles = $request->getUploadedFiles();
        $uploadedFile = $uploadedFiles['file'] ?? null;

        if ($uploadedFile !== null) {
            $stored = $this->storage->store($uploadedFile);
            $this->enqueueFlashMessage(
                sprintf('File "%s" uploaded (%d bytes).', $stored->filename, $stored->size),
                'Upload successful',
            );
        } else {
            $this->enqueueFlashMessage('No file uploaded.', 'Error', ContextualFeedbackSeverity::ERROR);
        }

        return new RedirectResponse($this->uriBuilder->buildUriFromRoute('example_native'));
    }

    public function downloadAction(ServerRequestInterface $request): ResponseInterface
    {
        $fileUid = (int)($request->getQueryParams()['file'] ?? 0);
        return $this->storage->createResponse($fileUid, forceDownload: true);
    }

    public function deleteAction(ServerRequestInterface $request): ResponseInterface
    {
        $fileUid = (int)($request->getParsedBody()['file'] ?? 0);
        $this->storage->delete($fileUid);
        $this->enqueueFlashMessage('File deleted.');

        return new RedirectResponse($this->uriBuilder->buildUriFromRoute('example_native'));
    }

    private function enqueueFlashMessage(
        string $body,
        string $title = '',
        ContextualFeedbackSeverity $severity = ContextualFeedbackSeverity::OK,
    ): void {
        $this->flashMessageService
            ->getMessageQueueByIdentifier()
            ->enqueue(new FlashMessage($body, $title, $severity, true));
    }
}
