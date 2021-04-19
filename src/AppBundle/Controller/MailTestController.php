<?php
/**
 * Created by PhpStorm.
 * User: manii
 * Date: 01/12/16
 * Time: 21:27
 */

namespace AppBundle\Controller;

use AppBundle\Handler\MailHandler;
use FOS\RestBundle\Controller\Annotations\Get;
use AppBundle\Controller\RestController;

class MailTestController extends RestController {
    /**
     * REST action which returns list of events
     * Method: GET, url: /events
     *
     * @param $id
     * @return mixed
     *
     * @Get("/email", name="get_email")
     */
    public function getMail()
    {
        /*$message = \Swift_Message::newInstance()
            ->setSubject('Feedback')
            ->setTo('developpeur1337@gmail.com')
            ->setFrom('developpeur1337@gmail.com')
            ->setBody(
                $this->renderView(
                    'Email/feedback_participant.html.twig',
                    array('firstName' => 'toto', 'eventFeedbackLink' => 'http://www.google.com', 'eventLink' => 'http://www.google.com', 'eventTitle' => 'marché de noel')
                ),
                'text/html'
            )
        ;*/
        /*
        $message = \Swift_Message::newInstance()
            ->setSubject('Feedback')
            ->setTo('developpeur1337@gmail.com')
            ->setFrom('developpeur1337@gmail.com')
            ->setBody(
                $this->renderView(
                    'Email/notification_organiser_new_registration.twig',
                    array('firstName' => 'toto', 'participantFirstName' => 'Manii', 'participantLastName' => 'Nada', 'spotsRemaining' => 2, 'eventLink' => 'http://www.google.com', 'eventTitle' => 'marché de noel')
                ),
                'text/html'
            )
        ;*/

        $mailManager = $this->get('app_notification.service');
        $eventRepository = $this->getRepository('AppBundle:Event');
        $userRepository = $this->getRepository('AppBundle:User');
        //$users = $userRepository->findActiveWithProfileFilledUsers();
        $users = $userRepository->findBy(array('id' => 2));
        $event = $eventRepository->findOneBy(['id' => 1]);

        foreach($users as $user) {

            $mailManager->sendEventCreationToFollower($user, $event->getUser(), $event);

        }
        return array();
        //$this->get('mailer')->send($message);
    }
}