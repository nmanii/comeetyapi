<?php

namespace AppBundle\Controller;

use AppBundle\Entity\EventMessageUser;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use AppBundle\Exception\InvalidFormException;
use AppBundle\Form\Type\EventMessageType;
use FOS\RestBundle\View\View;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Put;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Patch;
use FOS\RestBundle\Controller\Annotations\Delete;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use AppBundle\Entity\EventMessage;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\DateTime;
use AppBundle\Entity\ActivityEdge;

class EventMessageController extends RestController
{

    /**
     * REST action which returns list of eventMessages
     * Method: GET, url: /event/{eventId}/messages
     *
     * @ApiDoc(
     *   resource = true,
     *   description = "Gets a list of eventMessage",
     *   output = "AppBundle\Entity\EventMessage",
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     404 = "Returned when the page is not found"
     *   }
     * )
     *
     * @param $id
     * @return mixed
     *
     * @Get("/events/{eventId}/messages", name="get_event_messages")
     */
    public function getEventMessagesAction($eventId)
    {
        $userId = null;
        if($this->getUser() !== null) {
            $userId = $this->getUser()->getId();
        }

        $eventUser = null;
        if($userId!= null) {
            $eventUserRepository = $this->getRepository('AppBundle:EventUser');
            $eventUser = $eventUserRepository->findOneConfirmedEventUserByEventIdAndUserId($eventId, $userId);
        }
        $eventMessageRepository = $this->getRepository('AppBundle:EventMessage');
        $eventMessages = array();

        try {
            $eventMessages = $eventMessageRepository->findByEventForCurrentUser($eventId, empty($eventUser), $userId);
        } catch (\Exception $exception) {
            $eventMessages = array();
        }

        if (empty($eventMessages)) {
            return $this->getNoContentHttpView();
        }
        return $eventMessages;
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
     * @Get("/events/{eventId}/messages/{messageId}", name="get_event_message")
     */
    public function getEventMessageAction($eventId, $messageId)
    {
        $userId = $this->getUser()->getId();

        $eventUserRepository = $this->getRepository('AppBundle:EventUser');
        $eventUser = $eventUserRepository->findOneConfirmedEventUserByEventIdAndUserId(['event'=>$eventId, 'user' => $userId]);

        $eventMessageRepository = $this->getRepository('AppBundle:EventMessage');
        $eventMessage = NULL;
        try {
            $eventMessage = $eventMessageRepository->findOneById($messageId);
        } catch (\Exception $exception) {
            $eventMessage = NULL;
        }

        if (!$eventMessage) {
            throw new NotFoundHttpException(sprintf('The resource \'%s\' was not found.', $eventId));
        }

        //if the user is not registered to the event but the message is private, error
        if(!$eventUser && $eventMessage->isPrivate() && $userId != $eventMessage->getUser()->getId()) {
            throw new AccessDeniedHttpException('not_registered_to_event');
        }

        return $eventMessage;
    }

    /**
     * Create a EventMessage from the submitted data.
     *
     * @ApiDoc(
     *   resource = true,
     *   description = "Creates a new EventMessage from the submitted data.",
     *   input = "AppBundle\Entity\EventMessage",
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
     * @Post("/events/{eventId}/user/messages")
     */
    public function postEventMessageAction(Request $request, $eventId)
    {
        $userId = $this->getUser()->getId();

        $eventUserRepository = $this->getRepository('AppBundle:EventUser');
        $eventUser = $eventUserRepository->findOneConfirmedEventUserByEventIdAndUserId($eventId, $userId);

        try {
            try {
                //Only people register to event can choose the option, else it's public
                $privateOptionProtection = [];
                if(!$eventUser){
                    $privateOptionProtection = ['private' => false];
                }
                $persistedEventMessage = $this->createNewEventMessage($privateOptionProtection + $request->request->all() + ['event' => $eventId, 'user' => $userId]);

                /* Store activity to notify followers */
                $activityEdge = new ActivityEdge();
                $activityEdge
                    ->setUser($this->getUser())
                    ->setActivityType('event-comment')
                    ->setVerb('create')
                    ->setSourceId($persistedEventMessage->getId())
                    ->setCreationDateTime($persistedEventMessage->getCreationDateTime());
                $manager = $this->getManager();
                $manager->persist($activityEdge);
                $manager->flush();


                $mailManager = $this->get('mail.manager');
                /* Send notifications to user that are register to the event */

                $registeredEventUsers = $eventUserRepository->findConfirmedUsersByEventIdOrderByStateCreationDateTime($eventId);
                foreach($registeredEventUsers as $registeredEventUser) {
                    //If it's not the current logged user
                    if($registeredEventUser->getUser()->getId() !=  $userId) {

                        if($persistedEventMessage->isPrivate()) {
                            $mailManager->sendNotificationNewPrivateComment($registeredEventUser->getUser(), $persistedEventMessage);
                        } else {
                            $mailManager->sendNotificationNewComment($registeredEventUser->getUser(), $persistedEventMessage);
                        }
                    }
                }

                /* Send notifications to user that are not register to the event but did let a comment */

                $eventMessageRepository = $this->getRepository('AppBundle:EventMessage');
                $authors = $eventMessageRepository->findMessageAuthorsByEventId($eventId);

                foreach($registeredEventUsers as $registeredEventUser) {
                    if(isset($authors[$registeredEventUser->getUser()->getId()])) {
                        unset($authors[$registeredEventUser->getUser()->getId()]);
                    }
                }

                if(isset($authors[$userId])) {
                    unset($authors[$userId]);
                }

                //Send notifications to user that leave a comment but didn't registered to the event
                foreach($authors as $author) {
                    if (!$persistedEventMessage->isPrivate()) {
                        $mailManager->sendNotificationNewComment($author, $persistedEventMessage);
                    }
                }

                return $this->getViewAfterSuccessfulCreate($persistedEventMessage, $this->generateGetRouteFromEventMessage($persistedEventMessage));

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
     * @return EventMessage - persisted type
     */
    protected function createNewEventMessage($data)
    {
        $eventMessage = new EventMessage();
        $persistedEventMessage = $this->processForm($eventMessage, $data, 'POST');
        return $persistedEventMessage;
    }

    /**
     * Processes the form.
     *
     * @param EventMessage $eventMessage
     * @param array $parameters
     * @param String $method
     * @return EventMessage
     *
     * @throws InvalidFormException
     */
    private function processForm(EventMessage $eventMessage, array $parameters, $method = 'PUT')
    {
        $form = $this->createForm('AppBundle\Form\Type\EventMessageType', $eventMessage, ['method' => $method]);

        $form->submit($parameters, 'PATCH' !== $method);

        if ($form->isValid()) {
            $eventMessage = $form->getData();
            $eventMessage->setCreationDateTime($this->getCurrentDateTimeUTC());

            $manager = $this->getManager();
            $manager->persist($eventMessage);
            $manager->flush();

            return $eventMessage;
        }

        throw new InvalidFormException('Invalid submitted data', $form);
    }

    private function generateGetRouteFromEventMessage($eventMessage) {
        return $this->generateUrl('get_event_message', [
            'messageId'  => $eventMessage->getId(),
            'eventId' => $eventMessage->getEvent()->getId()
        ]);
    }
}
