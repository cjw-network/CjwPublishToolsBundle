<?php
/**
 * File containing the FormularController class
 *
 * @copyright Copyright (C) 2007-2015 CJW Network - Coolscreen.de, JAC Systeme GmbH, Webmanufaktur. All rights reserved.
 * @license http://ez.no/licenses/gnu_gpl GNU GPL v2
 * @version //autogentag//
 * @filesource
 */

namespace Cjw\PublishToolsBundle\Controller;

use Cjw\PublishToolsBundle\Services\FormHandlerService;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use eZ\Bundle\EzPublishCoreBundle\Controller;

class FormularController extends Controller
{
    protected $initialLanguageCode;
    protected $repository;
    protected $request;
    protected $contentService;
    protected $locationService;
    protected $contentTypeService;
    protected $formBuilderService;
    protected $formHandlerService;
    protected $identifier;
    protected $userService;

    public function __construct()
    {
        //
    }

    /**
     * initialize needed services
     */
    private function getServices()
    {
        $this->repository          = $this->getRepository();
        $this->request             = $this->getRequest();
        $this->contentService      = $this->repository->getContentService();
        $this->locationService     = $this->repository->getLocationService();
        $this->contentTypeService  = $this->repository->getContentTypeService();
        $this->formBuilderService  = $this->get( 'cjw_publishtools.formbuilder.functions' );
        $this->formHandlerService  = $this->get( 'cjw_publishtools.formhandler.functions' );
        $this->initialLanguageCode = $this->repository->getContentLanguageService()->getDefaultLanguageCode();
        $this->userService         = $this->repository->getUserService();
    }

    /**
     * Renders a form from an content object with fields marked "isInfocollector"
     *
     * @param int $locationId
     * @param string $viewType
     * @param bool $layout
     * @param array $params
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function formCollectorAction( $locationId, $viewType, $layout = false, $params = array() )
    {
        $this->getServices();

        $location    = $this->locationService->loadLocation( $locationId );
        $content     = $this->contentService->loadContent( $location->contentId );
        $contentInfo = $content->contentInfo;
        $contentType = $this->contentTypeService->loadContentType( $contentInfo->contentTypeId );

        $formParams  = $this->formBuilderService->getFormConfigType( 'parameters', 'formcollector_config', $contentType->identifier );
        $formSchema  = $this->formBuilderService->getFormSchemaFromContentObjectFields( $contentType, $this->initialLanguageCode, $content, $isCollector = true );

        $form = $this->formBuilderService->formBuilder( $formSchema['0'], $formSchema['1'], $this->initialLanguageCode, $formParams );

        // http://api.symfony.com/2.6/Symfony/Component/Form/FormInterface.html
        if ( $this->request->isMethod( 'POST' ) )
        {
            $form->handleRequest( $this->request );

            $requestForm = $this->request->request->get('form');
            if ( isset( $requestForm['cancel'] ) )
            {
                // ToDo: cancel redirect
                $redirectUrl = '/content/location/'.$locationId;
                return new RedirectResponse( $redirectUrl );
            }

            if ( $form->isValid() )
            {
                // process configured handlers
                $handlerParameters = array( 'location'        => $location,
                                            'content'         => $content,
                                            'contentObjectId' => $content->id,
                                            'contentType'     => $contentType->fieldDefinitionsByIdentifier,
                                            'currentUserId'   => $this->repository->getCurrentUser()->id );

                $response = $this->processHandlers( 'formcollector_config'.':'.$contentType->identifier, $form->getData(), $handlerParameters, $form );

                if ( $response !== false )
                {
                    // ToDo: dealing with cache ttl ?
                    return $response;
                }
            }
        }

        $params['location'] = $location;
        $params['content'] = $content;
        $params['form'] = $form->createView();

        foreach($params['form']->vars['value'] as $contentTypeIdentifier => $dataType)
        {
            //e.g ezstring___lastname
            $FullContentTypeName = explode(FormHandlerService::separator,$contentTypeIdentifier);
            //e.g ezstring
            $ezDataType = $FullContentTypeName[0];

            //e.g lastname
            $identifierName = $FullContentTypeName[1];

            //e.g on Twig {{ getFullContentName['lastname'] }} equals 'ezstring__lastname'
            $params['getFullContentName'][$identifierName] = $contentTypeIdentifier;
        }

        return $this->get( 'ez_content' )->viewLocation( $locationId, $viewType, $layout, $params );
    }

    /**
     * Renders a form from an config with $identifier in formbuilder.yml
     *
     * @param string $identifier
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function formBuilderAction( $identifier )
    {
        $this->getServices();
        $this->identifier = $identifier;

        $formSchema = $this->formBuilderService->getFormSchemaFromYamlConfig( $identifier );
        $formParams = $this->formBuilderService->getFormConfigType( 'parameters', 'formbuilder_config', $identifier );

        $form = $this->formBuilderService->formBuilder( $formSchema['0'], $formSchema['1'], $this->initialLanguageCode, $formParams );

        if ( $this->request->isMethod( 'POST' ) )
        {
            $form->handleRequest( $this->request );

            $requestForm = $this->request->request->get('form');
            if ( isset( $requestForm['cancel'] ) )
            {
                // ToDo: cancel redirect
                $redirectUrl = '';
                return new RedirectResponse( $redirectUrl );
            }

            if ( $form->isValid() )
            {
                // process configured handlers
                $response = $this->processHandlers( 'formbuilder_config'.':'.$identifier, $form->getData(), array(), $form );

                if ( $response !== false )
                {
                    // ToDo: dealing with cache ttl ?
                    return $response;
                }
            }
        }

        return $this->buildResponse( $form, $formParams, false, false );
    }

    /**
     * Renders a form from an content object for frontend editing
     *
     * @param int $locationid
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function editFormAction( $locationid = 0 )
    {
        $locationId = $locationid;
        $this->getServices();

        $location    = $this->locationService->loadLocation( $locationId );
        $content     = $this->contentService->loadContent( $location->contentId );
        $contentInfo = $content->contentInfo;
        $contentType = $this->contentTypeService->loadContentType( $contentInfo->contentTypeId );

        $formSchema  = $this->formBuilderService->getFormSchemaFromContentObjectFields( $contentType, $this->initialLanguageCode, $content );
        $formParams  = $this->formBuilderService->getFormConfigType( 'parameters', 'frontendediting_config', $contentType->identifier );

        $form = $this->formBuilderService->formBuilder( $formSchema['0'], $formSchema['1'], $this->initialLanguageCode, $formParams );

        if ( $this->request->isMethod( 'POST' ) )
        {
            $form->handleRequest( $this->request );
            $redirectUrl = '/content/location/'.$locationId;

            $requestForm = $this->request->request->get('form');
            if ( isset( $requestForm['cancel'] ) )
            {
                // ToDo: cancel redirect
                return new RedirectResponse( $redirectUrl );
            }

            if ( $form->isValid() )
            {
                $formDataObj = $form->getData();

                $this->updateContentWithFormDataHandler( $formDataObj, $contentInfo );

                return new RedirectResponse( $redirectUrl );
            }
        }

        return $this->buildResponse( $form, $formParams, $content, $location );
    }

    /**
     * Renders a form with a new content object struct for frontend editing
     *
     * @param int $locationid
     * @param int $contenttypeid
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function addFormAction( $locationid = 0, $contenttypeid = 0 )
    {
        $locationId = $locationid;
        $contentTypeId = $contenttypeid;
        $this->getServices();

        $contentType = $this->contentTypeService->loadContentType( $contentTypeId );

        $formParams  = $this->formBuilderService->getFormConfigType( 'parameters', 'frontendediting_config', $contentType->identifier );
        $formSchema  = $this->formBuilderService->getFormSchemaFromContentObjectFields( $contentType, $this->initialLanguageCode, $content = false, $locationId );

        $form = $this->formBuilderService->formBuilder( $formSchema['0'], $formSchema['1'], $this->initialLanguageCode );

        if ( $this->request->isMethod( 'POST' ) )
        {
            $form->handleRequest( $this->request );

            $requestForm = $this->request->request->get('form');
            if ( isset( $requestForm['cancel'] ) )
            {
                // ToDo: cancel redirect
                $redirectUrl = '/content/location/'.$locationId;
                return new RedirectResponse( $redirectUrl );
            }

            if ( $form->isValid() )
            {
                $formDataObj = $form->getData();

                $object = $this->addContentWithFormDataHandler( $formDataObj, $locationId, $contentType );
// ToDo:
                $mainLocationId = $object->versionInfo->contentInfo->mainLocationId;
                $redirectUrl = '/content/location/'.$mainLocationId;
                return new RedirectResponse( $redirectUrl );
            }
        }

        return $this->buildResponse( $form, $formParams, false, false );
    }

    /**
     * Renders a user register form, get the params from formbuilder.yml
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function userRegisterAction()
    {
        $this->getServices();

        $formParams = $this->formBuilderService->getFormConfigType( 'parameters', 'user_register_config', false );

        $locationId = $formParams['user_group_id'];             // ToDo: checks
        $contentTypeId = $formParams['user_contenttype_id'];    // ToDo: checks

        $contentType = $this->contentTypeService->loadContentType( $contentTypeId );

        $formSchema = $this->formBuilderService->getFormSchemaFromContentObjectFields( $contentType, $this->initialLanguageCode, $content = false, $locationId );

        $form = $this->formBuilderService->formBuilder( $formSchema['0'], $formSchema['1'], $this->initialLanguageCode, $formParams );

        if ( $this->request->isMethod( 'POST' ) )
        {
            $form->handleRequest( $this->request );

            $requestForm = $this->request->request->get('form');
            // redirect if the cancel button has been pressed
            if ( isset( $requestForm['cancel'] ) )
            {
                // Redirect to root on cancel
                // ToDo: Get redirect url from yml or template
                $redirectUrl = '/';

                return new RedirectResponse( $redirectUrl );
            }

            if ( $form->isValid() )
            {
                $formDataObj = $form->getData();

                $newUser = $this->userRegisterHandler( $formDataObj, $locationId, $contentType );

// ToDo: catch "Argument 'userCreateStruct' is invalid: User with provided login already exists" (InvalidArgumentException) exception

                if ( $newUser !== false )
                {
                    if ( $form->isValid() )
                    {
                        $app = array();
                        $app['request'] = $this->request;
                        $handlerParameters = array( 'content' => $newUser, 'app' => $app );

                        // process configured handlers
                        $response = $this->processHandlers( 'user_register_config:handlers', $form->getData(), $handlerParameters, $form );

                        if ( $response !== false )
                        {
                            // ToDo: dealing with cache ttl ?
                            return $response;
                        }
                    }

                }
            }
        }

        return $this->buildResponse( $form, $formParams, false, false );
    }

    /**
     * Builds an form response, try to match an template from formbuilder.yml, fallback to inline template if template not found
     *
     * @param mixed $form
     * @param string $formParams
     * @param \eZ\Publish\API\Repository\Values\Content\Content $content
     * @param \eZ\Publish\API\Repository\Values\Content\Location $location
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    private function buildResponse( $form, $formParams, $content = false, $location = false )
    {
        if ( isset( $formParams['template'] ) )
        {
            $template = $this->formBuilderService->getTemplateOverride( $formParams['template'] );
        }
        else
        {
            $template = false;
        }

        if ( $template !== false )
        {
            // ToDo: check if tpl exists
            // $this->get('templating')->exists('AcmeDemoBundle:Foo:bar.html.twig')
            // http://stackoverflow.com/questions/16751401/check-if-template-exists-before-rendering
        }

        if ( $template !== false )
        {
            return $this->render(
                $template,
                array( 'form' => $form->createView(), 'content' => $content, 'location' => $location )
            );
        }
        else
        {
            // https://www.techpunch.co.uk/development/render-string-twig-template-symfony2
            return new Response(
                $this->get( 'twig' )->render(
                    '<h1>no template specified or available</h1>{{ form( form ) }}',
                    array( 'form' => $form->createView() )
                )
            );
        }
    }

    /**
     * Process handler configured in formbuilder.yml, handler specific vars handed over in $parameters
     *
     * @param string $configIdentifierStr
     * @param mixed $formDataObj
     * @param mixed $handlerParameters
     *
     * @return mixed $resultAction
     */
    private function processHandlers( $configIdentifierStr, $formDataObj, $handlerParameters, $form )
    {
        $response = false;

        $configIdentifierArr = explode( ':', $configIdentifierStr );

        $handlerConfigArr = $this->formBuilderService->getFormConfigHandler( $configIdentifierArr['0'], $configIdentifierArr['1'] );

        foreach( $handlerConfigArr as $handlerConfig )
        {
            // If the handler_class is not set, we're continue with the next cycle of the loop
            if( isset( $handlerConfig['handler_class'] ) ) {
                $handlerClass = $handlerConfig[ 'handler_class' ];

                // Check whether the given $handler_class method exists in the "formHandlerService"
                // object member.
                if( method_exists( $this->formHandlerService, $handlerClass ) )
                {
                    $response = $this->formHandlerService->$handlerClass( $formDataObj, $handlerConfig, $handlerParameters, $form );
                }
            }
        }

        if ( $response !== false )
        {
            $response = new Response( $response );
        }

        return $response;
    }

    /**
     * Handle the content object update for frontend editing, returns the updated content object
     *
     * @param mixed $formDataObj
     * @param mixed $contentInfo
     *
     * @return mixed $object
     */
    // https://doc.ez.no/display/EZP/3.+Managing+Content#id-3.ManagingContent-UpdatingContent
    private function updateContentWithFormDataHandler( $formDataObj, $contentInfo )
    {
        $contentUpdateStruct = $this->contentService->newContentUpdateStruct();
        $contentUpdateStruct->initialLanguageCode = $this->initialLanguageCode;

        $contentDraft = $this->contentService->createContentDraft( $contentInfo );

        $contentStruct = $this->formBuilderService->buildContentStructWithFormData( $formDataObj, $contentUpdateStruct );

        // If user is given, we're going to create a userStruct to update the user object, too
        if ( $contentStruct['ezuser'] !== false ) {
            $user = $this->userService->loadUserByLogin( $contentStruct['ezuser']['login'] );

            $userStruct = $this->userService->newUserUpdateStruct();
            $userStruct->contentUpdateStruct = $contentUpdateStruct;

            $userStruct->email    = $contentStruct['ezuser']['email'];
            $userStruct->password = $contentStruct['ezuser']['password'];

            $user = $this->userService->updateUser( $user, $userStruct );
        }

        $contentStruct = $contentStruct['contentStruct'];

        // update and publishing
        $draft = $this->contentService->updateContent( $contentDraft->versionInfo, $contentStruct );
        $object = $this->contentService->publishVersion( $draft->versionInfo );

        return $object;
    }

    /**
     * Handle the content object adding for frontend editing, returns the added content object
     *
     * @param mixed $formDataObj
     * @param mixed $contentInfo
     * @param string $contentType
     *
     * @return mixed $object
     */
    // https://doc.ez.no/display/EZP/3.+Managing+Content#id-3.ManagingContent-Creatingcontent
    private function addContentWithFormDataHandler( $formDataObj, $locationId, $contentType )
    {
        // The ContentCreateStruct
        $contentCreateStruct = $this->contentService->newContentCreateStruct( $contentType, $this->initialLanguageCode );

        // Setting the Parent Location
        $locationCreateStruct = $this->locationService->newLocationCreateStruct( $locationId );

        $contentStruct = $this->formBuilderService->buildContentStructWithFormData( $formDataObj, $contentCreateStruct );
        $contentStruct = $contentStruct['contentStruct'];

        // creating and publishing
        $draft = $this->contentService->createContent( $contentStruct, array( $locationCreateStruct ) );
        $object = $this->contentService->publishVersion( $draft->versionInfo );

        return $object;
    }

    /**
     * Handle adding a new user content object adding for user regster action, changing the owner id to it self, getting the parameters from formbuilder.yml, returns the new user content object
     *
     * @param mixed $formDataObj
     * @param mixed $contentInfo
     * @param string $contentType
     *
     * @return mixed $object
     */
    // http://pubsvn.ez.no/doxygen/trunk/NS/html/interfaceeZ_1_1Publish_1_1API_1_1Repository_1_1UserService.html
    // http://share.ez.no/forums/ez-publish-5-platform/import-user-with-ezpublish-5
    // http://symfony.com/doc/current/cookbook/doctrine/registration_form.html
    private function userRegisterHandler( $formDataObj, $locationId, $contentType )
    {
        $formParams = $this->formBuilderService->getFormConfigType( 'parameters', 'user_register_config', false );

        if ( array_key_exists( 'admin_user_id', $formParams ) )
        {
            $adminUserId = $formParams['admin_user_id'];
        }
        else
        {
            $adminUserId = 14;
        }

        if ( array_key_exists( 'account_status_enabled', $formParams ) )
        {
            $accountStatusEnabled = $formParams['account_status_enabled'];
        }
        else
        {
            $accountStatusEnabled = true;
        }

        $object = false;
        $userService = $this->repository->getUserService();

        // save current user id
        $currentUserId = $this->repository->getCurrentUser()->content->versionInfo->contentInfo->id;

        // the id from config, et current user to admin user
        $this->repository->setCurrentUser( $userService->loadUser( $adminUserId ) );

        $contentCreateStruct = $this->contentService->newContentCreateStruct( $contentType, $this->initialLanguageCode );
        $object = $this->formBuilderService->buildContentStructWithFormData( $formDataObj, $contentCreateStruct );

        if ( $object['ezuser'] !== false )
        {
            $userGroup = $userService->loadUserGroup( $locationId );

            $userCreateStruct = $userService->newUserCreateStruct(
                $object['ezuser']['login'],
                $object['ezuser']['email'],
                $object['ezuser']['password'],
                $this->initialLanguageCode,
                $contentType
            );

            $userCreateStruct->enabled = $accountStatusEnabled;

            $contentStruct = $object['contentStruct'];
            foreach ( $contentStruct->fields as $field )
            {
                $userCreateStruct->setField( $field->fieldDefIdentifier, $field->value );
            }

            $object = $userService->createUser( $userCreateStruct, array( $userGroup ) );
// ToDo: set enabled = false, check login and email
            if ( $object !== false )
            {
                // change owner id to self
                $userContentInfo = $object->content->versionInfo->contentInfo;
                $metadataUpdateStruct = $this->contentService->newContentMetadataUpdateStruct();
                $metadataUpdateStruct->ownerId = $userContentInfo->id;
                $object = $this->contentService->updateContentMetadata( $userContentInfo, $metadataUpdateStruct );
            }
        }

        // revert admin rights
        $this->repository->setCurrentUser( $userService->loadUser( $currentUserId ) );

        return $object;
    }
}
