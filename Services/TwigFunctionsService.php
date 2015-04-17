<?php
/**
 * File containing the TwigFunctionsService class
 *
 * @copyright Copyright (C) 2007-2014 CJW Network - Coolscreen.de, JAC Systeme GmbH, Webmanufaktur. All rights reserved.
 * @license http://ez.no/licenses/gnu_gpl GNU GPL v2
 * @version //autogentag//
 * @filesource
 *
 */

namespace Cjw\PublishToolsBundle\Services;

class TwigFunctionsService extends \Twig_Extension
{
    /**
     * @var \Cjw\PublishToolsBundle\Services\PublishToolsService
     */
    protected $PublishToolsService;

    /**
     * @param \Cjw\PublishToolsBundle\Services
     */
    public function __construct( $PublishToolsService )
    {
        $this->PublishToolsService = $PublishToolsService;
    }

    /**
     * Returns a list of functions to add to the existing list.
     *
     * @return array
     */
    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction( 'cjw_cache_set_ttl', array( $this, 'setCacheTtl' ) ),
            new \Twig_SimpleFunction( 'cjw_breadcrumb', array( $this, 'getBreadcrumb' ) ),
            new \Twig_SimpleFunction( 'cjw_treemenu', array( $this, 'getTreemenu' ) ),
            new \Twig_SimpleFunction( 'cjw_load_content_by_id', array( $this, 'loadContentById' ) ),
            new \Twig_SimpleFunction( 'cjw_fetch_content', array( $this, 'fetchContent' ) ),
            new \Twig_SimpleFunction( 'cjw_user_get_current', array( $this, 'getCurrentUser' ) ),
            new \Twig_SimpleFunction( 'cjw_lang_get_default_code', array( $this, 'getDefaultLangCode' ) ),
            new \Twig_SimpleFunction( 'cjw_content_download_file', array( $this, 'streamFile' ) ),
            new \Twig_SimpleFunction( 'cjw_redirect', array( $this, 'redirect' ) )
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
        return 'cjw_publishtools_twig_extension';
    }

    /**
     * example:  {{ cjw_cache_set_ttl( 0 ) }}
     *
     * the first call will be set the ttl of the template
     *
     * @param int $ttl ttl of http_cache in s, 0 http_cache off
     */
    public function setCacheTtl( $ttl = 0 )
    {
        if ( !isset( $GLOBALS['CJW_HTTP_CACHE_TTL'] ) )
        {
            $GLOBALS['CJW_HTTP_CACHE_TTL'] = (int) $ttl;
        }
    }

    /**
     * Returns the breadcrumb for $locationId
     *
     * @param integer $locationId
     * @param array $params
     *
     * @return array
     */
    public function getBreadcrumb( $locationId = 0, array $params = array() )
    {
        $pathArr = $this->PublishToolsService->getPathArr( $locationId, $params );
        return $pathArr;
    }

    /**
     * Returns an treemenu (list of locations) for $locationId
     *
     * @param integer $locationId
     * @param array $params
     *
     * @return array
     */
    public function getTreemenu( $locationId = 0, array $params = array() )
    {
        $menuArr = array();

        $depth = 1;
        if ( isset( $params['depth'] ) && $params['depth'] > 1 )
        {
            $depth = $params['depth'];
        }

        $offset = 0;
        if ( isset( $params['offset'] ) && $params['offset'] > 0 )
        {
            $offset = $params['offset'];
        }

        $include = false;
        if ( isset( $params['include'] ) && is_array( $params['include'] ) && count( $params['include'] ) > 0 )
        {
            $include = $params['include'];
        }

        $datamap = false;
        if ( isset( $params['datamap'] ) && $params['datamap'] === true )
        {
            $datamap = $params['datamap'];
        }

        $sortby = false;
        if ( isset( $params['sortby'] ) && $params['sortby'] !== false )
        {
            $sortby = $params['sortby'];
        }

        $pathArr = $this->PublishToolsService->getPathArr( $locationId, array( 'offset' => $offset ) );

        $depthCounter = 1;
        foreach( $pathArr['items'] as $location )
        {
            $result = $this->PublishToolsService->fetchLocationListArr(
                array( $location['locationId'] ), array( 'depth' => 1, 'include' => $include, 'datamap' => $datamap, 'sortby' => $sortby )
            );

            $insertArr = $result[$location['locationId']]['children'];

            // add first, last and level info
            $insertArrNew = array();
            $lastCounter = 0;
            $firstToggle = 1;
            foreach( $insertArr as $child )
            {
                if ( $lastCounter > 0 )
                {
                    $firstToggle = 0;
                }

                $insertArrNew[] = array( 'node' => $child,
                                         'level' => $depthCounter,
                                         'selected' => 0,
                                         'children' => 0,
                                         'first' => $firstToggle,
                                         'last' => 0 );
                $lastCounter++;
            }
            if( count( $insertArrNew ) )
            {
                $insertArrNew[$lastCounter-1]['last'] = 1;
            }

            // get insert position
            $insertPosition = 0;
            foreach( $menuArr as $insertKey => $menuItem )
            {
                if( $location['locationId'] == $menuItem['node']->id )
                {
                    // add selected and children count info
                    $menuArr[$insertKey]['selected'] = 1;
                    $menuArr[$insertKey]['children'] = count( $insertArrNew );

                    $insertPosition = $insertKey + 1;
                    break;
                }
            }

            // if no insert position found (location is not part of menu tree), show top menu entries only
            if ( $insertPosition > 0 || $depthCounter == 1)
            {
                // http://stackoverflow.com/questions/3797239/insert-new-item-in-array-on-any-position-in-php
                array_splice( $menuArr, $insertPosition, 0, $insertArrNew );
            }

            $depthCounter++;
            if( $depthCounter > $depth )
            {
                break;
            }
        }

        return $menuArr;
    }

    /**
     * Fetch content by contentId.
     *
     * @param integer $contentId
     * @return \eZ\Publish\API\Repository\Values\Content\Content
     */
    public function loadContentById( $contentId )
    {
        $content = $this->PublishToolsService->loadContentById( $contentId );
        return $content;
    }

    /**
     * Returns / find a list of locations / content y search params
     *
     * @param array $locationId
     * @param array $params
     *
     * @return array
     */
    public function fetchContent( $locationId, array $params = array() )
    {
        $locationList = $this->PublishToolsService->fetchLocationListArr( $locationId, $params );
        return $locationList;
    }

    /**
     * Returns the current user info (is logged in)
     *
     * @return array
     */
    public function getCurrentUser()
    {
        $user = $this->PublishToolsService->getCurrentUser();
        return $user;
    }

    /**
     * Returns the default language code string
     *
     * @return string
     */
    public function getDefaultLangCode()
    {
        $lang = $this->PublishToolsService->getDefaultLangCode();
        return $lang;
    }

    /**
     * make an redirect to $url
     *
     * @param string $url
     */
    public function redirect( $url = false )
    {
        if( $url )
        {
            header( 'Location: '.$url );
            exit();
        }
    }

    /**
     * streams an file
     *
     * @param mixed $file
     */
    public function streamFile( $file = false )
    {
        if( $file )
        {
            header( 'Content-Description: File Transfer' );
            header( 'Content-type: '.$file->mimeType );
            header( 'Content-Transfer-Encoding: binary' );
            header( 'Content-Length: '.$file->fileSize );
            header( 'Content-Disposition: attachment; filename='.$file->fileName );
            ob_clean();
            flush();
            readfile( '.'.$file->uri );
            exit;
        }
    }
}
