<?php

namespace Cjw\PublishToolsBundle\Controller;

use eZ\Bundle\EzPublishCoreBundle\Controller;
use Symfony\Component\HttpFoundation\Response;

// todo: get available languages, config item for include types

class SitemapsController extends Controller
{
    public function sitemapAction()
    {
        $ttl = 3600 * 6;

        $response = new Response;
        $response->headers->set( 'Content-Type', 'text/xml' );
        $response->setPublic();
        $response->setSharedMaxAge( $ttl );

        $siteaccessName = $this->container->get('ezpublish.siteaccess')->name;
        $cacheFile = $this->container->getParameter( 'kernel.cache_dir' ) . '/sitemap_'.$siteaccessName.'.xml';

        $cacheFileMtime = 0;
        if ( file_exists( $cacheFile ) )
        {
            $cacheFileMtime = stat( $cacheFile )['mtime'];
        }

        if ( $cacheFileMtime < ( time() - $ttl ) )
        {
            $urls = array();
            $hostname = $this->getRequest()->getHost();

            $publishToolsService = $this->get( 'cjw_publishtools.service.functions' );

            $rootLocationId = $this->getConfigResolver()->getParameter('content.tree_root.location_id');

            $listLocations = $publishToolsService->fetchLocationListArr(
                array( $rootLocationId ), array( 'depth' => 10,
                                  'limit' => 25000,
//                                  'include' => array( 'article' ),
                                  'main_location_only' => true,
                                  'datamap' => false )
            );

            foreach( $listLocations[$rootLocationId]['children'] as $location )
            {
                $loc = $this->generateUrl( $location );
                $lastmod = date( 'c', $location->contentInfo->modificationDate->getTimestamp() );

                $urls[] = array( 'loc' => $loc, 'lastmod' => $lastmod );
            }

            $sitemapXmlResponse = $this->render(
                'CjwPublishToolsBundle::sitemap.xml.twig',
                array( 'urls' => $urls, 'hostname' => 'https://'.$hostname ),
                $response
            );

            file_put_contents( $cacheFile, $sitemapXmlResponse->getContent() );
        }
        else
        {
            $sitemapXmlResponse = $response->setContent( file_get_contents( $cacheFile ) );
        }

        return $sitemapXmlResponse;
    }

    public function robotsAction()
    {
        return $this->render(
            'CjwPublishToolsBundle::robots.txt.twig',
            array( 'hostname' => 'https://'.$this->getRequest()->getHost() )
        );
    }
}