<?php

namespace Cjw\PublishToolsBundle\Controller;

use eZ\Bundle\EzPublishCoreBundle\Controller;
use Symfony\Component\HttpFoundation\Response;
//use Symfony\Component\HttpFoundation\Cookie;

class UserRegisterActivateController extends Controller
{
    protected $repository;
    protected $userService;
    protected $currentUserId;

    public function userActivateAction( $hash = '', $id = 0 )
    {
        $this->repository = $this->getRepository();
        $this->userService = $this->repository->getUserService();

        // save current user id
        $this->currentUserId = $this->repository->getCurrentUser()->content->versionInfo->contentInfo->id;

        try {
            $this->repository->setCurrentUser( $this->userService->loadUser( $id ) );
        } catch( \eZ\Publish\Core\Base\Exceptions\NotFoundException $error ) {
            return $this->response();
        }

        $currentUser = $this->repository->getCurrentUser();

        if ( $currentUser->enabled )
        {
            return $this->response();
        }

        $accountKey = md5( $currentUser->login . $currentUser->email . $currentUser->passwordHash . $currentUser->contentInfo->modificationDate->format( 'U' ) );

        if ( $accountKey == $hash && $currentUser->enabled == false )
        {
            $userUpdateStruct = $this->userService->newUserUpdateStruct();
            $userUpdateStruct->enabled = true;
            $this->userService->updateUser( $currentUser, $userUpdateStruct );

            // ToDo: config option
            // update the userObject, set the modificationDate, this will permit reusing the hash link
            if ( true )
            {
                $contentService = $this->repository->getContentService();

                $contentDraft = $contentService->createContentDraft( $currentUser->contentInfo );

                $contentUpdateStruct = $contentService->newContentUpdateStruct();
                $contentUpdateStruct->initialLanguageCode = $contentDraft->versionInfo->contentInfo->mainLanguageCode;

                // ToDo: BO Special, move this out here or introduce an hook / config option!
                if ( isset( $contentDraft->fields->subscription_modified ) )
                {
                    $contentUpdateStruct->setField( 'subscription_modified', strval( time() ) );
                }

                $draft = $contentService->updateContent( $contentDraft->versionInfo, $contentUpdateStruct );
                $contentService->publishVersion( $draft->versionInfo );
            }
        }

        return $this->response();
    }

    private function response()
    {
        // revert user rights
        $this->repository->setCurrentUser( $this->userService->loadUser( $this->currentUserId ) );

        $response = new Response;
// ToDo: besser lösen, ist folgendes kompatible mit plain ezp5, was ist wenn tools bundle in vendor dir ?
        $siteBundlePathArr = array_reverse( explode( '/', $this->container->getParameter( 'kernel.root_dir' ) ) );
//        return (object) array( 'prefix' => $siteBundlePathArr['2'], 'name' => $siteBundlePathArr['1'] );
        $template = $siteBundlePathArr['2'] . $siteBundlePathArr['1'] . ':user:activate.html.twig';
/*
// http://share.ez.no/forums/ez-publish-5-platform/how-to-log-a-user-in-a-controller
$session = $this->getRequest()->getSession();
$session->set( 'eZUserLoggedInID', $currentUser->id );
$response->headers->setCookie( new Cookie( 'is_logged_in', 'true' ) );
*/
        return $this->render(
            $template,
            array(),
            $response
        );
    }
}
