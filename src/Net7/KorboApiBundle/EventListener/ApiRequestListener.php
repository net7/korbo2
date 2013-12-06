<?php

namespace Net7\KorboApiBundle\EventListener;


use Net7\KorboApiBundle\Controller\LettersController;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

/**
 * Class ApiRequestListener
 *
 * @package Net7\KorboApiBundle\EventListener
 */
class ApiRequestListener
{
    /**
     * On kernel request call the function initialize (is a kind of pre-execute)
     *
     * @param FilterControllerEvent $event
     */
    public function onKernelController(FilterControllerEvent $event)
    {
        $controller = $event->getController();

        /*
         * $controller passed can be either a class or a Closure. This is not usual in Symfony2 but it may happen.
         * If it is a class, it comes in array format
         */
        if (!is_array($controller)) {
            return;
        }

        $controllerObject = $controller[0];
        if ($controllerObject instanceof LettersController) {

            // call a kind of pre-execute method
            $controllerObject->initialize($event->getRequest());

        }

        // ...
    }

}