<?php

namespace Rabble\PreviewBundle\Controller;

use PHPCR\Util\PathHelper;
use PHPCR\Util\UUIDHelper;
use Rabble\AdminBundle\EventListener\RouterContextSubscriber;
use Rabble\ContentBundle\Content\Structure\StructureBuilder;
use Rabble\ContentBundle\ContentType\ContentTypeManagerInterface;
use Rabble\ContentBundle\Form\ContentFormType;
use Rabble\ContentBundle\Persistence\Document\AbstractPersistenceDocument;
use Rabble\ContentBundle\Persistence\Document\ContentDocument;
use Rabble\ContentBundle\Persistence\Manager\ContentManagerInterface;
use Rabble\WebsiteBundle\Controller\DefaultController;
use Rabble\WebsiteBundle\Routing\WebsiteRouter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\PropertyAccess\Exception\InvalidArgumentException;

class PreviewController extends AbstractController
{
    private const NEW_NODE_PATH = '/preview-new';
    private const NODE_PATH = '/preview';

    private ContentManagerInterface $contentManager;
    private ContentManagerInterface $previewContentManager;
    private ContentTypeManagerInterface $contentTypeManager;
    private StructureBuilder $structureBuilder;

    public function __construct(
        ContentManagerInterface $contentManager,
        ContentManagerInterface $previewContentManager,
        ContentTypeManagerInterface $contentTypeManager,
        StructureBuilder $structureBuilder
    ) {
        $this->contentManager = $contentManager;
        $this->previewContentManager = $previewContentManager;
        $this->contentTypeManager = $contentTypeManager;
        $this->structureBuilder = $structureBuilder;
    }

    public function previewAction(Request $request, string $content): Response
    {
        $content = $this->previewContentManager->find($content);
        if (!$content instanceof ContentDocument || !$this->contentTypeManager->has($content->getContentType())) {
            return new Response('');
        }
        $contentType = $this->contentTypeManager->get($content->getContentType());
        $routeDefaults = $contentType->getAttribute('route_defaults', []);
        $controller = $routeDefaults['_controller'] ?? sprintf('%s::indexAction', DefaultController::class);
        $template = $routeDefaults['_template'] ?? '@RabbleWebsite/Default/index.html.twig';

        return $this->forward($controller, array_merge($request->attributes->all(), [
            'template' => $template,
            WebsiteRouter::CONTENT_KEY => $this->structureBuilder->build($content, StructureBuilder::TARGET_WEBSITE),
        ]));
    }

    public function saveAction(Request $request): Response
    {
        $this->contentManager->setLocale($request->attributes->get(RouterContextSubscriber::CONTENT_LOCALE_KEY));
        $this->previewContentManager->setLocale($request->attributes->get(RouterContextSubscriber::CONTENT_LOCALE_KEY));
        $contentId = $request->request->get('content');
        $contentType = $request->request->get('contentType');
        if (!is_string($contentId) || !is_string($contentType) || !$this->contentTypeManager->has($contentType)) {
            throw new NotFoundHttpException();
        }
        $contentType = $this->contentTypeManager->get($contentType);

        $content = $this->previewContentManager->find(self::NEW_NODE_PATH) ?? new ContentDocument();
        $content->setPath(self::NEW_NODE_PATH);
        $content->setTitle('');
        if (UUIDHelper::isUUID($contentId)) {
            $existingDocument = $this->contentManager->find($contentId);
            if ($existingDocument instanceof AbstractPersistenceDocument) {
                $path = self::NODE_PATH.'/'.$contentId;
                $content = $this->previewContentManager->find($path) ?? new ContentDocument();
                $content->setPath($path);
                $content->setProperties($existingDocument->getProperties());
            }
        }
        $content->setContentType($contentType->getName());
        $content->setNodeName(PathHelper::getNodeName($content->getPath()));
        try {
            $this->createForm(
                ContentFormType::class,
                $content,
                ['fields' => $contentType->getFields()]
            )->handleRequest($request);
        } catch (InvalidArgumentException $exception) {
            return new JsonResponse([]);
        }
        $this->previewContentManager->persist($content);
        $this->previewContentManager->flush();

        return new JsonResponse([
            'uuid' => $content->getUuid(),
        ]);
    }
}
