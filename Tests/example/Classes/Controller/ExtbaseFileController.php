<?php

declare(strict_types=1);

namespace DbStore\Example\Controller;

use B13\DbFileStorage\Domain\Repository\StoredFileReferenceRepository;
use B13\DbFileStorage\Service\DatabaseFileStorage;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

final class ExtbaseFileController extends ActionController
{
    public function __construct(
        private readonly DatabaseFileStorage $storage,
        private readonly StoredFileReferenceRepository $repository,
        private readonly ModuleTemplateFactory $moduleTemplateFactory,
    ) {}

    public function initializeAction(): void
    {
        $querySettings = $this->repository->createQuery()->getQuerySettings();
        $querySettings->setRespectStoragePage(false);
        $this->repository->setDefaultQuerySettings($querySettings);
    }

    public function listAction(): ResponseInterface
    {
        $view = $this->moduleTemplateFactory->create($this->request);
        $view->assign('files', $this->repository->findAll());
        $view->setFlashMessageQueue($this->getFlashMessageQueue());
        return $view->renderResponse('ExtbaseFile/List');
    }

    public function uploadAction(): ResponseInterface
    {
        $uploadedFiles = $this->request->getUploadedFiles();
        $uploadedFile = $uploadedFiles['file'] ?? null;
        if ($uploadedFile === null) {
            $this->addFlashMessage('No file uploaded.', 'Error', ContextualFeedbackSeverity::ERROR);
            return $this->redirect('list');
        }

        $stored = $this->storage->store($uploadedFile);
        $this->addFlashMessage(
            sprintf('File "%s" uploaded (%d bytes).', $stored->filename, $stored->size),
            'Upload successful',
        );
        return $this->redirect('list');
    }

    public function downloadAction(int $file): ResponseInterface
    {
        return $this->storage->createResponse($file, forceDownload: true);
    }

    public function deleteAction(int $file): ResponseInterface
    {
        $this->storage->delete($file);
        $this->addFlashMessage('File deleted.');
        return $this->redirect('list');
    }
}
