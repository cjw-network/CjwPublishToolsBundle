<?php

namespace Cjw\PublishToolsBundle\Controller;

use eZ\Bundle\EzPublishCoreBundle\Controller;

use eZFunctionHandler;

class SearchController extends Controller
{
    public function indexAction()
    {
        $searchText = $this->getRequest()->query->get( 'SearchText', '' );
        $offset = 0;
        $limit = 7;
// ToDo: settings
        if($searchText != '')
        {
            $subtree = $this->getConfigResolver()->getParameter( 'content.tree_root.location_id' );
            $limit = $this->getConfigResolver()->getParameter( 'search.limit', 'todo' );

            $viewParameters = $this->getRequest()->attributes->get( 'viewParameters' );
            if ( isset( $viewParameters['offset'] ) )
            {
                $offset = $viewParameters['offset'];
            }

            $resultList = $this->getLegacyKernel()->runCallback(
                function () use ( $searchText, $subtree, $offset, $limit )
                {
                    return eZFunctionHandler::execute( 'content', 'search', array( 'text'          => $searchText,
                                                                                   'subtree_array' => array( $subtree ),
                                                                                   'offset'        => $offset,
                                                                                   'limit'         => $limit,
                                                                                   'section_id'    => array( 1 ),
                                                                                   'class_id'      => array( 16 ),
//                                                                                   'sort_by'       => array( array( 'attribute', false, '188' ),
//                                                                                                             array( 'published', false ) ) ) );
                                                                                   'sort_by'       => array( 'published', false ) ) );
                }
            );

            $searchResult = array();
            $searchResult['searchHits'] = $resultList['SearchResult'];
            $searchResult['totalCount'] = $resultList['SearchCount'];
        }
        else
        {
            $searchResult = false;
        }

        return $this->render(
            'CjwPublishToolsBundle:full:search.html.twig',
            array(
                'results' => $searchResult,
                'searchText' => $searchText,
                'offset' => $offset,
                'limit' => $limit
            )
        );
    }
}