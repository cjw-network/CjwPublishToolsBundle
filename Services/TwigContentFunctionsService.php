<?php
/**
 * File containing the TwigFunctionsService class
 *
 * @copyright Copyright (C) 2007-2015 CJW Network - Coolscreen.de, JAC Systeme GmbH, Webmanufaktur. All rights reserved.
 * @license http://ez.no/licenses/gnu_gpl GNU GPL v2
 * @version //autogentag//
 * @filesource
 *
 */

namespace Cjw\PublishToolsBundle\Services;

use eZ\Publish\API\Repository\Repository;
use eZ\Publish\Core\Base\Exceptions\InvalidArgumentException;
use eZ\Publish\Core\FieldType\RichText\Converter as RichTextConverterInterface;
use eZ\Publish\Core\MVC\ConfigResolverInterface;
use eZ\Publish\Core\MVC\Symfony\Matcher\ContentBased\Identifier\ContentType;
use eZ\Publish\Core\Repository\Values\Content\Content;
use eZ\Publish\Core\Repository\Values\Content\Location;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Container;
use Twig_Extension;
use Twig_Environment;
use Twig_SimpleFunction;
use Twig_Template;

/**
 * Twig content extension for eZ Publish specific usage.
 * Exposes helpers to play with public API objects.
 */
class TwigContentFunctionsService extends Twig_Extension
{
    /**
     * Array of Twig template resources for cjw_render_view
     * Either the path to each template and its priority in a hash or its
     * \Twig_Template (compiled) counterpart
     *
     * @var array|\Twig_Template[]
     */
    protected $renderLocationViewResources;

    /**
     * A \Twig_Template instance used to render template blocks.
     *
     * @var \Twig_Template
     */
    protected $template;

    /**
     * The Twig environment
     *
     * @var \Twig_Environment
     */
    protected $environment;

    /**
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     */
    protected $container;

    /**
     * @var \eZ\Publish\API\Repository\Repository
     */
    protected $repository;

    /**
     * @var \eZ\Publish\Core\MVC\ConfigResolverInterface
     */
    protected $configResolver;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    protected $templateDebug = false;

    protected $cacheContentTypeIdentifier = array();


    public function __construct(
        Container $container,
        Repository $repository,
        ConfigResolverInterface $resolver,
        LoggerInterface $logger = null
    )
    {

        $this->renderLocationViewResources = $resolver->getParameter( 'location_view' );
        //  var_dump( $this->renderLocationViewSettings );

        $this->container = $container;
        $this->repository = $repository;
        $this->configResolver = $resolver;
        $this->logger = $logger;

        // add html comments of rendered template into html
       // $this->templateDebug = true;
    }


    /**
     * Initializes the template runtime (aka Twig environment).
     *
     * @param \Twig_Environment $environment
     */
    public function initRuntime( Twig_Environment $environment )
    {
        $this->environment = $environment;
    }

    /**
     * Returns a list of functions to add to the existing list.
     *
     * @return array
     */
    public function getFunctions()
    {
        return array(
            new Twig_SimpleFunction(
                'cjw_render_location',
                array( $this, 'renderLocation' ),
                array( 'is_safe' => array( 'html' ) )
            )
        );
    }

    /**
     * Returns a list of filters to add to the existing list
     *
     * @return array
     */
    public function getFilters()
    {
        return array();
    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'cjw_publishtools_twig_content_extension';
    }


    /**
     *  render a viewtemplate with a twigfunction uses the ezpulish  override.yml
     *
     *     {{ cjw_render_location( {'locationId': locationObject.id, 'viewType': 'line'} ) }}
     *
     *     => faster is the following call
     *
     *     {{ cjw_render_location( {'location': locationObject, 'viewType': 'line'} ) }}

     * @param array  $parameters  hash
     *
     * @return string
     */
    public function renderLocation( array $params )
    {

        $viewType = $params['viewType'];

        if ( isset( $params['location'] ) && $params['location'] instanceof Location )
        {
            $location = $params['location'];

            $locationId = $location->id;
        }
        elseif( isset( $params['locationId'] ) )
        {
            $locationId = $params['locationId'];

            $location = $this->repository->getLocationService()->loadLocation( $locationId );
            if ( $location->invisible )
            {
                throw new NotFoundHttpException( "Location #$locationId cannot be displayed as it is flagged as invisible." );
            }
        }
        else
        {
            // TODO Fehler wenn locationId nicht gesetzt oder location keine Location objekt
            return false;
        }

        $templateFileName = 'CjwPublishToolsBundle::line.html.twig';

        $this->template = $this->environment->loadTemplate( $templateFileName );


        $locationObject = $this->repository->getLocationService()->loadLocation( $locationId );

        $contentId = $locationObject->contentInfo->id;
        $contentObject = $this->repository->getContentService()->loadContent( $contentId );

       // $view = 'CjwPublishToolsBundle::line.html.twig';

        // find override template
        $template = $this->getOverrideTemplate( $locationObject, $contentObject, $viewType );

        //var_dump( $template );

        $params['location'] = $locationObject;
        $params['content'] = $contentObject;

        // name of the rendered template
        $params['_template'] = $template;

        $temapleRenderedContent = $this->getTemplateEngine()->render( $template, $params );

        // TODO  wenn templatedebug eingeschaltet ist dieses anzeigen

        if ( $this->templateDebug == true )
        {
            $temapleContentDebug = "\n<!-- cjw_render_location: LocationId: $locationId, viewType: $viewType, Template: $template -->\n";

            $temapleRenderedContent = $temapleContentDebug . "\n" . $temapleRenderedContent;
        }

        return  $temapleRenderedContent;
    }







    /**
     * Use ez override yml for override Rules which are used vor  default ViewController::viewLocation
     *
     * all Matcher from ez should work
     *
     * match:
     *     Identifier\ContentType: cjw_article
     *
     * TODO custom matcher not work at the moment
     *
     * @param Location $location
     * @param Content $content
     * @param $viewType line, full, my_view_name
     * @return string templateName  CjwPublishToolsBundle::default_location_view.html.twig
     */
    private function getOverrideTemplate( Location $location, Content $content, $viewType = line )
    {

        //
        //        location_view:
        //            line:
        //                cjw_article:
        //                    template: CjwPublishToolsBundle:line:cjw_article.html.twig
        //                         match:
        //                            Identifier\ContentType: cjw_article

        //                default:
        //                       template: CjwPublishToolsBundle::line.html.twig
        //                       match:

        $locationViewArray = $this->configResolver->getParameter( 'location_view' );

        // no template rules for given viewtype configured
        if ( !isset( $locationViewArray[ $viewType ] ) )
        {
            $defaultTemplate = "CjwPublishToolsBundle::default_location_view.html.twig";
            return $defaultTemplate;
        }

        //   line ... settings
        $configViewType = $locationViewArray[ $viewType ];

        foreach( $configViewType as $configOverrideName => $configOverrideItem )
        {
            //$hashKey = $viewType. '|'. $configOverrideName;

            // den namen nicht speichern um später schnell auf hash zugreifen zu können
            $hashKey = $viewType;
            $template = $configOverrideItem['template'];

            $matchArray = $configOverrideItem['match'];

            $isOverrideMatch = true;

            // default matching - because no matcher defined $machtarray count = 0
            if ( is_array( $matchArray ) && count( $matchArray ) == 0 )
            {
                //echo "OVERRIDE_RULE_MATCH:  $viewType: $configOverrideName: match: ";
                //$isOverrideMatch = true;
            }
            else
            {
                // check all defined matcher of the override rule
                // if all matches the rule matches => return template
                // if not return false
                foreach( $matchArray as $matchIdentifier => $matchIdentifierValue )
                {

                    //var_dump( $matchIdentifierValue );
                    switch( $matchIdentifier )
                    {
                        // optimize ez matcher Identifier\ContentType, reduce api calls for getting contentTypeIdentifer for contentTypId
                        // for 20 lineviews and 3 override rules with class_identifier it reduces the api / stash calls with 130 calls and execution time ~30ms depends on system
                        // so 20 spi calls cost us 3ms ~  7spi calls cost us 1 ms
                        // with the optimized version the ContentTypeIdentifier for an id will be cached in RAM
                        // TODO may be override the original Identifier\ContentType class or write an own CjwPublishTools\Identifier\ContentType
                        case 'Identifier\ContentType':

                            $contentTypeId = $content->contentInfo->contentTypeId;
                            $contentTypeIdentifier = $this->getContentTypeIdentifier( $contentTypeId );

                            if ( !is_array( $matchIdentifierValue ) )
                            {
                                $matchIdentifierValue = array( $matchIdentifierValue );
                            }

                            //echo "<br>OVERRIDE_RULE check::  $viewType: $configOverrideName: match: $matchIdentifier: $matchIdentifierValue == $contentTypeIdentifier";
                            if ( !in_array( $contentTypeIdentifier, $matchIdentifierValue ) )
                            {
                                //echo "<br>OVERRIDE_RULE_MATCH:  $viewType: $configOverrideName: match: $matchIdentifier: $matchIdentifierValue";
                                // if not match set false
                                $isOverrideMatch = false;
                            }
                            break;

                        // using ez matchers
                        default:

                            // using Identifier/ContentType matcher von ez
                            //$namespace = 'eZ\Publish\Core\MVC\Symfony\Matcher\ContentBased\Identifier\ContentType';

                            $namespace = 'eZ\Publish\Core\MVC\Symfony\Matcher\ContentBased'."\\". $matchIdentifier;
                            $matchObject = new $namespace;

                            $matchObject->setRepository( $this->repository );
                            $matchObject->setMatchingConfig( $matchIdentifierValue );
                            $matchResult = $matchObject->matchLocation( $location );

                            if ( $matchResult == false )
                            {

                                $isOverrideMatch = false;
                            }
                            else
                            {
                               // echo "<br>OVERRIDE_RULE_MATCH:  $viewType: $configOverrideName: match: $matchIdentifier: $matchIdentifierValue";
                            }
                            break;
                    }

                }
            }

            if ( $isOverrideMatch === true )
            {
                return $template;
            }

        }

        $defaultTemplate = "CjwPublishToolsBundle::default_location_view.html.twig";
        return $defaultTemplate;
    }



    /**
     * @return \Symfony\Component\Templating\EngineInterface
     */
    public function getTemplateEngine()
    {
        return $this->container->get( 'templating' );
    }
//
//    /**
//     * @return \Psr\Log\LoggerInterface|null
//     */
//    public function getLogger()
//    {
//        return $this->container->get( 'logger', ContainerInterface::NULL_ON_INVALID_REFERENCE );
//    }
//
//    /**
//     * @return \eZ\Publish\API\Repository\Repository
//     */
//    public function getRepository()
//    {
//        return $this->container->get( 'ezpublish.api.repository' );
//    }
//

    /**
     * @param $contentTypeId
     * @return bool|string
     */
    public function getContentTypeIdentifier( $contentTypeId )
    {

        if ( isset( $this->cacheContentTypeIdentifier[$contentTypeId] ) )
        {
            return $this->cacheContentTypeIdentifier[$contentTypeId];
        }


        $cs = $this->repository->getContentTypeService();
        try
        {
            $ct = $cs->loadContentType( $contentTypeId );
            $contentTypeIdentifier = $ct->identifier;
            $this->cacheContentTypeIdentifier[$contentTypeId] = $contentTypeIdentifier;
            return $contentTypeIdentifier;
        }
        catch( \eZ\Publish\API\Repository\Exceptions\NotFoundException $error )
        {
            return false;
        }
    }

}
