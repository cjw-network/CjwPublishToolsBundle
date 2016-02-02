<?php
/**
 * File containing the PublishToolsService class
 *
 * @copyright Copyright (C) 2007-2015 CJW Network - Coolscreen.de, JAC Systeme GmbH, Webmanufaktur. All rights reserved.
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
use eZ\Publish\Core\MVC\ConfigResolverInterface;

/**
 * PublishToolsService class.
 *
 * This class contains the main functionality for the PublishToolsService, which is bundled with the
 * PublishToolsBundle.
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
     * @var \eZ\Publish\API\Repository\UserService
     */
    protected $userService;

    /**
     * @var \eZ\Publish\API\Repository\ContentTypeService
     */
    protected $contentTypeService;

    /**
     * @var \eZ\Publish\Core\MVC\ConfigResolverInterface
     */
    protected $configResolver;

    /**
     * @param \eZ\Publish\API\Repository\Repository $repository
     * @param ConfigResolverInterface|\Psr\Log\LoggerInterface $resolver
     */
    public function __construct( Repository $repository, ConfigResolverInterface $resolver )
    {
        $this->repository = $repository;
        $this->searchService = $this->repository->getSearchService();
        $this->locationService = $this->repository->getLocationService();
        $this->contentService = $this->repository->getContentService();
        $this->languageService = $this->repository->getContentLanguageService();
        $this->userService = $this->repository->getUserService();
        $this->contentTypeService = $this->repository->getContentTypeService();
        $this->configResolver = $resolver;
    }

    /**
     * Fetches a list of locations for a given parent location and returns them ready to be used for
     * a breadcrumb navigation.
     *
     * @param integer $locationId ID of the location, whose children should be included in the
     *                            returned path array.
     * @param array $params The fetching parameters, which should be used to fetch the path arrays
     *                      children from the location service – given as an array.
     *
     * The following default parameters are set:
     * <pre>
     *  array(
     *      'offset'    => 0,
     *      'rootName'  => false,
     *      'separator' => ''
     *  )
     * </pre>
     *
     * @return array Path array containing the given (or default) separator and the fetched items.
     *
     * The array is defined like so:
     * <pre>
     *  array(
     *      'items'     => array( ... !TODO! .. ),
     *      'separator' => ''
     *  );
     * </pre>
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
                        $contentId =  $parentLocation->contentInfo->id;
                        $content = $this->loadContentById( $contentId );

                        // Get the default language code for the siteaccess, so we can load the
                        // correct name by using this language code for getting the "name" fields
                        // value.
                        // ToDo: Find a simpler way for getting the correct language. See: vendor/ezsystems/ezpublish-kernel/eZ/Publish/Core/MVC/Symfony/Templating/Twig/Extension/ContentExtension.php:getTranslatedContentName
                        $languageCode = $this->languageService->getDefaultLanguageCode();

                        // We're getting the name of the object in the language it has been created,
                        // in other words the language in which the object has been initially
                        // created
                        $translatedName = $content->versionInfo->getName();

                        // Now we're going to get the field value in the default language, by using
                        // the $languageCode, which we've fetched previously.
                        $translatedNameTemp = $content->getFieldValue( 'name', $languageCode );
                        // If the translatedNameTemp has correctly been set, we're going to use the
                        // text of the field value to get the "real" translated name!
                        if( isset( $translatedNameTemp ) && is_object( $translatedNameTemp ) )
                        {
                            $translatedName = $translatedNameTemp->text;
                        }

//                        $name = $parentLocation->contentInfo->name;
                        $name = $translatedName;
                    }

                    $pathArr[$parentLocationId] = array(
                        'name' => $name,
                        'locationId' => $parentLocationId,
                        'location' => $parentLocation
                    );

                    unset( $parentLocation );
                }
            }
        }

        $result = array( 'items' => $pathArr, 'separator' => $separator );

        return $result;
    }

    /**
     * Return the user object of the current user as well as an information, whether the user is
     * logged in.
     *
     * @return array Array containing the user object and a boolean, whether the user is logged in.
     * <pre>
     *  array(
     *      'content'  => Values\User\User object,
     *      'isLogged' => false
     *  )
     * </pre>
     */
    public function getCurrentUser()
    {
        $currentUser = $this->repository->getCurrentUser();

        $result = array();
//        $result['versionInfo'] = $currentUser->versionInfo;
        $result['content'] = $currentUser;
        $result['isLogged'] = false;

        // TODO => deprecated function call *loadAnonymousUser()*
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
     * @param integer $contentId ID of the content object
     * @return \eZ\Publish\API\Repository\Values\Content\Content
     */
    public function loadContentById( $contentId )
    {
        try {
            return $this->contentService->loadContent( $contentId );
        } catch( \eZ\Publish\API\Repository\Exceptions\UnauthorizedException $error ) {
            return false;
        } catch( \eZ\Publish\API\Repository\Exceptions\NotFoundException $error ) {
            return false;
        }
    }

    /**
     * Load contentInfo by contentId.
     *
     * @param integer $contentId ID of the content object
     * @return \eZ\Publish\API\Repository\Values\Content\Content
     */
    public function loadContentInfoById( $contentId )
    {
        try {
            return $this->contentService->loadContentInfo( $contentId );
        } catch( \eZ\Publish\API\Repository\Exceptions\UnauthorizedException $error ) {
            return false;
        } catch( \eZ\Publish\API\Repository\Exceptions\NotFoundException $error ) {
            return false;
        }
    }

    /**
     * Returns list of locations under given parent location
     *
     * Note, that you have to set the *depth* parameter to "1" or higher, if you want to fetch
     * children of objects.
     *
     * @param array $locationIdArr Plain array of location ID's, which should be used to fetch the
     *                             location list array.
     * @param array $params Parameters to control the fetch for the location list.
     * <pre>
     *  array(
     *      'depth'   => integer,
     *      'datamap' => boolean
     *  )
     * </pre>
     *
     * @return array
     */
    public function fetchLocationListArr( array $locationIdArr = array(), array $params = array() )
    {
        $locationListArr = array();

        foreach ( $locationIdArr as $locationId )
        {
            $locationObj = $this->locationService->loadLocation( $locationId );

            if ( isset( $params['depth'] ) && $params['depth'] > 0 )
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
     * Fetches a subtree for a given location object.
     *
     * @param mixed $locationObj
     * @param array $params
     *
     * @return array
     */
    private function fetchSubtree( $locationObj, array $params = array() )
    {
        $criterion = array();

        // ToDo: ignore visibility
        $criterion[] = new Criterion\Visibility( Criterion\Visibility::VISIBLE );

        $locationList = array();

        // List fetch direct children
        $paramDepth = 1;

        if ( isset( $params['depth'] ) )
        {
            $paramDepth = (int) $params['depth'];
        }

        // list fetch use parentNodeId for faster fetch
        if ( $paramDepth <= 1 )
        {
            $depth = $locationObj->depth + $paramDepth;
            // http://share.ez.no/blogs/thiago-campos-viana/ez-publish-5-tip-search-cheat-sheet
            $criterion[] = new Criterion\ParentLocationId( $locationObj->id );
        }
/*
            // idee um 1 + 2 ebene ohne einen like fetch zu erhalten
                //        elseif( $paramDepth == 2 )
                //        {
                //            $params2 = array();
                //            $params2['depth'] = 1;
                //            $params2['count'] = false;
                //            $params2['datamap'] = false;
                //
                //            $locationDepth1Result = $this->fetchSubtree( $locationObj, $params2 );
                //
                //            $location1List = $locationDepth1Result['searchResult'];
                //
                //            $location1IdList = array();
                //            foreach( $location1List as $location1 )
                //            {
                //                $location1IdList[] = $location1->id;
                //            }
                //          //  var_dump( $locationList );
                //
                //            $depth = $locationObj->depth + $paramDepth;
                //            // http://share.ez.no/blogs/thiago-campos-viana/ez-publish-5-tip-search-cheat-sheet
                //            $criterion = array(
                //                new Criterion\Visibility( Criterion\Visibility::VISIBLE ),
                //                new Criterion\ParentLocationId( $location1IdList ),
                //            );
                //
                //        }
*/
        // use pathsting with like statement
        else
        {
            $depth = $locationObj->depth + $paramDepth;
            // http://share.ez.no/blogs/thiago-campos-viana/ez-publish-5-tip-search-cheat-sheet
            $criterion[] = new Criterion\Subtree( $locationObj->pathString );
            $criterion[] = new Criterion\Location\Depth( Criterion\Operator::GT, $locationObj->depth );
            $criterion[] = new Criterion\Location\Depth( Criterion\Operator::LTE, $depth );
        }

        // main_node_only => true => only include mainlocatione
        if ( isset( $params['main_location_only'] ) )
        {
            // true, 1, '1'
            if ( (int) $params['main_location_only'] === 1 )
            {
                // only include mainLocations
                $criterion[] = new Criterion\Location\IsMainLocation( Criterion\Location\IsMainLocation::MAIN );
            }
        }

        if ( isset( $params['include'] ) && is_array( $params['include'] ) && count( $params['include'] ) > 0 )
        {
            $criterion[] = new Criterion\ContentTypeIdentifier( $params['include'] );

// TODO get ID for Identifier and use this for search so we save a subrequest and its lots faster
//            if ( in_array( 'article', $params['include'] ) )
//            {
//                $criterion[] = new Criterion\ContentTypeId( array( 16 ) );
//            }
//            else
//            {
//                $criterion[] = new Criterion\ContentTypeIdentifier( $params['include'] );
//            }
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
            foreach ( $params['sortby'] as $sortKey => $sortArray )
            {
                // deprecated, for backwards compability (sort by content meta / attribute)
                if ( is_array( $sortArray ) === false )
                {
                    $newSortClause = $this->generateSortClauseFromString( $sortKey, $sortArray );

                    if ( $newSortClause !== false )
                    {
                        $sortClauses[] = $newSortClause;
                    }
                }

                // 2 array items means sorts by content meta / attribute
                if ( is_array( $sortArray ) && count( $sortArray ) == 2 )
                {
                    $newSortClause = $this->generateSortClauseFromString( $sortArray['0'], $sortArray['1'] );

                    if ( $newSortClause !== false )
                    {
                        $sortClauses[] = $newSortClause;
                    }
                }

                // 4 array items means sorts by content field
                if ( is_array( $sortArray ) && count( $sortArray ) == 4 )
                {
                    $sortOrder = 'ascending';
                    if ( $sortArray['2'] == 'DESC' || $sortArray['2'] == 'descending' )
                    {
                        $sortOrder = 'descending';
                    }

//                    $lang = $this->getPrioritizedLanguages();
                    $sortClauses[] = new SortClause\Field( $sortArray['0'], $sortArray['1'], $sortOrder, $sortArray['3'] );
                }
            }
        }
        else
        {
            // default sort by parent object sort clause
            $sortClauses[] = $this->generateSortClauseFromId( $locationObj->sortField, $locationObj->sortOrder );
        }

        /* vendor/ezsystems/ezpublish-kernel/eZ/Publish/API/Repository/Values/Content/Query/Criterion/Operator.php
        abstract class Operator
        {
            const EQ = "=";
            const GT = ">";
            const GTE = ">=";
            const LT = "<";
            const LTE = "<=";
            const IN = "in";
            const BETWEEN = "between";
            const LIKE = "like";
            const CONTAINS = "contains";
        }
        */

        if ( isset( $params['filter_relations'] ) && is_array( $params['filter_relations'] ) && count( $params['filter_relations'] ) > 0 )
        {
            foreach ( $params['filter_relations'] as $fieldCriterion )
            {
// todo check valid syntax
                if ( is_array( $fieldCriterion ) && count( $fieldCriterion ) == 3 )
                {
                    $criterion[] = new Criterion\FieldRelation( $fieldCriterion['0'], $fieldCriterion['1'], $fieldCriterion['2'] );
                }
            }
        }

        if ( isset( $params['filter_fields'] ) && is_array( $params['filter_fields'] ) && count( $params['filter_fields'] ) > 0 )
        {
            foreach ( $params['filter_fields'] as $fieldCriterion )
            {
// todo check valid syntax
                if ( is_array( $fieldCriterion ) && count( $fieldCriterion ) == 3 )
                {
                    // Attention! criterion field must set to be searchable
                    $criterion[] = new Criterion\Field( $fieldCriterion['0'], $fieldCriterion['1'], $fieldCriterion['2'] );
                }
            }
        }

        if ( isset( $params['language'] ) && is_array( $params['language'] ) && count( $params['language'] ) > 0 )
        {
// ToDo: combine with and, always available?
            $criterion[] = new Criterion\LanguageCode( $params['language'] );
        }
        else
        {
            // only include languages which are defined in siteaccess config  system default|siteaccess languages
            // if more than on a correct bitmask is build to include all object where one of the language is set
            $criterion[] = new Criterion\LanguageCode( $this->getPrioritizedLanguages() );
        }

        $querySearch = new LocationQuery( array( 'offset' => $offset, 'limit' => $limit ) );
        $querySearch->criterion = new Criterion\LogicalAnd( $criterion );
        $querySearch->sortClauses = $sortClauses;
        $searchResult = $this->searchService->findLocations( $querySearch );

        $searchCount = false;
        // only if count parameter is set return count
        // in newer ez version > 5.4 and in 5.3.5 the count is optional so you can
        // save 1 sql query
        if ( isset( $params['count'] ) && $params['count'] === true )
        {
            // use count from searchResult => the fastest way :-)
            $searchCount = $searchResult->totalCount;
        }

        foreach ( $searchResult->searchHits as $searchItem )
        {
            $childLocationId = $searchItem->valueObject->id;

            if ( isset( $params['datamap'] ) && $params['datamap'] === true )
            {
                $childContentId = $searchItem->valueObject->contentInfo->id;
                $locationList[] = $this->contentService->loadContent( $childContentId );
            }
            else
            {
                $locationList[] = $searchItem->valueObject;
            }
        }

        return array( 'searchResult' => $locationList, 'searchCount' => $searchCount );
    }

    /**
     * Generate a sort clause depending on the location's sort fields (adapted from Donat's
     * AbstractController.php)
     *
     * @param integer $sortField Numerous representation of the sort field (use "SORT_FIELD_…"
     *                           constants)
     * @param string $sortOrder LocationQuery::SORT_ASC or LocationQuery::SORT_DESC
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
     * Generates a sort clause, depending on the given sort field and sort order parameters.
     *
     * @param string $sortField Sort field, which should be used for sorting. Can be one of the
     *                          following:
     * <pre>
     *  - 'LocationPath'
     *  - 'LocationDepth'
     *  - 'LocationPriority'
     *  - 'ContentName'
     *  - 'ContentId'
     *  - 'DateModified'
     *  - 'DatePublished'
     * </pre>
     * @param string $sortOrder Sort order, which should be used for sorting. Can be <b>'ASC'</b> or
     *                          <b>'DESC'</b>.
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
                $result = new SortClause\ContentId( $sortOrder );
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

    /**
     * Fetches the string representation of a given content type ID (content type identifier).
     *
     * @param integer $contentTypeId Numerous ID of the content type
     * @return bool|string False on error or a string representation of the given content type ID.
     */
    public function getContentTypeIdentifier( $contentTypeId )
    {
        $cs = $this->contentTypeService;

        try {
            $ct = $cs->loadContentType($contentTypeId);
            $contentTypeIdentifier = $ct->identifier;
            return $contentTypeIdentifier;
        } catch( \eZ\Publish\API\Repository\Exceptions\NotFoundException $error ) {
            return false;
        }
    }

    /**
     * Returns an array of the priorized languages codes defined in YAML settings.
     *
     * <pre>
     *  system:
     *     default|siteaccess:
     *        languages: [ger-DE,eng-GB]
     * </pre>
     * @return array An array containing the proriozed languages code from YAML as follows:
     * <pre>
     *  array(
     *      'languageCode1',
     *      'languageCode2'
     *  )
     * </pre>
     */
    public function getPrioritizedLanguages()
    {
        $languages = $this->configResolver->getParameter( 'languages' );

        return $languages;
    }
}
