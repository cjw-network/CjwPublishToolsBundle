<?php

namespace Cjw\PublishToolsBundle\Controller;

use eZ\Bundle\EzPublishCoreBundle\Controller;
use Symfony\Component\HttpFoundation\Response;

// todo: get available languages, config item for include types, caching etc.
class SitemapsController extends Controller
{
    public function sitemapAction()
    {
        $ttl = 3600 * 12;
        $urls = array();
        $hostname = $this->getRequest()->getHost();

        $publishToolsService = $this->get( 'cjw_publishtools.service.functions' );

        $rootLocationId = $this->getConfigResolver()->getParameter('content.tree_root.location_id');

        $listLocations = $publishToolsService->fetchLocationListArr(
            array( $rootLocationId ), array( 'depth' => 10,
                              'limit' => 25000,
//                              'include' => array( 'article' ),
                              'main_location_only' => true,
                              'datamap' => false )
        );

        foreach( $listLocations[$rootLocationId]['children'] as $location )
        {
            $loc = $this->generateUrl( $location );
            $lastmod = date( 'c', $location->contentInfo->modificationDate->getTimestamp() );

            $urls[] = array( 'loc' => $loc,
                             'lastmod' => $lastmod );
        }

// https://doc.ez.no/display/EZP/Persistence+cache
// http://blogs.arondor.com/en/layout/set/print/Content-management/%28tag%29/Cache
        /* INIT Filesystem Cache */
/*
        $pool = $this->container->get( 'ezpublish.cache_pool' );
        $item = $pool->getItem( 'sitemapxml', $rootLocationId, $this->getRequest()->getScheme(), null );
        $data = $item->get();
*/
        /* GENERATE Cache Block */
/*
        if($item->isMiss())
        {
            // RenderView without cache headers
            $data = $this->renderView( 'CjwPublishToolsBundle::sitemap.xml.twig',
                                       array( 'urls' => $urls, 'hostname' => $hostname ) );
            // Store data in cache
            $item->set( $data, $TTL );
        }
*/
        $response = new Response;
        $response->setPublic();
        $response->setSharedMaxAge( $ttl );
//        $response->headers->set( 'X-Location-Id', $rootLocationId );

        return $this->render(
            'CjwPublishToolsBundle::sitemap.xml.twig',
            array( 'urls' => $urls, 'hostname' => $hostname ),
            $response
        );
    }

    public function robotsAction()
    {
        return $this->render(
            'CjwPublishToolsBundle::robots.txt.twig',
            array( 'hostname' => $this->getRequest()->getHost() )
        );
    }
}