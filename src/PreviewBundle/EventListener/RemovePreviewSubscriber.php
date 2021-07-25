<?php

namespace Rabble\PreviewBundle\EventListener;

use Rabble\ContentBundle\Persistence\Event\AfterSaveEvent;
use Rabble\ContentBundle\Persistence\Manager\ContentManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class RemovePreviewSubscriber implements EventSubscriberInterface
{
    private ContentManagerInterface $previewContentManager;

    public function __construct(ContentManagerInterface $previewContentManager)
    {
        $this->previewContentManager = $previewContentManager;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            AfterSaveEvent::class => 'afterSave',
        ];
    }

    public function afterSave(AfterSaveEvent $event)
    {
        foreach ($event->getRemoved() as $document) {
            $previewDocument = $this->previewContentManager->find('/preview/'.$document->getUuid());
            if (null !== $previewDocument) {
                $this->previewContentManager->remove($previewDocument);
            }
        }
        $this->previewContentManager->flush();
    }
}