<?php

namespace AppBundle\Controller;

use AppBundle\Entity\EventUserState;
use AppBundle\Entity\UserLink;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use AppBundle\Exception\InvalidFormException;
use FOS\RestBundle\View\View;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Put;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Patch;
use FOS\RestBundle\Controller\Annotations\Delete;

use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use AppBundle\Entity\EventUserInvitation;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Validator\Constraints\DateTime;
use AppBundle\Entity\ActivityEdge;

class EventUserInvitationController extends RestController
{

    /**
     * REST action which returns list of eventUserInvitations
     * Method: GET, url: /events/{eventId}/user/invitations
     *
     * @ApiDoc(
     *   resource = true,
     *   description = "Gets a list of eventUserInvitation",
     *   output = "AppBundle\Entity\EventUserInvitation",
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     404 = "Returned when the page is not found"
     *   }
     * )
     *
     * @param $id
     * @return mixed
     *
     * @Get("/events/{eventId}/user/invitations", name="get_event_user_invitations")
     */
    public function getEventUserInvitationsAction($eventId)
    {
        $userId = $this->getUser()->getId();

        $eventUserInvitationRepository = $this->getRepository('AppBundle:EventUserInvitation');
        $eventUserInvitations = array();

        try {
            $eventUserInvitations = $eventUserInvitationRepository->findBy(['event' => $eventId, 'user' => $userId]);
        } catch (\Exception $exception) {
            $eventUserInvitations = array();
        }

        if (empty($eventUserInvitations)) {
            return $this->getNoContentHttpView();
        }
        return $eventUserInvitations;
    }

    /**
     * REST action which returns one eventUserInvitation or null
     * Method: GET, url: /events/{eventId}/user/invitation
     *
     * @ApiDoc(
     *   resource = true,
     *   description = "Gets a list of eventUserInvitation",
     *   output = "AppBundle\Entity\EventUserInvitation",
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     404 = "Returned when the page is not found"
     *   }
     * )
     *
     * @param $id
     * @return mixed
     *
     * @Get("/events/{eventId}/user/invitations/{invitedUserId}", name="get_event_user_invitation")
     */
    public function getEventUserInvitationAction($eventId, $invitedUserId)
    {
        $userId = $this->getUser()->getId();

        $eventUserInvitationRepository = $this->getRepository('AppBundle:EventUserInvitation');
        $eventUserInvitations = array();

        try {
            $eventUserInvitations = $eventUserInvitationRepository->findOneBy(['event' => $eventId, 'invitedUser' => $invitedUserId]);
        } catch (\Exception $exception) {
            $eventUserInvitations = array();
        }

        if (empty($eventUserInvitations)) {
            return $this->getNoContentHttpView();
        }
        return $eventUserInvitations;
    }

    /**
 * Create a EventUserInvitation from the submitted data.
 *
 * @ApiDoc(
 *   resource = true,
 *   description = "Creates a new EventUserInvitation from the submitted data.",
 *   input = "AppBundle\Entity\EventUserInvitation",
 *   statusCodes = {
 *     200 = "Returned when successful",
 *     400 = "Returned when the form has errors",
 *     401 = "Returned when not authenticated",
 *     403 = "Returned when not having permissions"
 *   }
 * )
 *
 * @param Request $request the request object
 *
 * @return FormTypeInterface|View
 *
 * @Post("/events/{eventId}/user/invitations/{invitedUserId}")
 */
    public function postEventUserInvitationAction(Request $request, $eventId, $invitedUserId)
    {
        $userId = $this->getUser()->getId();

        $userRepository = $this->getRepository('AppBundle:User');
        $invitedUser = $userRepository->findOneById($invitedUserId);

        if($invitedUser === null || ($invitedUser !== null && !$invitedUser->isActive() || !$invitedUser->isConfirmed())) {
            throw new UnprocessableEntityHttpException('user_not_exists');
        }

        $userLinkRepository = $this->getRepository('AppBundle:UserLink');
        $userLink = $userLinkRepository->findOneBy(['user' => $userId, 'targetUser' => $invitedUserId]);

        if($userLink === null) {
            throw new UnprocessableEntityHttpException('user_not_connected');
        }

        $eventUserRepository = $this->getRepository('AppBundle:EventUser');
        $eventUser = $eventUserRepository->findOneConfirmedEventUserByEventIdAndUserId($eventId, $invitedUserId);

        if($eventUser !== null && $eventUser->getCurrentState()->getName() === EventUserState::CONFIRMED) {
            throw new UnprocessableEntityHttpException('user_already_registered');
        }

        $eventUserInvitationRepository = $this->getRepository('AppBundle:EventUserInvitation');
        $eventUserInvitation = $eventUserInvitationRepository->findOneBy(['event'=>$eventId, 'invitedUser'=>$invitedUserId, 'user' => $userId]);

        if($eventUserInvitation !== null) {
            throw new UnprocessableEntityHttpException('user_already_invited');
        }

        $invitedUserLink = $userLinkRepository->findOneBy(['user' => $invitedUserId, 'targetUser' => $userId]);

        $mailManager = $this->get('mail.manager');

        try {
            try {
                $persistedEventUserInvitation = $this->createNewEventUserInvitation($request->request->all() + ['event' => $eventId, 'user' => $userId, 'invitedUser' => $invitedUserId]);

                //If the invited user doesn't have the inviting user in contacts or if he hasn't block him we send mails
                if($invitedUserLink === null ||
                    ($invitedUserLink !== null && ($invitedUserLink->getType() == UserLink::TYPE_FOLLOW || $invitedUserLink->getType() == UserLink::TYPE_DELETE))) {
                    if (false && $persistedEventUserInvitation->getEvent()->getUser() === $persistedEventUserInvitation->getUser()) {
                        $mailManager->sendInvitationToEventByOrganiser($persistedEventUserInvitation->getEvent(), $persistedEventUserInvitation->getUser(), $persistedEventUserInvitation->getInvitedUser());
                    } else {
                        $mailManager->sendInvitationToEventByParticipant($persistedEventUserInvitation->getEvent(), $persistedEventUserInvitation->getUser(), $persistedEventUserInvitation->getInvitedUser());
                    }
                }

                return $this->getViewAfterSuccessfulCreate($persistedEventUserInvitation, $this->generateGetRouteFromEventUserInvitation($persistedEventUserInvitation));

            } catch (InvalidFormException $exception) {

                return $exception->getForm();
            }
        } catch (\Exception $exception) {
            $this->throwFosrestSupportedException($exception);
        }
    }

    /**
     * Create multiple EventUserInvitations from the submitted data.
     *
     * @ApiDoc(
     *   resource = true,
     *   description = "Creates new EventUserInvitations from the submitted data.",
     *   input = "AppBundle\Entity\EventUserInvitation",
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     400 = "Returned when the form has errors",
     *     401 = "Returned when not authenticated",
     *     403 = "Returned when not having permissions"
     *   }
     * )
     *
     * @param Request $request the request object
     *
     * @return FormTypeInterface|View
     *
     * @Post("/events/{eventId}/user/invitations")
     */
    public function postEventUserInvitationsAction(Request $request, $eventId)
    {
        $requestData = $request->request->all();
        $invitedUsers = [];
        if(isset($requestData['invitedUsers'])) {
            $request = new Request();
            foreach($requestData['invitedUsers'] as $invitedUser) {
                if(isset($invitedUser['invitedUser'])) {
                    $invitedUserId = $invitedUser['invitedUser'];
                    try {
                        $invitedUsers[$invitedUserId] = $this->postEventUserInvitationAction($request, $eventId, $invitedUserId);
                    } catch (\Exception $exception) {
                        $statusCode = 0;
                        if($exception instanceof HttpException) {
                            $statusCode = $exception->getStatusCode();
                        }
                        $invitedUsers[$invitedUserId] = ['error'=>true, 'code'=> $statusCode, 'message'=>$exception->getMessage()];
                    }
                }
            }
        }

        return $invitedUsers;
    }

    /**
     * Creates new type from request parameters and persists it.
     *
     * @param Request $request
     * @return EventUserInvitation - persisted type
     */
    protected function createNewEventUserInvitation($data)
    {
        $eventUserInvitation = new EventUserInvitation();
        $persistedEventUserInvitation = $this->processForm($eventUserInvitation, $data, 'POST');
        return $persistedEventUserInvitation;
    }

    /**
     * Processes the form.
     *
     * @param EventUserInvitation $eventUserInvitation
     * @param array $parameters
     * @param String $method
     * @return EventUserInvitation
     *
     * @throws InvalidFormException
     */
    private function processForm(EventUserInvitation $eventUserInvitation, array $parameters, $method = 'PUT')
    {
        $form = $this->createForm('AppBundle\Form\Type\EventUserInvitationType', $eventUserInvitation, ['method' => $method]);

        $form->submit($parameters, 'PATCH' !== $method);

        if ($form->isValid()) {
            $eventUserInvitation = $form->getData();
            $eventUserInvitation->setCreationDateTime($this->getCurrentDateTimeUTC());

            $manager = $this->getManager();
            $manager->persist($eventUserInvitation);
            $manager->flush();

            return $eventUserInvitation;
        }

        throw new InvalidFormException('Invalid submitted data', $form);
    }

    private function generateGetRouteFromEventUserInvitation($eventUserInvitation) {
        return $this->generateUrl('get_event_user_invitation', [
            'invitedUserId'  => $eventUserInvitation->getInvitedUser()->getId(),
            'eventId' => $eventUserInvitation->getEvent()->getId()
        ]);
    }
}
