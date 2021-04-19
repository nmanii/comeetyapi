<?php

namespace AppBundle\Controller;

use AppBundle\Entity\EventUser;
use AppBundle\Entity\EventUserState;
use AppBundle\Entity\ActivityEdge;
use AppBundle\Entity\Location;
use AppBundle\Entity\UserLink;
use AppBundle\Exception\InvalidFormException;
use AppBundle\Form\Type\EventType;
use FOS\RestBundle\View\View;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Put;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Patch;
use FOS\RestBundle\Controller\Annotations\Delete;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use AppBundle\Entity\Event;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Validator\Constraints\DateTime;

class EventController extends RestController
{

    /**
     * @Get("/user/registered_events/finished", name="get_user_finished_registered_events")
     */
    public function getCurrentUserFinishedRegisteredEventsAction()
    {
        $eventRepository = $this->getRepository('AppBundle:Event');

        $events = [];
        try {
            $userId = $this->getUser()->getId();
            $events = $eventRepository->findPastRegisteredEventByUserId($userId);
        } catch (\Exception $exception) {
            throw $exception;
            $events = [];
        }

        if (!$events) {
            return $this->getNoContentHttpView();
        }
        return $events;
    }

    /**
     *
     * @Get("/user/registered_events/upcoming", name="get_user_upcoming_registered_events")
     */
    public function getCurrentUserUpcomingRegisteredEventsAction()
    {
        $eventRepository = $this->getRepository('AppBundle:Event');

        $events = [];
        try {
            $userId = $this->getUser()->getId();
            $events = $eventRepository->findUpcomingRegisteredEventByUserId($userId);
        } catch (\Exception $exception) {
            $events = [];
        }

        if (!$events) {
            return $this->getNoContentHttpView();
        }
        return $events;
    }

    /**
     *
     * @Get("/user/pending_invitations_events", name="get_user_pending_invitation_events")
     */
    public function getCurrentUserPendingInvitationEventsAction()
    {
        $eventRepository = $this->getRepository('AppBundle:Event');

        $events = [];
        try {
            $userId = $this->getUser()->getId();
            $events = $eventRepository->findUpcomingPendingInvitationEventByUserId($userId);
        } catch (\Exception $exception) {
            $events = [];
        }

        if (!$events) {
            return $this->getNoContentHttpView();
        }
        return $events;
    }



    /**
     * REST action which returns list of events
     * Method: GET, url: /events
     *
     * @ApiDoc(
     *   resource = true,
     *   description = "Gets a list of event",
     *   output = "AppBundle\Entity\Event",
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     404 = "Returned when the page is not found"
     *   }
     * )
     *
     * @param $id
     * @return mixed
     *
     * @Get("/events", name="get_events")
     */
    public function getEventsAction()
    {
        $eventRepository = $this->getRepository('AppBundle:Event');
        $events = array();
        try {
            $events = $eventRepository->findOpenEvents();
        } catch (\Exception $exception) {
            $events = array();
        }

        if (empty($events)) {
            return $this->getNoContentHttpView();
        }

        return $events;
    }

    /**
     * REST action which returns list of past events
     * Method: GET, url: /events
     *
     * @ApiDoc(
     *   resource = true,
     *   description = "Gets a list of event",
     *   output = "AppBundle\Entity\Event",
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     404 = "Returned when the page is not found"
     *   }
     * )
     *
     * @param $id
     * @return mixed
     *
     * @Get("/events/past", name="get_past_events")
     */
    public function getPastEventsAction()
    {
        $eventRepository = $this->getRepository('AppBundle:Event');
        $events = array();
        try {
            $events = $eventRepository->findPastEvents();
        } catch (\Exception $exception) {
            $events = array();
        }

        if (empty($events)) {
            return $this->getNoContentHttpView();
        }
        return $events;
    }

    /**
     * REST action which returns event by id.
     * Method: GET, url: /events/{id}
     *
     * @ApiDoc(
     *   resource = true,
     *   description = "Gets a Event for a given id",
     *   output = "AppBundle\Entity\Event",
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     404 = "Returned when the page is not found"
     *   }
     * )
     *
     * @param $id
     * @return mixed
     *
     * @Get("/events/{id}", name="get_event")
     */
    public function getEventAction($id)
    {
        $eventRepository = $this->getRepository('AppBundle:Event');
        $event = NULL;

        try {
            $event = $eventRepository->findOneById($id);
        } catch (\Exception $exception) {
            $event = NULL;
        }

        if (!$event) {
            throw new NotFoundHttpException(sprintf('The resource \'%s\' was not found.', $id));
        }

        //if not authenticated, we show less info
        if(!$this->getUser()) {
            $this->getViewHandler()->setExclusionStrategyGroups(['NotAuthenticated']);
        }
        return $event;
    }

    /**
     * Create a Event from the submitted data.
     *
     * @ApiDoc(
     *   resource = true,
     *   description = "Creates a new Event from the submitted data.",
     *   input = "AppBundle\Entity\Event",
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
     * @Post("/user/events")
     */
    public function postEventAction(Request $request)
    {
        try {
            try {
                $userId = $this->getUser()->getId();

                $persistedEvent = $this->createNewEvent($request->request->all() + ['user' => $userId]);

                if($persistedEvent->isPublic()) {
                    /* Store activity to notify followers */
                    $activityEdge = new ActivityEdge();
                    $activityEdge
                        ->setUser($this->getUser())
                        ->setActivityType('event')
                        ->setVerb('create')
                        ->setSourceId($persistedEvent->getId())
                        ->setCreationDateTime($persistedEvent->getCreationDateTime());
                    $manager = $this->getManager();
                    $manager->persist($activityEdge);
                    $manager->flush();

                    $mailManager = $this->get('mail.manager');
                    $appNotificationManager = $this->get('app_notification.service');

                    $userLinkRepository = $this->getRepository('AppBundle:UserLink');
                    $userLinks = $userLinkRepository->findActiveFollowersByTargetId($userId);
                    foreach ($userLinks as $userLink) {
                        $mailManager->sendEventCreationToFollower($userLink->getUser(), $this->getUser(), $persistedEvent);
                        $appNotificationManager->sendEventCreationToFollower($userLink->getUser(), $this->getUser(), $persistedEvent);
                    }
/*
                    $userRepository = $this->getRepository('AppBundle:User');
                    $users = $userRepository->findActiveWithProfileFilledUsers();
                    foreach($users as $user) {
                        if($user === $persistedEvent->getUser()) {
                            continue;
                        }
                        $mailManager->sendEventCreationToFollower($user, $persistedEvent->getUser(), $persistedEvent);
                    }
*/
                }

                return $this->getViewAfterSuccessfulCreate($persistedEvent, $this->generateGetRouteFromEvent($persistedEvent));

            } catch (InvalidFormException $exception) {

                return $exception->getForm();
            }
        } catch (\Exception $exception) {
            $this->throwFosrestSupportedException($exception);
        }
    }

    /**
     * Update existing Event from the submitted data or create a new Event.
     * All required fields must be set within request data.
     *
     * @ApiDoc(
     *   resource = true,
     *   input = "AppBundle\Entity\Event",
     *   statusCodes = {
     *     201 = "Returned when the Event is created",
     *     204 = "Returned when successful",
     *     400 = "Returned when the form has errors",
     *     401 = "Returned when not authenticated",
     *     403 = "Returned when not having permissions"
     *   }
     * )
     *
     * @param Request $request the request object
     * @param int $userId the userId id
     * @param int $eventId the event id
     *
     * @return FormTypeInterface|View
     *
     * @throws NotFoundHttpException when Event not exist
     *
     * @Put("/user/events/{id}")
     */
    public function putEventAction(Request $request, $id)
    {
        try {
            try {
                $eventRepository = $this->getRepository('AppBundle:Event');
                $event = $eventRepository->findOneBy(['id'=>$id]);
                $userId = $this->getUser()->getId();
                if (!$event) {
                    throw new UnprocessableEntityHttpException('use_post_method_to_create');
                    //$event = $this->createNewEvent($request->request->all() + ['user' => $userId, 'id' => $id]);
                    //return $this->getViewAfterSuccessfulCreate($event, $this->generateGetRouteFromEvent($event));
                } else {
                    $eventUser = $event->getUser();
                    $this->checkIfCanEditData($eventUser->getId());

                    if($event->getStartDateTimeUTC() < $this->getCurrentDateTimeUTC()) {
                        throw new UnprocessableEntityHttpException('cannot_edit_past_event');
                    }
                    $event = $this->processForm($event, $request->request->all() + ['user' => $userId, 'id' => $id], 'PUT');
                    return $this->getViewAfterSuccessfulUpdate($event, $this->generateGetRouteFromEvent($event));
                }

            } catch (InvalidFormException $exception) {

                return $exception->getForm();
            }
        } catch (\Exception $exception) {
            $this->throwFosrestSupportedException($exception);
        }
    }

    /**
     * Get all events where both users where registered and present
     *
     * @ApiDoc(
     *   resource = true,
     *   input = "AppBundle\Entity\Event",
     *   statusCodes = {
     *     201 = "Returned when the Event is created",
     *     204 = "Returned when successful",
     *     400 = "Returned when the form has errors",
     *     401 = "Returned when not authenticated",
     *     403 = "Returned when not having permissions"
     *   }
     * )
     *
     * @param Request $request the request object
     * @param int $userId the userId id
     * @param int $eventId the event id
     *
     * @return FormTypeInterface|View
     *
     * @Get("/user/events/registered/{encounteredUserId}")
     */
    public function getEventsByUserPresence(Request $request, $encounteredUserId)
    {
        $eventRepository = $this->getRepository('AppBundle:Event');

        $events = [];
        try {
            $userId = $this->getUser()->getId();
            $events = $eventRepository->findPastRegisteredEventByUserPresence($userId, $encounteredUserId);
        } catch (\Exception $exception) {
            throw $exception;
            $events = [];
        }

        if (!$events) {
            return $this->getNoContentHttpView();
        }
        return $events;
    }

    /**
     * Creates new type from request parameters and persists it.
     *
     * @param Request $request
     * @return Event - persisted type
     */
    protected function createNewEvent($data)
    {
        $event = new Event();
        $persistedEvent = $this->processForm($event, $data, 'POST');
        return $persistedEvent;
    }

    /**
     * Processes the form.
     *
     * @param Event $event
     * @param array $parameters
     * @param String $method
     * @return Event
     *
     * @throws InvalidFormException
     */
    private function processForm(Event $event, array $parameters, $method = 'PUT')
    {
        $manager = $this->getManager();
        $form = $this->createForm('AppBundle\Form\Type\EventType', $event, ['method' => $method]);

        $form->submit($parameters, 'PATCH' !== $method);

        if ($form->isValid()) {
            $event = $form->getData();

            $placeExternalId = $form->get('placeExternalId')->getData();
            if(!empty($placeExternalId)) {
                if($event->getLocation() === null ||
                    ($event->getLocation() !== null
                        && $event->getLocation()->getExternalId() !== $placeExternalId
                    )
            ) {
                    $locationService = $this->get('location.service');
                    $location = $locationService->getLocationByExternalPlaceId($placeExternalId);
                    $event->setLocation($location);
                }
            }

            $event->setCreationDateTime($this->getCurrentDateTimeUTC());

            if($event->getId() === null) {

                $eventUserState = new EventUserState();
                $eventUserState->setName(EventUserState::CONFIRMED)
                    ->setCreationDateTime($this->getCurrentDateTimeUTC());
                $eventUser = new EventUser();
                $eventUser->setEvent($event)
                    ->setUser($event->getUser())
                    ->setCurrentState($eventUserState);
            }

            $eventService = $this->get('event.service');
            $eventService->calculateUTCInfos($event);


            if($event->getId() === null) {
                $manager->persist($eventUserState);
                $manager->persist($eventUser);
            }
            $manager->persist($event);
            $manager->flush();

            return $event;
        }

        throw new InvalidFormException('Invalid submitted data', $form);
    }

    private function generateGetRouteFromEvent($event) {
        return $this->generateUrl('get_event', [
            'id'  => $event->getId()
        ]);
    }


}