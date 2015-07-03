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

use eZ\Publish\Core\Base\Exceptions\InvalidArgumentException;
//use eZ\Publish\Core\FieldType\RichText\Converter as RichTextConverterInterface;
use eZ\Publish\Core\MVC\ConfigResolverInterface;
//use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Container;
use Twig_Extension;
//use Twig_Environment;
use Twig_SimpleFunction;
//use Twig_Template;

/**
 * Twig config extension for eZ Publish specific usage.
 * get access to config files from Twig
 *
 *
 * {{dump( cjw_siteaccess_parameters() ) }}
 * {{dump( cjw_siteaccess_parameters().bundleName ) }}
 *
 * {{dump( cjw_config_resolver_get_parameter( 'parameters' , 'cjwsiteaccess' ).bundleName ) }}
 *
 * {{dump( cjw_config_get_parameter( 'mailer_transport' ) ) }}
 *
 * {{dump( cjw_config_get_parameter( 'locale_fallback' ) ) }}
 *
 *
 */
class TwigConfigFunctionsService extends Twig_Extension
{

    /**
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     */
    protected $container;

    /**
     * @var \eZ\Publish\Core\MVC\ConfigResolverInterface
     */
    protected $configResolver;

    protected $cjwSiteAccessParameters;

    public function __construct(
        Container $container,
        ConfigResolverInterface $resolver
    )
    {
        $this->container = $container;
        $this->configResolver = $resolver;

    }

    /**
     * Returns a list of functions to add to the existing list.
     *
     * @return array An array of Twig_SimpleFunction objects.
     */
    public function getFunctions()
    {
        return array(
            new Twig_SimpleFunction(
                'cjw_siteaccess_parameters',
                array( $this, 'siteAccessParameters' ),
                array( 'is_safe' => array( 'html' ) )
            ),
            new Twig_SimpleFunction(
                'cjw_config_resolver_get_parameter',
                array( $this, 'configResolverGetParameter' ),
                array( 'is_safe' => array( 'html' ) )
            ),
            new Twig_SimpleFunction(
                'cjw_config_get_parameter',
                array( $this, 'configGetParameter' ),
                array( 'is_safe' => array( 'html' ) )
            )
        );
    }

    /**
     * Returns a list of filters to add to the existing list
     *
     * @return array Array of filters.
     */
    public function getFilters()
    {
        return array();
    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extensions name
     */
    public function getName()
    {
        return 'cjw_publishtools_twig_config_extension';
    }

    /**
     * Returns sizeaccess aware configuration parameters.
     *
     * {{ cjw_siteaccess_parameters() }}
     * {{ cjw_siteaccess_parameters().bundleName }}
     *
     * parameters:
     *     cjwsiteaccess.default.parameters:
     *         bundleName: SiteAccessTestBundle
     *         var2: Var2Value
     *
     * @return array An array with siteaccess aware configurations.
     */
    public function siteAccessParameters()
    {
        if ( !is_array( $this->cjwSiteAccessParameters ) )
        {
            $this->cjwSiteAccessParameters = $this->configResolverGetParameter( 'parameters', 'cjwsiteaccess' );
        }
        return $this->cjwSiteAccessParameters;
    }

    /**
     * Returns value for $paramName, in $namespace.
     *
     * @param string $paramName The parameter name, without $prefix and the current scope (i.e. siteaccess name).
     * @param string $namespace Namespace for the parameter name. If null, the default namespace should be used.
     * @param string $scope The scope you need $paramName value for.
     *
     * @return mixed
     */
    public function configResolverGetParameter( $paramName, $namespace = 'ezsettings'  )
    {
        $scope = null;

        if ( $this->configResolver->hasParameter( $paramName, $namespace, $scope ) )
        {
            return $this->configResolver->getParameter( $paramName, $namespace, $scope );
        }
        else
        {
            return null;
        }
    }

    /**
     *
     * {{dump( cjw_config_get_parameter( 'mailer_transport' ) ) }}
     *
     * Gets a parameter.
     *
     * @param string $name The parameter name
     *
     * @return mixed  The parameter value
     *
     * @throws InvalidArgumentException if the parameter is not defined
     *
     * @api
     */
    public function configGetParameter( $paramName )
    {
        if ( $this->container->hasParameter( $paramName ) )
        {
            return $this->container->getParameter( $paramName );
        }
        else
        {
            return null;
        }
    }

    /**
     *
     * {{ cjw_siteaccess_parameters() }}
     * {{ cjw_siteaccess_parameters().bundleName }}
     * {{ cjw_siteaccess_parameters( 'bundleName' ) }}
     *
     * parameters:
     *     cjwsiteaccess.default.parameters:
     *         bundleName: SiteAccessTestBundle
     *         var2: Var2Value
     * @paramName if set return the param from siteAccessParameters array
     * @return array with configuration siteaccess aware
     */
    /*public function siteAccessParameters( $paramName = false )
    {
        if ( !is_array( $this->cjwSiteAccessParameters ) )
        {
            $this->cjwSiteAccessParameters = $this->configResolverGetParameter( 'parameters', 'cjwsiteaccess' );
        }

        if ( $paramName !== false )
        {
            if ( isset( $this->cjwSiteAccessParameters[$paramName] ) )
            {
                return $this->cjwSiteAccessParameters[$paramName];
            }
            else
            {
                return null;
            }
        }
        else
        {
            return $this->cjwSiteAccessParameters;
        }
    }*/
}
