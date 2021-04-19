<?php

namespace AppBundle\Controller;

use AppBundle\Entity\EventUserState;
use AppBundle\Entity\UserLink;
use AppBundle\Exception\InvalidFormException;
use AppBundle\Form\Type\EventUserType;
use FOS\RestBundle\View\View;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Put;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Patch;
use FOS\RestBundle\Controller\Annotations\Delete;


use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use AppBundle\Entity\EventUser;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use AppBundle\Entity\ActivityEdge;

class UserLinkController extends RestController
{

    /**
     * REST action which returns list of UserLink
     * Method: GET
     *
     * @ApiDoc(
     *   resource = true,
     *   description = "Gets a list of links",
     *   output = "AppBundle\Entity\UserLink",
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     404 = "Returned when the page is not found"
     *   }
     * )
     *
     * @param $id
     * @return mixed
     *
     * @Get("/user/links", name="get_user_links")
     */
    public function getUserLinksAction()
    {
        $userLinkRepository = $this->getRepository('AppBundle:UserLink');
        $userLinks = array();
        try {
            $userId = $this->getUser()->getId();
            $userLinks = $userLinkRepository->findByUserId($userId);

        } catch (\Exception $exception) {
            throw  $exception;
            $userLinks = array();
        }

        if (empty($userLinks)) {
            return $this->getNoContentHttpView();
        }
        return $userLinks;
    }

    /**
     * REST action which returns list of blocked UserLink
     * Method: GET
     *
     * @ApiDoc(
     *   resource = true,
     *   description = "Gets a list of links",
     *   output = "AppBundle\Entity\UserLink",
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     404 = "Returned when the page is not found"
     *   }
     * )
     *
     * @param $id
     * @return mixed
     *
     * @Get("/user/links/blocked", name="get_user_links_blocked")
     */
    public function getUserBlockedLinksAction()
    {
        $userLinkRepository = $this->getRepository('AppBundle:UserLink');
        $userLinks = array();
        try {
            $userId = $this->getUser()->getId();
            $userLinks = $userLinkRepository->findBlockedByUserId($userId);

        } catch (\Exception $exception) {
            throw  $exception;
            $userLinks = array();
        }

        if (empty($userLinks)) {
            return $this->getNoContentHttpView();
        }
        return $userLinks;
    }

    /**
     * REST action which returns list of paused UserLink
     * Method: GET
     *
     * @ApiDoc(
     *   resource = true,
     *   description = "Gets a list of links",
     *   output = "AppBundle\Entity\UserLink",
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     404 = "Returned when the page is not found"
     *   }
     * )
     *
     * @param $id
     * @return mixed
     *
     * @Get("/user/links/paused", name="get_user_links_paused")
     */
    public function getUserPausedLinksAction()
    {
        $userLinkRepository = $this->getRepository('AppBundle:UserLink');
        $userLinks = array();
        try {
            $userId = $this->getUser()->getId();
            $userLinks = $userLinkRepository->findPausedByUserId($userId);

        } catch (\Exception $exception) {
            throw  $exception;
            $userLinks = array();
        }

        if (empty($userLinks)) {
            return $this->getNoContentHttpView();
        }
        return $userLinks;
    }

    /**
     * REST action which returns list of UserLink
     * Method: GET
     *
     * @ApiDoc(
     *   resource = true,
     *   description = "Gets a list of user followers",
     *   output = "AppBundle\Entity\UserLink",
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     404 = "Returned when the page is not found"
     *   }
     * )
     *
     * @param $id
     * @return mixed
     *
     * @Get("/user/followers", name="get_user_followers")
     */
    public function getUserFollowersAction()
    {
        $userLinkRepository = $this->getRepository('AppBundle:UserLink');
        $userLinks = array();
        try {
            $userId = $this->getUser()->getId();
            $userLinks = $userLinkRepository->findFollowersByTargetId($userId);

        } catch (\Exception $exception) {
            throw  $exception;
            $userLinks = array();
        }

        if (empty($userLinks)) {
            return $this->getNoContentHttpView();
        }
        return $userLinks;
    }

    /**
     * Update existing UserLink from the submitted data or create a new UserLink.
     * All required fields must be set within request data.
     *
     * @ApiDoc(
     *   resource = true,
     *   input = "AppBundle\Entity\UserLink",
     *   statusCodes = {
     *     201 = "Returned when the UserLink is created",
     *     204 = "Returned when successful",
     *     400 = "Returned when the form has errors",
     *     401 = "Returned when not authenticated",
     *     403 = "Returned when not having permissions"
     *   }
     * )
     *
     * @param Request $request the request object
     * @param int $userId the userId id
     *
     * @return FormTypeInterface|View
     *
     * @throws NotFoundHttpException when UserLanguage not exist
     *
     * @Put("/user/links/{targetUserId}")
     */
    public function putUserLinkAction(Request $request, $targetUserId)
    {
        try {
            $userId = $this->getUser()->getId();

            $data = $request->request->all();

            $userLinkRepository = $this->getRepository('AppBundle:UserLink');
            $userLink = $userLinkRepository->findOneBy(['targetUser' => $targetUserId, 'user'=>$userId]);

            //only allow connect if present at the same event
            if(array_key_exists('type', $data)
                && $data['type'] == 'follow'
                && (!$userLink || $userLink->getType() === UserLink::TYPE_DELETE)) {
                if(!array_key_exists('event', $data)) {
                    throw new UnprocessableEntityHttpException('event_not_provided');
                }

                $eventUserLinkRepository = $this->getRepository('AppBundle:EventUser');
                $haveBothUserConfirmedRegistrationToEvent = $eventUserLinkRepository->haveBothUserConfirmedRegistrationToEvent($data['event'], $userId, $targetUserId);
                if(!$haveBothUserConfirmedRegistrationToEvent) {
                    throw new UnprocessableEntityHttpException('both_users_not_registered_to_event');
                }

                $eventRepository = $this->getRepository('AppBundle:Event');
                $event = $eventRepository->findOneById($data['event']);
                if($event === null){
                    throw new UnprocessableEntityHttpException('event_not_exists');
                }
                $dateTime = new \DateTime('now', new \DateTimeZone('UTC'));
                if($event->getStartDateTimeUTC() > $dateTime) {
                    throw new UnprocessableEntityHttpException('event_not_started');
                }
            }

            try {
                if (!$userLink) {
                    $userLink = $this->createNewUserLink($request->request->all() + ['user' => $userId, 'targetUser' => $targetUserId]);
                    return $this->getViewAfterSuccessfulCreate($userLink, $this->generateGetRouteFromUserLink($userLink));
                } else {
                    $userLink = $this->processForm($userLink, $request->request->all() + ['user' => $userId, 'targetUser' => $targetUserId], 'PUT');
                    return $this->getViewAfterSuccessfulUpdate($userLink, $this->generateGetRouteFromUserLink($userLink));
                }

            } catch (InvalidFormException $exception) {

                return $exception->getForm();
            }
        } catch (\Exception $exception) {
            $this->throwFosrestSupportedException($exception);
        }
    }

    /**
     * Creates new type from request parameters and persists it.
     *
     * @param Request $request
     * @return UserLink - persisted type
     */
    protected function createNewUserLink($data)
    {
        $userLink = new UserLink();
        $userLink->setCreationDateTime($this->getCurrentDateTimeUTC());
        $persistedUserLanguage = $this->processForm($userLink, $data, 'POST');
        return $persistedUserLanguage;
    }

    /**
     * REST action which deletes UserLink by targetUserid.
     * Method: DELETE, url: /user/links/{targetUserId}/
     *
     * @ApiDoc(
     *   resource = true,
     *   description = "Deletes an UserLink for a given id",
     *   statusCodes = {
     *     204 = "Returned when successful",
     *     401 = "Returned when not authenticated",
     *     403 = "Returned when not having permissions",
     *     404 = "Returned when the userLanguage is not found"
     *   }
     * )
     *
     * @param Request $request
     * @param $userId
     * @param $targetUserId
     * @return mixed
     *
     * @Delete("/user/links/{targetUserId}")
     */
    public function deleteUserLinkAction(Request $request, $targetUserId) {
        $userId = $this->getUser()->getId();
        $userLinkRepository = $this->getRepository('AppBundle:UserLink');
        $userLink = $userLinkRepository->findOneBy(['user' => $userId, 'targetUser' => $targetUserId]);

        if ($userLink) {
            try {
                $manager = $this->getManager();
                $userLink->setType(UserLink::TYPE_DELETE);
                $manager->persist($userLink);
                $manager->flush();

                return $this->getViewAfterSuccessfulDelete();
            } catch (\Exception $exception) {
                $this->throwFosrestSupportedException($exception);
            }
        } else {
            throw new NotFoundHttpException(sprintf('The resource \'%s\' was not found.', $targetUserId));
        }
    }

    /**
     * Processes the form.
     *
     * @param UserLink $userLink
     * @param array $parameters
     * @param String $method
     * @return UserLink
     *
     * @throws InvalidFormException
     */
    private function processForm(UserLink $userLink, array $parameters, $method = 'PUT')
    {
        $form = $this->createForm('AppBundle\Form\Type\UserLinkType', $userLink, ['method' => $method]);

        $form->submit($parameters, 'PATCH' !== $method);

        if ($form->isValid()) {

            $userLink = $form->getData();

            $manager = $this->getManager();
            $manager->persist($userLink);
            $manager->flush();

            return $userLink;
        }

        throw new InvalidFormException('Invalid submitted data', $form);
    }

    private function generateGetRouteFromUserLink($userLink) {
        return $this->generateUrl('get_user_language', [
            'languageId' => $userLink->getId(),
            'userId'  => $userLink->getUser()->getId()
        ]);
    }
}
