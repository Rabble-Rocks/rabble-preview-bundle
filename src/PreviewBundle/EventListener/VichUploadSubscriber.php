<?php

namespace Rabble\PreviewBundle\EventListener;

use PHPCR\Util\UUIDHelper;
use Rabble\ContentBundle\ContentBlock\ContentBlockManagerInterface;
use Rabble\ContentBundle\DocumentFieldsProvider\DocumentFieldsProviderInterface;
use Rabble\ContentBundle\Event\ContentImageEvent;
use Rabble\ContentBundle\FieldType\ContentBlockType;
use Rabble\ContentBundle\Persistence\Document\AbstractPersistenceDocument;
use Rabble\ContentBundle\Persistence\Manager\ContentManagerInterface;
use Rabble\FieldTypeBundle\FieldType\AbstractFieldType;
use Rabble\FieldTypeBundle\FieldType\FieldContainerInterface;
use Rabble\FieldTypeBundle\FieldType\ImageType;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Vich\UploaderBundle\Mapping\PropertyMapping;

class VichUploadSubscriber implements EventSubscriberInterface
{
    private DocumentFieldsProviderInterface $fieldsProvider;
    private ContentManagerInterface $contentManager;
    private ContentBlockManagerInterface $contentBlockManager;

    public function __construct(
        DocumentFieldsProviderInterface $fieldsProvider,
        ContentManagerInterface $contentManager,
        ContentBlockManagerInterface $contentBlockManager
    ) {
        $this->fieldsProvider = $fieldsProvider;
        $this->contentManager = $contentManager;
        $this->contentBlockManager = $contentBlockManager;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ContentImageEvent::class => 'onPreRemove',
        ];
    }

    public function onPreRemove(ContentImageEvent $event)
    {
        $object = $event->getObject();
        $mapping = $event->getMapping();
        $document = $event->getDocument();
        if (0 !== strpos($document->getPath(), '/preview/') || !UUIDHelper::isUUID($document->getNodeName())) {
            return;
        }
        $content = $this->contentManager->find($document->getNodeName());
        if (null === $content) {
            return;
        }
        if ($this->findImage($mapping->getFileName($object), $mapping, $content)) {
            $event->cancel();
        }
    }

    private function findImage(string $filename, PropertyMapping $mapping, AbstractPersistenceDocument $document): bool
    {
        $fields = $this->fieldsProvider->getFields($document);
        $properties = $document->getProperties();
        foreach ($document->getOwnProperties() as $property) {
            $getter = 'get'.ucfirst($property);
            $properties[$property] = $document->{$getter}();
        }

        foreach ($fields as $field) {
            if ($field instanceof AbstractFieldType && isset($properties[$field->getName()])) {
                if ($this->findImageForField($field, $mapping, $filename, $properties[$field->getName()])) {
                    return true;
                }
            }
        }

        return false;
    }

    private function findImageForField(AbstractFieldType $field, PropertyMapping $mapping, string $filename, $propertyValue): bool
    {
        if ($field instanceof ImageType) {
            return $propertyValue === $filename && $field->getOption('mapping') === $mapping->getMappingName();
        }
        if ($field instanceof FieldContainerInterface) {
            /** @var AbstractFieldType[] $fields */
            $fields = $field->getOption($field->getFieldsOption());
            foreach ($propertyValue as $item) {
                foreach ($fields as $subField) {
                    if (isset($item[$subField->getName()]) && $this->findImageForField($subField, $mapping, $filename, $item[$subField->getName()])) {
                        return true;
                    }
                }
            }
        }
        if ($field instanceof ContentBlockType) {
            foreach ($propertyValue as $item) {
                $blockType = $item['rabble:content_block'] ?? null;
                if (null === $blockType || !$this->contentBlockManager->has($blockType)) {
                    continue;
                }
                $block = $this->contentBlockManager->get($blockType);
                /** @var AbstractFieldType $subField */
                foreach ($block->getFields() as $subField) {
                    if (isset($item[$subField->getName()]) && $this->findImageForField($subField, $mapping, $filename, $item[$subField->getName()])) {
                        return true;
                    }
                }
            }
        }

        return false;
    }
}
