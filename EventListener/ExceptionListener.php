<?php

namespace Cjw\PublishToolsBundle\EventListener;

use eZ\Bundle\EzPublishCoreBundle\Kernel;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\HttpKernel;

/**
 * Class ExceptionListener
 *
 * Shows a custom error page for symfony exception and exceptions that will be thrown by the
 * {@see eZ\Bundle\EzPublishLegacyBundle\Controller\LegacyKernelController} controller as well.
 */
class ExceptionListener
{
    /**
     * Template engine
     *
     * @var EngineInterface
     */
    protected $templating;

    /**
     * HTTP kernel
     *
     * @var Kernel
     */
    protected $kernel;

    /**
     * Template, which will be rendered, if an exception is catched by the ExceptionListener class.
     *
     * @var string $errorTemplate
     */
    protected $errorTemplate;

    /**
     * Constructor
     *
     * @param EngineInterface $templating Templating engine
     * @param Kernel $kernel Application kernel
     * @param string $errorTemplate Template, which will be rendered, if an exception is catched by
     *               the ExceptionListener class.
     */
    public function __construct( EngineInterface $templating, $kernel, $errorTemplate )
    {
        $this->templating = $templating;
        $this->kernel = $kernel;
        $this->errorTemplate = $errorTemplate;
    }

    /**
     * Renders the defined {@see ExceptionListener::$errorTemplate}, which has been defined via YAML
     * settings, on exception.
     *
     * Note, that the function is only called, if the *debug value* is set or *error pages* are
     * enabled via Parameter *stvd.error_page.enabled*.
     *
     * @param GetResponseForExceptionEvent $event
     */
    public function onKernelException( GetResponseForExceptionEvent $event )
    {
        // don't do anything if it's not the master request
        if ( HttpKernel::MASTER_REQUEST != $event->getRequestType() )
        {
            return;
        }

        // You get the exception object from the received event
        $exception = $event->getException();

        // Customize your response object to display the exception details
        $response = new Response();

        // set response content
        $response->setContent(
            $this->templating->render(
                $this->errorTemplate,
                array( 'exception' => $exception )
            )
        );

        // HttpExceptionInterface is a special type of exception that
        // holds status code and header details
        if ( $exception instanceof HttpExceptionInterface )
        {
            $response->setStatusCode( $exception->getStatusCode() );
            $response->headers->replace( $exception->getHeaders() );
        }
        else
        {
            // If the exception's status code is not valid, set it to *500*. If it's valid, the
            // status code will be transferred to the response.
            if ( $exception->getCode() )
            {
                $response->setStatusCode( $exception->getCode() );
            }
            else
            {
                $response->setStatusCode( 500 );
            }
        }

        // Send the modified response object to the event
        $event->setResponse( $response );
    }
}
