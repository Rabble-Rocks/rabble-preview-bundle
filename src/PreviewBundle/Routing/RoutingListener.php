<?php

namespace Rabble\PreviewBundle\Routing;

use Rabble\AdminBundle\Routing\Event\RoutingEvent;

class RoutingListener
{
    public function onRoutingLoad(RoutingEvent $event)
    {
        $event->addResources('xml', ['@RabblePreviewBundle/Resources/config/routing.xml']);
    }
}
