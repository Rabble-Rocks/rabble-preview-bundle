<?php

namespace Rabble\PreviewBundle\EventListener;

use Rabble\AdminBundle\Ui\Layout\GridColumn;
use Rabble\AdminBundle\Ui\Layout\GridRow;
use Rabble\AdminBundle\Ui\Panel\ContentPanel;
use Rabble\ContentBundle\UI\Event\ContentUiEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Twig\Environment;

class ContentUiGridSubscriber implements EventSubscriberInterface
{
    private Environment $twig;

    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }

    public static function getSubscribedEvents()
    {
        return [
            ContentUiEvent::class => 'onContentUi',
        ];
    }

    public function onContentUi(ContentUiEvent $event)
    {
        $row = $event->getPane();
        if (!$row instanceof GridRow) {
            return;
        }
        $formPanel = $row->getColumn('form_panel');
        $formPanel->setOption('defaultWidth', 12);
        $formPanel->setOption('largeWidth', 6);
        $row->addColumn('preview', new GridColumn([
            'defaultWidth' => 6,
            'attributes' => [
                'class' => 'd-none d-lg-block',
            ],
            'content' => new ContentPanel([
                'content' => $this->twig->render('@RabblePreview/Ui/preview.html.twig'),
            ]),
        ]));
    }
}
