<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Billing\Subscription;
use AppBundle\Entity\EventUserState;
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
use DateTime;
use DateInterval;

class EventUserController extends RestController
{

    /**
     * REST action which returns list of eventUsers
     * Method: GET
     *
     * @ApiDoc(
     *   resource = true,
     *   description = "Gets a list of eventUser",
     *   output = "AppBundle\Entity\EventUser",
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     404 = "Returned when the page is not found"
     *   }
     * )
     *
     * @param $id
     * @return mixed
     *
     * @Get("/events/{eventId}/users", name="get_event_users")
     */
    public function getEventUsersAction($eventId)
    {
        $eventUserRepository = $this->getRepository('AppBundle:EventUser');
        $eventUsers = array();
        try {
            $eventUsers = $eventUserRepository->findConfirmedUsersByEventIdOrderByStateCreationDateTime($eventId);

        } catch (\Exception $exception) {
            $eventUsers = array();
        }

        if (empty($eventUsers)) {
            return $this->getNoContentHttpView();
        }
        return $eventUsers;
    }

    /**
     * REST action which returns eventUser by id.
     * Method: GET
     *
     * @ApiDoc(
     *   resource = true,
     *   description = "Gets a EventUser for a given id",
     *   output = "AppBundle\Entity\EventUser",
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     404 = "Returned when the page is not found"
     *   }
     * )
     *
     * @param $id
     * @return mixed
     *
     * @Get("/events/{eventId}/user", name="get_event_user")
     */
    public function getCurrentUserEventUserAction($eventId)
    {
        $eventUserRepository = $this->getRepository('AppBundle:EventUser');
        $eventUser = NULL;
        try {
            $userId = $this->getUser()->getId();
            $eventUser = $eventUserRepository->findOneBy(['event' => $eventId, 'user' => $userId]);
        } catch (\Exception $exception) {
            $eventUser = NULL;
        }

        if (!$eventUser) {
            throw new NotFoundHttpException(sprintf('The resource \'%s\' for event \'%s\' was not found.', $userId, $eventId));
        }
        return $eventUser;
    }

    /**
     * Update existing EventUser from the submitted data or create a new EventUser.
     * All required fields must be set within request data.
     *
     * @ApiDoc(
     *   resource = true,
     *   input = "AppBundle\Entity\EventUser",
     *   statusCodes = {
     *     201 = "Returned when the EventUser is created",
     *     204 = "Returned when successful",
     *     400 = "Returned when the form has errors",
     *     401 = "Returned when not authenticated",
     *     403 = "Returned when not having permissions"
     *   }
     * )
     *
     * @param Request $request the request object
     * @param int $userId the userId id
     * @param int $eventUserId the eventUser id
     *
     * @return FormTypeInterface|View
     *
     * @throws NotFoundHttpException when EventUser not exist
     *
     * @Put("/events/{eventId}/user")
     *
     */
    public function putCurrentUserEventUserAction(Request $request, $eventId)
    {
        try {
            try {
                $userId = $this->getUser()->getId();
                $eventUserRepository = $this->getRepository('AppBundle:EventUser');
                $eventUser = $eventUserRepository->findOneBy(['event'=>$eventId, 'user' => $userId]);

                if (!$eventUser) {
                    //TODO : Check participation count from database for the current period
                    //$this->checkPublicEventRegistrationUsage();
                    $userStatistics = $this->getUser()->getStatistics();
                    $score = $userStatistics->getCommitmentScore();
                    if($score === 0) {
                        throw new UnprocessableEntityHttpException('user_commitment_score_zero');
                    }

                    $eventUser = $this->createNewEventUser($request->request->all() + ['user' => $userId, 'event' => $eventId]);

                    /* Store activity to notify followers */
                    $activityEdge = new ActivityEdge();
                    $activityEdge
                        ->setUser($this->getUser())
                        ->setActivityType('event')
                        ->setVerb('register')
                        ->setSourceId($eventId)
                        ->setCreationDateTime($eventUser->getCurrentState()->getCreationDateTime());
                    $manager = $this->getManager();
                    $manager->persist($activityEdge);
                    $manager->flush();

                    $mailManager = $this->get('mail.manager');
                    $mailManager->sendParticipantRegistrationToOrganiser($eventUser);

                    return $this->getViewAfterSuccessfulCreate($eventUser, $this->generateGetRouteFromEventUser($eventUser));
                } else {
                    $eventUserUser = $eventUser->getUser();
                    $this->checkIfCanEditData($eventUserUser->getId());
                    $eventUser = $this->processForm($eventUser, $request->request->all() + ['user' => $userId, 'event' => $eventId], 'PUT');
                    return $this->getViewAfterSuccessfulUpdate($eventUser, $this->generateGetRouteFromEventUser($eventUser));
                }

            } catch (InvalidFormException $exception) {

                return $exception->getForm();
            }
        } catch (\Exception $exception) {
            $this->throwFosrestSupportedException($exception);
        }
    }

    /**
     * Update existing EventUser from the submitted data
     * All required fields must be set within request data.
     *
     * @ApiDoc(
     *   resource = true,
     *   input = "AppBundle\Entity\EventUser",
     *   statusCodes = {
     *     201 = "Returned when the EventUser is created",
     *     204 = "Returned when successful",
     *     400 = "Returned when the form has errors",
     *     401 = "Returned when not authenticated",
     *     403 = "Returned when not having permissions"
     *   }
     * )
     *
     * @param Request $request the request object
     * @param int $userId the userId id
     * @param int $eventUserId the eventUser id
     *
     * @return FormTypeInterface|View
     *
     * @throws NotFoundHttpException when EventUser not exist
     *
     * @Patch("/events/{eventId}/user")
     *
     */
    public function patchCurrentUserEventUserAction(Request $request, $eventId)
    {
        try {
            try {
                $userId = $this->getUser()->getId();
                $eventUserRepository = $this->getRepository('AppBundle:EventUser');
                $eventUser = $eventUserRepository->findOneBy(['event'=>$eventId, 'user' => $userId]);

                if (!$eventUser) {
                    throw new NotFoundHttpException('registation_not_found');
                }

                $eventUser = $this->processForm($eventUser, $request->request->all() + ['user' => $userId, 'event' => $eventId], 'PATCH');
                return $this->getViewAfterSuccessfulUpdate($eventUser, $this->generateGetRouteFromEventUser($eventUser));
            } catch (InvalidFormException $exception) {

                return $exception->getForm();
            }
        } catch (\Exception $exception) {
            $this->throwFosrestSupportedException($exception);
        }
    }

    /**
     * Update existing EventUser from the submitted data or create a new EventUser.
     * All required fields must be set within request data.
     *
     * @ApiDoc(
     *   resource = true,
     *   input = "AppBundle\Entity\EventUser",
     *   statusCodes = {
     *     201 = "Returned when the EventUser is created",
     *     204 = "Returned when successful",
     *     400 = "Returned when the form has errors",
     *     401 = "Returned when not authenticated",
     *     403 = "Returned when not having permissions"
     *   }
     * )
     *
     * @param Request $request the request object
     * @param int $userId the userId id
     * @param int $eventUserId the eventUser id
     *
     * @return FormTypeInterface|View
     *
     * @throws NotFoundHttpException when EventUser not exist
     *
     * @Put("/events/{eventId}/users/{userId}")
     */
    public function putEventUserAction(Request $request, $eventId, $userId)
    {
        try {
            try {
                //TODO autoriser seulement aux admins
                throw new AccessDeniedHttpException();

                $eventUserRepository = $this->getRepository('AppBundle:EventUser');
                $eventUser = $eventUserRepository->findOneBy(['event'=>$eventId, 'user' => $userId]);

                if (!$eventUser) {
                    $eventUser = $this->createNewEventUser($request->request->all() + ['user' => $userId, 'event' => $eventId]);
                    return $this->getViewAfterSuccessfulCreate($eventUser, $this->generateGetRouteFromEventUser($eventUser));
                } else {
                    $eventUserUser = $eventUser->getUser();
                    $eventUser = $this->processForm($eventUser, $request->request->all() + ['user' => $userId, 'event' => $eventId], 'PUT');
                    return $this->getViewAfterSuccessfulUpdate($eventUser, $this->generateGetRouteFromEventUser($eventUser));
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
     * @return EventUser - persisted type
     */
    protected function createNewEventUser($data)
    {
        $eventUser = new EventUser();
        $persistedEventUser = $this->processForm($eventUser, $data, 'POST');
        return $persistedEventUser;
    }

    /**
     * Processes the form.
     *
     * @param EventUser $eventUser
     * @param array $parameters
     * @param String $method
     * @return EventUser
     *
     * @throws InvalidFormException
     */
    private function processForm(EventUser $eventUser, array $parameters, $method = 'PUT')
    {
        $form = $this->createForm('AppBundle\Form\Type\EventUserType', $eventUser, ['method' => $method]);

        $form->submit($parameters, 'PATCH' !== $method);

        if ($form->isValid()) {
            $eventUser = $form->getData();

            //If organizer try to cancel registration, error
            if($form->get('state')->getData() !== null
                && $form->get('state')->getData() === EventUserState::CANCELLED
                && $eventUser->getUser()->getId() === $eventUser->getEvent()->getUser()->getId()) {
                throw new UnprocessableEntityHttpException('organizer_cannot_unregister');
            }

            $stateName = EventUserState::CONFIRMED;
            if($eventUser->getCurrentState() === null
                || $form->get('state')->getData() !== $eventUser->getCurrentState()->getName()) {


                if($form->get('state')->getData() !== null) {
                    $stateName = $form->get('state')->getData();
                }

                //if user try to register to an event after cancelling or changing the state, we recheck registration count
                /*
                if($eventUser->getCurrentState() !== null
                    && $stateName === EventUserState::CONFIRMED
                    && $form->get('state')->getData() !== $eventUser->getCurrentState()->getName()) {
                    $this->checkPublicEventRegistrationUsage();
                }
                */
                $eventUserState = new EventUserState();
                $eventUserState->setName($stateName)
                    ->setCreationDateTime($this->getCurrentDateTimeUTC());
                $eventUser->setCurrentState($eventUserState);
            }

            $eventUserRepository = $this->getRepository('AppBundle:EventUser');
            $event = $eventUser->getEvent();

            /**
             //TODO: limitation to activate when we want to limit the number of event joined
            $usersCount = $eventUserRepository->countConfirmedUsersByEventId($event->getId());


            if($event->getMaximumCapacity() != null
                && $event->getMaximumCapacity() <= $usersCount
                && $stateName === EventUserState::CONFIRMED) {
                throw new UnprocessableEntityHttpException('maximum_capacity_reached');
            }
             **/

            $manager = $this->getManager();
            if(isset($eventUserState)) {
                $manager->persist($eventUserState);
            }
            $manager->persist($eventUser);
            $manager->flush();

            if($stateName === EventUserState::CANCELLED) {
                $mailManager = $this->get('mail.manager');
                $mailManager->sendParticipantCancellationToOrganiser($eventUser);
            }

            return $eventUser;
        }

        throw new InvalidFormException('Invalid submitted data', $form);
    }

    private function generateGetRouteFromEventUser($eventUser)
    {
        return $this->generateUrl('get_event_user', [
            'eventId'  => $eventUser->getEvent()->getId(),
            'userId' => $eventUser->getUser()->getId(),
        ]);
    }

    private function getCurrentPeriodPublicEventRegistrationUsage()
    {
        $user = $this->getUser();
        $subscriptionRepository = $this->getRepository(Subscription::class);
        $subscription = $subscriptionRepository->findOneBy(['user' => $user, 'active' => true]);
        if(empty($subscription)) {
            throw new UnprocessableEntityHttpException('no_subscription_found');
        }

        $periodDateInfo = $this->getCurrentPeriodInfo($subscription->getStartDate());
        $eventUserRepository = $this->getRepository(EventUser::class);
        $publicEventRegistrationUsage  = $eventUserRepository->findPublicEventRegistrationUsageByUserAndPeriod($user,$periodDateInfo['startDate']);
        return $publicEventRegistrationUsage;
    }

    private function getPublicEventRegistrationMaximumLimitForCurrentUser()
    {
        $user = $this->getUser();
        $subscriptionRepository = $this->getRepository(Subscription::class);
        $subscription = $subscriptionRepository->findOneBy(['user' => $user, 'active' => true]);
        if(empty($subscription)) {
            throw new UnprocessableEntityHttpException('no_subscription_found');
        }
        $limit = $subscription->getPlan()->getEventRegistrationMaximumLimit();
        return $limit;
    }

    private function checkPublicEventRegistrationUsage() {
        if($this->getCurrentPeriodPublicEventRegistrationUsage() >= $this->getPublicEventRegistrationMaximumLimitForCurrentUser()) {
            throw new AccessDeniedHttpException('registration_limit_to_public_event_reached');
        }
    }

    //Calculate current period start and end date from subscription start date
    private function getCurrentPeriodInfo($subscriptionStartDate) {
        $todayDate = new DateTime();
        $previousMonthDate = new DateTime();
        $previousMonthDate->sub(new DateInterval('P1M'));
        $nextMonthDate = new DateTime();
        $nextMonthDate->sub(new DateInterval('P1M'));
        if($subscriptionStartDate->format('d') > $todayDate->format('d')) {
            $periodStartDate = DateTime::createFromFormat('d-m-Y', $subscriptionStartDate->format('d').'-'.$previousMonthDate->format('m').'-'.$todayDate->format('Y'));
            $periodEndDate = DateTime::createFromFormat('d-m-Y', $subscriptionStartDate->format('d').'-'.$todayDate->format('m').'-'.$todayDate->format('Y'));
        } else {
            $periodStartDate = DateTime::createFromFormat('d-m-Y', $subscriptionStartDate->format('d').'-'.$todayDate->format('m').'-'.$todayDate->format('Y'));
            $periodEndDate = DateTime::createFromFormat('d-m-Y', $subscriptionStartDate->format('d').'-'.$nextMonthDate->format('m').'-'.$todayDate->format('Y'));
        }
        return ['startDate' => $periodStartDate, 'endDate' => $periodEndDate];
    }

}
