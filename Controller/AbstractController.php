<?php
/**
 * PimcoreConfigurationBundle
 * Copyright (c) Lukaschel
 */

namespace Lukaschel\PimcoreConfigurationBundle\Controller;

use Pimcore\Controller\FrontendController;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

abstract class AbstractController extends FrontendController
{
    /**
     * {@inheritdoc}
     */
    public function onKernelController(FilterControllerEvent $event)
    {
        parent::onKernelController($event);
        $loader = $this->container->get('twig')->getLoader();
        $loader->addPath(dirname(__DIR__) . '/Resources/views');
        $this->setViewAutoRender($event->getRequest(), true, 'twig');
    }
}
