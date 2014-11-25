<?php
/**
 * File containing the PartsController class
 *
 * @copyright Copyright (C) 2007-2014 CJW Network - Coolscreen.de, JAC Systeme GmbH, Webmanufaktur. All rights reserved.
 * @license http://ez.no/licenses/gnu_gpl GNU GPL v2
 * @version //autogentag//
 * @filesource
 *
 */

namespace Cjw\PublishToolsBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class PartsController extends Controller
{
    /**
     *
     */
    public function menuAction( array $locationIdArr = array(), array $params = array() )
    {
//        $rootLocationId = $this->getConfigResolver()->getParameter( 'content.tree_root.location_id' );

        // Setting HTTP cache for the response to be public and with a TTL of 1 day.
        $response = new Response;
/*
        $response->setPublic();
        $response->setSharedMaxAge( 86400 );
        // Menu will expire when top location cache expires.
        $response->headers->set( 'X-Location-Id', $locationId );
        // Menu might vary depending on user permissions, so make the cache vary on the user hash.
        $response->setVary( 'X-User-Hash' );
*/
        $PublishToolsService = $this->get( 'publishtools.service.functions' );
        $locationListArr = $PublishToolsService->fetchLocationListArr( $locationIdArr, $params );

        return $this->render(
            'CjwPublishToolsBundle:parts:menu.html.twig',
            array( 'locationListArr' => $locationListArr ),
            $response
        );
    }

    /**
     *
     */
    public function pathAction( $locationId = 0, array $params = array() )
    {
        $response = new Response;
//        $response = $this->cacheResponse( $response, $locationId );

        $PublishToolsService = $this->get( 'publishtools.service.functions' );
        $pathArr = $PublishToolsService->getPathArr( $locationId, $params );

        return $this->render(
            'CjwPublishToolsBundle:parts:path.html.twig',
            array( 'pathArr' => $pathArr ),
            $response
        );
    }
}
