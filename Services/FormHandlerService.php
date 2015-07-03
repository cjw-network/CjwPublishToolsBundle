<?php
/**
 * File containing the FormHandlerService class
 *
 * @copyright Copyright (C) 2007-2015 CJW Network - Coolscreen.de, JAC Systeme GmbH, Webmanufaktur. All rights reserved.
 * @license http://ez.no/licenses/gnu_gpl GNU GPL v2
 * @version //autogentag//
 * @filesource
 */

namespace Cjw\PublishToolsBundle\Services;

use Symfony\Component\Templating\EngineInterface;

class FormHandlerService
{
    protected $container;
    protected $em;
    protected $mailer;
    protected $templating;
    protected $formBuilderService;

    /**
     * init the needed services
     */
    public function __construct( $container, $em, \Swift_Mailer $mailer, EngineInterface $templating, $FormBuilderService )
    {
        $this->container = $container;
        $this->em = $em;
        $this->mailer = $mailer;
        $this->templating = $templating;
        $this->formBuilderService = $FormBuilderService;
    }

    /**
     * Adding collected form content to the old ez info collector
     *
     * @param mixed $formDataObj
     * @param array $handlerConfigArr
     * @param mixed $handlerParameters
     *
     * @return bool false
     */
    public function addToInfoCollectorHandler( $formDataObj, $handlerConfigArr, $handlerParameters )
    {
        $content = $handlerParameters['content'];
        $contentType = $handlerParameters['contentType'];

        $formBuilderService = $this->container->get( 'cjw_publishtools.formbuilder.functions' );

        $timestamp = time();

        // get table from db (services.yml)
        $ezinfocollection = $this->container->get( 'db_table_ezinfocollection' );

        // add new collection
        $ezinfocollectionRow = new $ezinfocollection();

        $ezinfocollectionRow->set( 'contentobject_id', $handlerParameters['contentObjectId'] );
        $ezinfocollectionRow->set( 'user_identifier', '' );
        $ezinfocollectionRow->set( 'creator_id', $handlerParameters['currentUserId'] );
        $ezinfocollectionRow->set( 'created', $timestamp );
        $ezinfocollectionRow->set( 'modified', $timestamp );

        $this->em->persist( $ezinfocollectionRow );
        $this->em->flush();

        $informationcollectionId = $ezinfocollectionRow->getId();

        // get table from db (services.yml)
        $ezinfocollectionAttribute = $this->container->get( 'db_table_ezinfocollection_attribute' );

        // add collection attribute
        foreach( $formDataObj as $key => $attribute )
        {
            $keyArr = explode( ':', $key );
            $fieldType = $keyArr['0'];
            $fieldIdentifier = $keyArr['1'];

            $data_float = 0;
            $data_int = 0;
            $data_text = '';

            switch ( $fieldType )
            {
                case 'ezxml':
                    $data_text =  $formBuilderService->newEzXmltextSchema( $attribute );
                    break;

                default:
                    $data_text = (string) $attribute;
            }

            $ezinfocollectionAttributeRow = new $ezinfocollectionAttribute();
            $ezinfocollectionAttributeRow->set( 'contentobject_id', $handlerParameters['contentObjectId'] );
            $ezinfocollectionAttributeRow->set( 'informationcollection_id', $informationcollectionId );
            $ezinfocollectionAttributeRow->set( 'contentclass_attribute_id', $contentType[$fieldIdentifier]->id );
            $ezinfocollectionAttributeRow->set( 'contentobject_attribute_id', $content->getField($fieldIdentifier)->id );
            $ezinfocollectionAttributeRow->set( 'data_float', $data_float );
            $ezinfocollectionAttributeRow->set( 'data_int', $data_int );
            $ezinfocollectionAttributeRow->set( 'data_text', $data_text );

            $this->em->persist( $ezinfocollectionAttributeRow );
            $this->em->flush();
        }

        return false;
    }

    /**
     * Builds and sending an email, renders the email body with an twig template
     *
     * @param mixed $formDataObj
     * @param array $handlerConfigArr
     * @param mixed $handlerParameters
     *
     * @return bool false
     */
    public function sendEmailHandler( $formDataObj, $handlerConfigArr, $handlerParameters )
    {
        $formDataArr = array();
        foreach ( $formDataObj as $key => $item )
        {
            $keyArr = explode( ':', $key );
            $formDataArr[$keyArr['1']] = $item;
        }

        $template = false;
        if ( isset( $handlerConfigArr['template'] ) )       // ToDo: more checks
        {
            $template = $this->formBuilderService->getTemplateOverride( $handlerConfigArr['template'] );
        }

        $subject = false;
        if ( isset( $handlerConfigArr['email_subject'] ) )        // ToDo: more checks
        {
            if ( substr( $handlerConfigArr['email_subject'], 0, 1 ) === '@' )
            {
                $subject_mapping = substr( $handlerConfigArr['email_subject'], 1 );
                if ( isset( $formDataArr[$subject_mapping] ) )
                {
                    $subject = $formDataArr[$subject_mapping];
                }
            }
            else
            {
                $subject = $handlerConfigArr['email_subject'];
            }
// ToDo: subject mapping / static (intl)
        }

        $from = false;
        if ( isset( $handlerConfigArr['email_sender'] ) )
        {
            if ( substr( $handlerConfigArr['email_sender'], 0, 1 ) === '@' )
            {
                $email_sender_mapping = substr( $handlerConfigArr['email_sender'], 1 );
                if ( isset( $formDataArr[$email_sender_mapping] ) )
                {
                    // Check email addresses validity by using PHP's internal filter_var function
                    if( filter_var( $formDataArr[$email_sender_mapping], FILTER_VALIDATE_EMAIL ) )
                    {
                        $from = $formDataArr[$email_sender_mapping];
                    }
                }
            }
            else
            {
                // Check email addresses validity by using PHP's internal filter_var function
                if( filter_var( $handlerConfigArr['email_sender'], FILTER_VALIDATE_EMAIL ) )
                {
                    $from = $handlerConfigArr['email_sender'];
                }
            }
        }

        $to = false;
        if ( isset( $handlerConfigArr['email_receiver'] ) )
        {
            if( substr( $handlerConfigArr['email_receiver'], 0, 1 ) === '@' )
            {
                $email_receiver_mapping = substr( $handlerConfigArr['email_receiver'], 1 );
                if( isset( $formDataArr[$email_receiver_mapping] ) )
                {
                    // Check email addresses validity by using PHP's internal filter_var function
                    if ( filter_var( $formDataArr[$email_receiver_mapping], FILTER_VALIDATE_EMAIL ) )
                    {
                        $to = $formDataArr[$email_receiver_mapping];
                    }
                }
            }
            else
            {
                // Check email addresses validity by using PHP's internal filter_var function
                if ( filter_var( $handlerConfigArr['email_receiver'], FILTER_VALIDATE_EMAIL ) )
                {
                    $to = $handlerConfigArr['email_receiver'];
                }
            }
        }

        $logging = false;
        if ( isset( $handlerConfigArr['logging'] ) && $handlerConfigArr['logging'] === true )
        {
            $logging = true;
        }

        $debug = false;
        if ( isset( $handlerConfigArr['debug'] ) && $handlerConfigArr['debug'] === true )
        {
            $debug = true;
        }

        if ( $template !== false && $subject !== false && $from !== false && $to !== false )
        {
// ToDo: render template inline if $template false
            $message = \Swift_Message::newInstance()
                ->setSubject( $subject )
                ->setFrom( $from )
                ->setTo( $to )
                ->setBody( $this->templating->render( $template, array( 'body' => $formDataArr ) ), 'text/html' );

//            $message->addPart('ToDo', 'text/plain');

            if ( $debug === false )
            {
                $this->mailer->send( $message );
                // ToDo: catch errors
            }

            if ( $logging === true )
            {
                $msgId = substr( $message->getHeaders()->get( 'Message-ID' )->getFieldBody(), 1, -1 );
                $dump = $message->getHeaders()->toString() . $message->getBody();
                $log_dir = $this->container->getParameter( 'kernel.logs_dir' ) . '/formbuilder/';

                if ( is_dir( $log_dir ) === false )
                {
                    mkdir( $log_dir );
                }

                file_put_contents( $log_dir.time() . '_' . $msgId, $dump );
            }
        }
        else
        {
            // ToDo: error log
            echo 'error';exit;
        }

        return false;
    }

    /**
     * Handle a success action
     *
     * @param mixed $formDataObj
     * @param array $handlerConfigArr
     * @param mixed $handlerParameters
     *
     * @return result
     */
    public function successHandler( $formDataObj, $handlerConfigArr, $handlerParameters )
    {
        $content = false;
        if ( isset( $handlerParameters['content'] ) )
        {
            $content = $handlerParameters['content'];
        }

        $location = false;
        if ( isset( $handlerParameters['location'] ) )
        {
            $location = $handlerParameters['location'];
        }

        $template = $this->formBuilderService->getTemplateOverride( $handlerConfigArr['template'] );

        // ToDo: template checks, if false render inline

        return $this->templating->render(
            $template,
            array( 'form' => $formDataObj, 'content' => $content, 'location' => $location )
        );
    }

    /**
     * ToDo
     */
    public function contentAddHandler()
    {
        return false;
    }

    /**
     * ToDo
     */
    public function contentEditHandler()
    {
        return false;
    }
}
