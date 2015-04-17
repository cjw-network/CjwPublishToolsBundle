<?php
/**
 * File containing the PublishToolsService class
 *
 * @copyright Copyright (C) 2007-2014 CJW Network - Coolscreen.de, JAC Systeme GmbH, Webmanufaktur. All rights reserved.
 * @license http://ez.no/licenses/gnu_gpl GNU GPL v2
 * @version //autogentag//
 * @filesource
 *
 */

namespace Cjw\PublishToolsBundle\Services;

use eZ\Publish\API\Repository\Repository;
use eZ\Publish\API\Repository\Values\Content\Query;
use eZ\Publish\API\Repository\Values\Content\Query\SortClause;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion;
use eZ\Publish\API\Repository\Values\Content\Location;
use eZ\Publish\API\Repository\Values\Content\LocationQuery;

/**
 * 
 */
class PublishToolsService
{
    /**
     * @var \eZ\Publish\API\Repository\Repository
     */
    protected $repository;

    /**
     * @var \eZ\Publish\API\Repository\LocationService
     */
    protected $locationService;

    /**
     * @var \eZ\Publish\API\Repository\SearchService
     */
    protected $searchService;

    /**
     * @var \eZ\Publish\API\Repository\ContentService
     */
    protected $contentService;

    /**
     * @var \eZ\Publish\API\Repository\LanguageService
     */
    protected $languageService;

    /**
     * @var \eZ\Publish\API\Repository\LanguageService
     */
    protected $userService;

    /**
     * @param \eZ\Publish\API\Repository
     * @param \Psr\Log\LoggerInterface
     */
    public function __construct( Repository $repository )
    {
        $this->repository = $repository;
        $this->searchService = $this->repository->getSearchService();
        $this->locationService = $this->repository->getLocationService();
        $this->contentService = $this->repository->getContentService();
        $this->languageService = $this->repository->getContentLanguageService();
        $this->userService = $this->repository->getUserService();
    }

    /**
     * Returns a list of locations for an given parent location (breadcrumb)
     *
     * @param integer $locationId
     * @param array $params
     *
     * @return array
     */
    public function getPathArr( $locationId = 0, array $params = array() )
    {
        $pathArr   = array();
        $offset    = 0;
        $rootName  = false;
        $separator = '';

        if ( isset( $params['offset'] ) )
        {
            $offset = $params['offset'];
        }

        if ( isset( $params['rootName'] ) && trim( $params['rootName'] ) != '' )
        {
            $rootName = $params['rootName'];
        }

        if ( isset( $params['separator'] ) )
        {
            $separator = $params['separator'];
        }

        $location = $this->locationService->loadLocation( $locationId );

        $counter = 0;
        foreach ( $location->path as $key => $parentLocationId )
        {
            if ( $parentLocationId > 1 )
            {
                if ( $key > $offset )
                {
                    $counter++;
                    $parentLocation = $this->locationService->loadLocation( $parentLocationId );

                    if ( $counter == 1 && $rootName !== false )
                    {
                        $name = $rootName;
                    }
                    else
                    {
                        $name = $parentLocation->contentInfo->name;
                    }

                    $pathArr[$parentLocationId] = array(
                        'name' => $name,
                        'locationId' => $parentLocationId
                    );

                    unset( $parentLocation );
                }
            }
        }

        $result = array( 'items' => $pathArr,
                         'separator' => $separator );

        return $result;
    }

    /**
     * Returns the current user object
     *
     * @return array
     */
    public function getCurrentUser()
    {
        $currentUser = $this->repository->getCurrentUser();

        $result = array();
        $result['versionInfo'] = $currentUser->versionInfo;
        $result['content'] = $currentUser->content->fields;
        $result['isLogged'] = false;

        $anonymousUserId = $this->userService->loadAnonymousUser()->content->versionInfo->contentInfo->id;

        if ( $anonymousUserId && $anonymousUserId != $currentUser->id )
        {
            $result['isLogged'] = true;
        }

        return $result;
    }

    /**
     * Returns the current language object
     *
     * @return array
     */
    public function getDefaultLangCode()
    {
        return $this->languageService->getDefaultLanguageCode();
    }

    /**
     * Fetch content by contentId.
     *
     * @param integer $contentId
     * @return \eZ\Publish\API\Repository\Values\Content\Content
     */
    public function loadContentById( $contentId )
    {
        return $this->contentService->loadContent( $contentId );
    }

    /**
     * Returns list of locations under given parent location
     *
     * @param array $locationIdArr
     * @param array $params
     *
     * @return array
     */
    public function fetchLocationListArr( array $locationIdArr = array(), array $params = array() )
    {
        $locationListArr = array();

        foreach ( $locationIdArr as $locationId )
        {
            $locationObj = $this->locationService->loadLocation( $locationId );

            if( isset( $params['depth'] ) && $params['depth'] > 0 )
            {
                $locationList = $this->fetchSubtree( $locationObj, $params );

                $locationListArr[$locationId] = array();

                $locationListArr[$locationId]['parent'] = false;
                $locationListArr[$locationId]['children'] = $locationList['searchResult'];
                $locationListArr[$locationId]['count'] = $locationList['searchCount'];

                unset( $locationList );
            }
            else
            {
                if ( isset( $params['datamap'] ) && $params['datamap'] === true )
                {
                    $locationListArr[$locationId] = array( $this->contentService->loadContent( $locationObj->contentInfo->id ) );
                }
                else
                {
                    $locationListArr[$locationId] = array( $locationObj );
                }
            }
        }

        return $locationListArr;
    }

    /**
     * Returns a subtree for an location object
     *
     * @param mixed $locationObj
     * @param array $params
     *
     * @return array
     */
    private function fetchSubtree( $locationObj, array $params = array() )
    {
        $locationList = array();
        $depth = $locationObj->depth + $params['depth'];

        // http://share.ez.no/blogs/thiago-campos-viana/ez-publish-5-tip-search-cheat-sheet
        $criterion = array(
            new Criterion\Visibility( Criterion\Visibility::VISIBLE ),
            new Criterion\Subtree( $locationObj->pathString ),
            new Criterion\Location\Depth( Criterion\Operator::GT, $locationObj->depth ),
            new Criterion\Location\Depth( Criterion\Operator::LTE, $depth )
        );

        if ( isset( $params['include'] ) && is_array( $params['include'] ) && count( $params['include'] ) > 0 )
        {
            $criterion[] = new Criterion\ContentTypeIdentifier( $params['include'] );
        }

// ToDo: role and rights, visibility, date, object states criterion

        $offset = 0;
        if ( isset( $params['offset'] ) && $params['offset'] > 0 )
        {
            $offset = $params['offset'];
        }

        $limit = null;
        if ( isset( $params['limit'] ) && $params['limit'] > 0 )
        {
            $limit = $params['limit'];
        }

        $sortClauses = array();
        if ( isset( $params['sortby'] ) && is_array( $params['sortby'] ) && count( $params['sortby'] ) > 0 )
        {
            foreach ( $params['sortby'] as $sortField => $sortOrder )
            {
                $newSortClause = $this->generateSortClauseFromString( $sortField, $sortOrder );

                if ( $newSortClause !== false )
                {
                    $sortClauses[] = $newSortClause;
                }
            }
        }
        else
        {
            // default sort by parent object sort clause
            $sortClauses[] = $this->generateSortClauseFromId( $locationObj->sortField, $locationObj->sortOrder );
        }

        if ( isset( $params['language'] ) && is_array( $params['language'] ) && count( $params['language'] ) > 0 )
        {
            // ToDo: combine with and, always available?
//            $criterion[] = new Criterion\LangueCode( $params['language'] );
        }
        else
        {
            // get the default language
            $defaultLanguageCode = $this->getDefaultLangCode();
            $criterion[] = new Criterion\LanguageCode( $defaultLanguageCode );
        }

        // search count
        // https://doc.ez.no/display/EZP/2.+Browsing,+finding,+viewing#id-2.Browsing,finding,viewing-Performingapuresearchcount
        $searchCount = false;
        if ( isset( $params['count'] ) && $params['count'] === true )
        {
            $queryCount = new LocationQuery( array() );
            $queryCount->criterion = new Criterion\LogicalAnd( $criterion );
            $queryCount->sortClauses = $sortClauses;
            $searchCount = $this->searchService->findLocations( $queryCount )->totalCount;
        }

        $querySearch = new LocationQuery( array( 'offset' => $offset, 'limit' => $limit ) );
        $querySearch->criterion = new Criterion\LogicalAnd( $criterion );
        $querySearch->sortClauses = $sortClauses;
        $searchResult = $this->searchService->findLocations( $querySearch );

        foreach ( $searchResult->searchHits as $searchItem )
        {
            if ( isset( $params['datamap'] ) && $params['datamap'] === true )
            {
                $childContentId = $searchItem->valueObject->contentInfo->id;
                $locationList[] = $this->contentService->loadContent( $childContentId );
            }
            else
            {
                $childLocationId = $searchItem->valueObject->contentInfo->mainLocationId;
                $locationList[] = $this->locationService->loadLocation( $childLocationId );
            }
        }

        return array( 'searchResult' => $locationList, 'searchCount' => $searchCount );
    }

    /**
     * Generate a sort clause depending on the location's sort fields (adapted from Donat's AbstractController.php)
     *
     * @param $sortField
     * @param $sortOrder
     *
     * @return SortClause\ContentId|SortClause\ContentName|SortClause\DateModified|SortClause\DatePublished|SortClause\LocationDepth|SortClause\LocationPathString|SortClause\LocationPriority|SortClause\SectionIdentifier
     */
    private function generateSortClauseFromId( $sortField, $sortOrder )
    {
        $sortOrder = ( $sortOrder ) ? LocationQuery::SORT_ASC : LocationQuery::SORT_DESC;

        /*
            const SORT_FIELD_PATH = 1;
            const SORT_FIELD_PUBLISHED = 2;
            const SORT_FIELD_MODIFIED = 3;
            const SORT_FIELD_SECTION = 4;
            const SORT_FIELD_DEPTH = 5;
            const SORT_FIELD_CLASS_IDENTIFIER = 6;
            const SORT_FIELD_CLASS_NAME = 7;
            const SORT_FIELD_PRIORITY = 8;
            const SORT_FIELD_NAME = 9;
            const SORT_FIELD_MODIFIED_SUBNODE = 10;
            const SORT_FIELD_NODE_ID = 11;
            const SORT_FIELD_CONTENTOBJECT_ID = 12;
        */

        switch ( $sortField )
        {
            case Location::SORT_FIELD_PATH:
                return new SortClause\Location\Path( $sortOrder );
            case Location::SORT_FIELD_PUBLISHED:
                return new SortClause\DatePublished( $sortOrder );
            case Location::SORT_FIELD_MODIFIED:
                return new SortClause\DateModified( $sortOrder );
            case Location::SORT_FIELD_SECTION:
                return new SortClause\SectionIdentifier( $sortOrder );
            case Location::SORT_FIELD_DEPTH:
                return new SortClause\Location\Depth( $sortOrder );
            case Location::SORT_FIELD_PRIORITY:
                return new SortClause\Location\Priority( $sortOrder );
            case Location::SORT_FIELD_NAME:
                return new SortClause\ContentName( $sortOrder );
            case Location::SORT_FIELD_CONTENTOBJECT_ID:
                return new SortClause\ContentId( $sortOrder );
            // No matching sort clause available, create default
            case Location::SORT_FIELD_CLASS_IDENTIFIER:
            case Location::SORT_FIELD_CLASS_NAME:
            case Location::SORT_FIELD_MODIFIED_SUBNODE:
            case Location::SORT_FIELD_NODE_ID:
            default:
                return new SortClause\ContentName( Query::SORT_ASC );
        }
    }

    /**
     * Generate a sort clause depending on String Parameter
     *
     * @param $sortField
     * @param $sortOrder
     *
     * @return SortClause\ContentId|SortClause\ContentName|SortClause\DateModified|SortClause\DatePublished|SortClause\LocationDepth|SortClause\LocationPathString|SortClause\LocationPriority|SortClause\SectionIdentifier
     */
    private function generateSortClauseFromString( $sortField, $sortOrder = 'ASC' )
    {
        $result = false;

        if ( $sortOrder === 'DESC' )
        {
            $sortOrder = LocationQuery::SORT_DESC;
        }
        else
        {
            $sortOrder = LocationQuery::SORT_ASC;
        }

        switch ( $sortField )
        {
            case 'LocationPath':
                $result = new SortClause\Location\Path( $sortOrder );
                break;
            case 'LocationDepth':
                $result = new SortClause\Location\Depth( $sortOrder );
                break;
            case 'LocationPriority':
                $result = new SortClause\Location\Priority( $sortOrder );
                break;
            case 'ContentName':
                $result = new SortClause\ContentName( $sortOrder );
                break;
            case 'ContentId':
                $result = new SortClause\Id( $sortOrder );
                break;
            case 'DateModified':
                $result = new SortClause\DateModified( $sortOrder );
                break;
            case 'DatePublished':
                $result = new SortClause\DatePublished( $sortOrder );
                break;
        }

        return $result;
    }
}
