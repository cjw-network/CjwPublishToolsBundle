<?php
/**
 * File containing the CjwViewController class
 *
 * @copyright Copyright (C) 2007-2014 CJW Network - Coolscreen.de, JAC Systeme GmbH, Webmanufaktur. All rights reserved.
 * @license http://ez.no/licenses/gnu_gpl GNU GPL v2
 * @version //autogentag//
 * @filesource
 *
 */

namespace Cjw\PublishToolsBundle\Controller\Content;

use eZ\Publish\Core\MVC\Symfony\Controller\Content\ViewController;
use Symfony\Component\HttpFoundation\Response;

class CjwPublishToolsViewController extends ViewController
{
    /**
     * Main action for viewing content through a location in the repository.
     * Response will be cached with HttpCache validation model (Etag)
     *
     * it is possible to change the ttl of the cache by setting a global Variable
     *
     * e.g. in twig template with a twig function which set the global variable
     *
     *  $GLOBALS['EZ_TWIG_HTTP_CACHE_TTL'] = 0; => disable cache
     *  $GLOBALS['EZ_TWIG_HTTP_CACHE_TTL'] = 3; => 3 s
     *
     * @param int $locationId
     * @param string $viewType
     * @param boolean $layout
     * @param array $params
     *
     * @throws \Symfony\Component\Security\Core\Exception\AccessDeniedException
     * @throws \Exception
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function viewLocation( $locationId, $viewType, $layout = false, array $params = array() )
    {
        // run the original ViewController from Ez
        // if all is ok we get a Response object otherwise an exception
        $response = parent::viewLocation( $locationId, $viewType, $layout, $params );

        if ( $response instanceof Response )
        {
            if ( isset( $GLOBALS['CJW_HTTP_CACHE_TTL'] ) )
            {
                $ttlFromTemplate = (int) $GLOBALS['CJW_HTTP_CACHE_TTL'];

                $response->setTtl ( $ttlFromTemplate );

                if ( $ttlFromTemplate == 0 )
                {
                    $response->setPrivate();
                }
                //echo __METHOD__ . " get http_cache ttl from tpl: $ttlFromTemplate<br>";
                //$response->headers->set( 'X-Cache-Ttl', $ttlFromTemplate );
            }
        }
        return $response;
    }
}
