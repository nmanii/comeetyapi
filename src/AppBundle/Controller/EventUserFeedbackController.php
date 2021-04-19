<?php

namespace AppBundle\Controller;

use AppBundle\Entity\EventUserFeedbackState;
use AppBundle\Entity\UserLink;
use AppBundle\Exception\InvalidFormException;
use AppBundle\Form\Type\EventUserFeedbackType;
use FOS\RestBundle\View\View;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Put;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Patch;
use FOS\RestBundle\Controller\Annotations\Delete;


use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use AppBundle\Entity\EventUserFeedback;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class EventUserFeedbackController extends RestController
{
    /**
     * Update existing EventUserFeedback from the submitted data or create a new EventUserFeedback.
     * All required fields must be set within request data.
     *
     * @ApiDoc(
     *   resource = true,
     *   input = "AppBundle\Entity\EventUserFeedback",
     *   statusCodes = {
     *     201 = "Returned when the EventUserFeedback is created",
     *     204 = "Returned when successful",
     *     400 = "Returned when the form has errors",
     *     401 = "Returned when not authenticated",
     *     403 = "Returned when not having permissions"
     *   }
     * )
     *
     * @param Request $request the request object
     * @param int $userId the userId id
     * @param int $eventUserFeedbackId the eventUserFeedback id
     *
     * @return FormTypeInterface|View
     *
     * @throws NotFoundHttpException when EventUserFeedback not exist
     *
     * @Post("/events/{eventId}/user/feedback")
     */
    public function postCurrentUserEventUserFeedbackAction(Request $request, $eventId)
    {
        try {
            try {
                $userId = $this->getUser()->getId();

                $eventUserRepository = $this->getRepository('AppBundle:EventUser');
                $eventUser = $eventUserRepository->findOneConfirmedEventUserByEventIdAndUserId($eventId,$userId);

                if(!$eventUser) {
                    throw new AccessDeniedHttpException('not_registered_to_event');
                }

                $eventUserFeedbackRepository = $this->getRepository('AppBundle:EventFeedback');
                $eventUserFeedback = $eventUserFeedbackRepository->findBy(['event' => $eventId, 'user' => $userId]);

                if($eventUserFeedback) {
                    throw new UnprocessableEntityHttpException('feedback_already_given');
                }

                $this->processForm($request->request->all(), 'POST', ['event' => $eventUser->getEvent(), 'user' => $eventUser->getUser()]);

                return $this->getNoContentHttpView();

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
     * @return EventUserFeedback - persisted type
     */
    protected function createNewEventUserFeedback($data)
    {
        //$eventUserFeedback = new EventUserFeedback();
        $persistedEventUserFeedback = $this->processForm($data, 'POST');
        return $persistedEventUserFeedback;
    }

    /**
     * Processes the form.
     *
     * @param EventUserFeedback $eventUserFeedback
     * @param array $parameters
     * @param String $method
     * @return EventUserFeedback
     *
     * @throws InvalidFormException
     */
    private function processForm(array $parameters, $method = 'PUT', $additionalParameters = [])
    {

        $form = $this->createForm('AppBundle\Form\Type\EventUserFeedbackCollectionType', null, ['method' => $method]);

        $form->submit($parameters, 'PATCH' !== $method);

        if ($form->isValid()) {
            $eventUserFeedbackCollection = $form->getData();

            $manager = $this->getManager();
            foreach($eventUserFeedbackCollection['venue'] as $venueFeedback) {
                $venueFeedback->setCreationDateTime($this->getCurrentDateTimeUTC());
                $venueFeedback->setEvent($additionalParameters['event']);
                $venueFeedback->setUser($additionalParameters['user']);
                $manager->persist($venueFeedback);
            }
            foreach($eventUserFeedbackCollection['event'] as $eventFeedback) {
                $eventFeedback->setCreationDateTime($this->getCurrentDateTimeUTC());
                $eventFeedback->setEvent($additionalParameters['event']);
                $eventFeedback->setUser($additionalParameters['user']);
                $manager->persist($eventFeedback);
            }

            foreach($eventUserFeedbackCollection['users'] as $userFeedback) {
                $userFeedback->setCreationDateTime($this->getCurrentDateTimeUTC());
                $userFeedback->setEvent($additionalParameters['event']);
                $userFeedback->setUser($additionalParameters['user']);
                $manager->persist($userFeedback);

                if($userFeedback->getCategory() === 'follow') {
                    $link = new UserLink();
                    $link->setUser($additionalParameters['user'])
                        ->setTargetUser($userFeedback->getReference())
                        ->setIsCrush(0)
                        ->setType(UserLink::TYPE_FOLLOW)
                        ->setCreationDateTime($this->getCurrentDateTimeUTC());
                    $manager->persist($link);
                }
            }

            $manager->flush();

            return;
        }

        throw new InvalidFormException('Invalid submitted data', $form);
    }

    /**
     * REST action which returns eventFeedback for a given id
     * Method: GET
     *
     * @ApiDoc(
     *   resource = true,
     *   description = "Gets a EventFeedback for a given event id",
     *   output = "AppBundle\Entity\EventFeedback",
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     404 = "Returned when the page is not found"
     *   }
     * )
     *
     * @param $id
     * @return mixed
     *
     * @Get("/events/{eventId}/feedback", name="get_event_feedback")
     */
    public function getEventFeedbackAction($eventId)
    {

        $eventFeedbackRepository = $this->getRepository('AppBundle:EventFeedback');
        $eventFeedbacks = [];
        try {
            $eventFeedbacks = $eventFeedbackRepository->findBy(['event' => $eventId]);
        } catch (\Exception $exception) {
            $eventFeedbacks = [];
        }

        if (!$eventFeedbackRepository) {
            throw new NotFoundHttpException(sprintf('No feedback for event \'%s\' was not found.', $eventId));
        }

        return $eventFeedbacks;
    }

}
