<?php

namespace AppBundle\Service;

use AppBundle\Entity\User;
use AppBundle\Entity\UserProfile;

class MailService
{

    const DYNAMIC_TEMPLATE_MODE = 'dynamic';

    private $mailer;
    private $smtpMailer1;
    private $templating;
    private $entityManager;
    private $websiteConfiguration;
    private $sendGridTemplateEmailService;
    private $mailLogger;

    public function __construct($mailer, $smtpMailer1, $templating, $entityManager, $websiteConfiguration, $sendGridTemplateEmailService, $mailLogger)
    {
        $this->mailer = $mailer;
        $this->smtpMailer1 = $smtpMailer1;
        $this->templating = $templating;
        $this->entityManager = $entityManager;
        $this->websiteConfiguration = $websiteConfiguration;
        $this->sendGridTemplateEmailService = $sendGridTemplateEmailService;
        $this->mailLogger = $mailLogger;
    }

    private function generateSendGridData($from, $to, $data, $templateId, $dynamicTemplateMode = 'static') {
        $sendGridData = [
            'from' => [
                'name' => current($from),
                'email' => key($from)
            ],
            'template_id' => $templateId,
            'personalizations' => [
                [
                    'to'=> [[
                            'email' => $to
                        ]]
                ]
            ]
        ];

        if($dynamicTemplateMode === self::DYNAMIC_TEMPLATE_MODE) {

            $sendGridData['personalizations'][0]['dynamic_template_data'] = [];
            foreach ($data as $substitutionKey => $substitutionValue) {
                $sendGridData['personalizations'][0]['dynamic_template_data'][$substitutionKey] = (string)$substitutionValue;
            }
        } else {
            $sendGridData['personalizations'][0]['substitutions'] = [];
            foreach ($data as $substitutionKey => $substitutionValue) {
                $sendGridData['personalizations'][0]['substitutions']['[%' . $substitutionKey . '%]'] = (string)$substitutionValue;
            }
        }

        return $sendGridData;
    }

    public function getSenderInformation($sender = null)
    {
        switch($sender) {
            case 'founder':
                $information = ['team@comeety.net' => 'Founder1 - Comeety.net'];
                break;
            case 'founder2':
                $information = ['manii@comeety.net' => 'Founder2 - Comeety.net'];
                break;
            case 'team':
                $information = ['team@comeety.net' => 'Comeety.net team'];
                break;
            case 'system':
                $information = ['team@comeety.net' => 'Comeety.net'];
                break;
            default:
                $information = ['team@comeety.net' => 'Your name'];
        }
        return $information;
    }

    private function generateTrackingPixel($userId, $name)
    {
        return 'https://www.google-analytics.com/collect?v=1&t=event&ds=apiEmail&ec=email&ea=open&el='.$userId.'&cm=email&uid='.$userId.'&tid=UA-84443205-1&cn='.$name;
    }

    public function sendWelcome(UserProfile $userProfile)
    {
        /*
        $message = \Swift_Message::newInstance()
            ->setSubject('Welcome to comeety')
            ->setTo($userProfile->getUser()->getEmail())
            ->setFrom($this->getSenderInformation('founder'))
            ->setBody(
                $this->templating->render(
                    'Email/welcome.html.twig',
                    array(
                        'firstName' => $userProfile->getFirstName(),
                        'trackingPixel' => $this->generateTrackingPixel($userProfile->getUser()->getId(), 'Welcome')
                    )
                ),
                'text/html'
            )
        ;
        $this->mailer->send($message);
        */

        $data = $this->generateSendGridData(
            $this->getSenderInformation('founder'),
            $userProfile->getUser()->getEmail(),
            [
                'firstName' => $userProfile->getFirstName(),
                'trackingPixel' => $this->generateTrackingPixel($userProfile->getUser()->getId(), 'Welcome')
            ],
            $this->sendGridTemplateEmailService->getTemplateIdByType('welcome')
        );

        $this->sendGridTemplateEmailService->send($data);
    }

    public function sendEmailConfirmation($user, $userConfirmation)
    {

        $message = \Swift_Message::newInstance()
            ->setSubject('Registration confirmation')
            ->setTo($user->getEmail())
            ->setFrom($this->getSenderInformation('system'))
            ->setBody(
                $this->templating->render(
                    'Email/registrationConfirmation.html.twig',
                    array(
                        'confirmationLink' => $this->websiteConfiguration.'/registration-confirmation/'.$user->getId().'?t='.urlencode($userConfirmation->getToken()),
                        'trackingPixel' => $this->generateTrackingPixel($user->getId(), 'EmailConfirmation')

                    )
                ),
                'text/html'
            )
        ;
        $this->mailer->send($message);


        /*
        $data = $this->generateSendGridData(
            $this->getSenderInformation('system'),
            $user->getEmail(),
            ['confirmationUrl' => $this->websiteConfiguration.'/registration-confirmation/'.$user->getId().'?t='.urlencode($userConfirmation->getToken())],
            $this->sendGridTemplateEmailService->getTemplateIdByType('account_confirmation')
        );


        $this->sendGridTemplateEmailService->send($data);
        */
    }

    public function sendParticipantRegistrationToOrganiser($eventUser)
    {
        $eventUserRepository = $this->entityManager->getRepository('AppBundle:EventUser');
        $usersCount = $eventUserRepository->countConfirmedUsersByEventId($eventUser->getEvent()->getId());
        $spotsRemaining = null;
        if($eventUser->getEvent()->getMaximumCapacity() != null) {
            $spotsRemaining = $eventUser->getEvent()->getMaximumCapacity()  - $usersCount;
        }

        /*
        $message = \Swift_Message::newInstance()
            ->setSubject('A comeeter has joined your event')
            ->setTo($eventUser->getEvent()->getUser()->getEmail())
            ->setFrom($this->getSenderInformation('system'))
            ->setBody(
                $this->templating->render(
                    'Email/notification_organiser_new_registration.html.twig',
                    array('firstName' => $eventUser->getEvent()->getUser()->getProfile()->getFirstName(),
                        'participantFirstName' => $eventUser->getUser()->getProfile()->getFirstName(),
                        'participantLastName' => $eventUser->getUser()->getProfile()->getLastName(),
                        'spotsRemaining' => $spotsRemaining,
                        'eventLink' => $this->websiteConfiguration.'/events/'.$eventUser->getEvent()->getId(),
                        'eventTitle' => $eventUser->getEvent()->getTitle(),
                        'trackingPixel' => $this->generateTrackingPixel($eventUser->getEvent()->getUser()->getId(), 'ParticipantRegistrationToOrganiser')
                    )
                ),
                'text/html'
            )
        ;

        $this->mailer->send($message);
        */

        $data = $this->generateSendGridData(
            $this->getSenderInformation('system'),
            $eventUser->getEvent()->getUser()->getEmail(),
            ['firstName' => $eventUser->getEvent()->getUser()->getProfile()->getFirstName(),
                'participantFirstName' => $eventUser->getUser()->getProfile()->getFirstName(),
                'participantLastName' => $eventUser->getUser()->getProfile()->getLastName(),
                'spotsRemaining' => $spotsRemaining,
                'eventUrl' => $this->websiteConfiguration.'/events/'.$eventUser->getEvent()->getId(),
                'eventTitle' => $eventUser->getEvent()->getTitle(),
                'trackingPixel' => $this->generateTrackingPixel($eventUser->getEvent()->getUser()->getId(), 'ParticipantRegistrationToOrganiser')
            ],
            $this->sendGridTemplateEmailService->getTemplateIdByType('notification_participant_registration_to_organizer')
        );

        $this->sendGridTemplateEmailService->send($data);

    }

    public function sendParticipantCancellationToOrganiser($eventUser)
    {
        $eventUserRepository = $this->entityManager->getRepository('AppBundle:EventUser');
        $usersCount = $eventUserRepository->countConfirmedUsersByEventId($eventUser->getEvent()->getId());
        $spotsRemaining = null;
        if($eventUser->getEvent()->getMaximumCapacity() != null) {
            $spotsRemaining = $eventUser->getEvent()->getMaximumCapacity()  - $usersCount;
        }

        $message = \Swift_Message::newInstance()
            ->setSubject('A comeeter\'s registration to your event has been cancelled')
            ->setTo($eventUser->getEvent()->getUser()->getEmail())
            ->setFrom($this->getSenderInformation('system'))
            ->setBody(
                $this->templating->render(
                    'Email/notification_organiser_cancellation.html.twig',
                    array('firstName' => $eventUser->getEvent()->getUser()->getProfile()->getFirstName(),
                        'participantFirstName' => $eventUser->getUser()->getProfile()->getFirstName(),
                        'participantLastName' => $eventUser->getUser()->getProfile()->getLastName(),
                        'spotsRemaining' => $spotsRemaining,
                        'eventLink' => $this->websiteConfiguration.'/events/'.$eventUser->getEvent()->getId(),
                        'eventTitle' => $eventUser->getEvent()->getTitle(),
                        'trackingPixel' => $this->generateTrackingPixel($eventUser->getEvent()->getUser()->getId(), 'ParticipantCancellationToOrganiser')
                    )
                ),
                'text/html'
            )
        ;

        $this->mailer->send($message);
    }

    public function sendEventCreationToFollower($follower, $followed, $event)
    {

        $message = \Swift_Message::newInstance()
            ->setSubject($followed->getProfile()->getFirstName(). ' has created a new event')
            ->setTo($follower->getEmail())
            ->setFrom($this->getSenderInformation('system'))
            ->setBody(
                $this->templating->render(
                    'Email/notification_follower_new_creation.html.twig',
                    array('firstName' => $follower->getProfile()->getFirstName(),
                        'followedFirstName' => $followed->getProfile()->getFirstName(),
                        'followedLastName' => $followed->getProfile()->getLastName(),
                        'eventLink' => $this->websiteConfiguration.'/events/'.$event->getId(),
                        'eventTitle' => $event->getTitle(),
                        'trackingPixel' => $this->generateTrackingPixel($follower->getId(), 'EventCreationToFollower')
                    )
                ),
                'text/html'
            )
        ;


        $this->smtpMailer1->send($message);
        /*
        $data = $this->generateSendGridData(
            $this->getSenderInformation('system'),
            $follower->getEmail(),
            [
                'firstName' => $follower->getProfile()->getFirstName(),
                'followedFirstName' => $followed->getProfile()->getFirstName(),
                'followedLastName' => $followed->getProfile()->getLastName(),
                'eventUrl' => $this->websiteConfiguration.'/events/'.$event->getId(),
                'eventTitle' => $event->getTitle(),
                'trackingPixel' => $this->generateTrackingPixel($follower->getId(), 'EventCreationToFollower')
            ],
            $this->sendGridTemplateEmailService->getTemplateIdByType('notification_event_creation_to_follower')
        );

        $this->sendGridTemplateEmailService->send($data);
        */
    }

    public function sendFeedbackRequestToParticipant($event, $user)
    {
        $message = \Swift_Message::newInstance()
            ->setSubject('How did '.$event->getUser()->getProfile()->getFirstname().'\'s event go? Share your feedback about '.$event->getTitle().'')
            ->setTo($user->getEmail())
            ->setFrom($this->getSenderInformation('system'))
            ->setBody(
                $this->templating->render(
                    'Email/feedback_participant.html.twig',
                    array('firstName' => $user->getProfile()->getFirstname(),
                        'eventFeedbackLink' => $this->websiteConfiguration.'/events/'.$event->getId().'/feedback',
                        'eventLink' => $this->websiteConfiguration.'/events/'.$event->getId(),
                        'eventOrganizerFirstName' => $event->getUser()->getProfile()->getFirstname(),
                        'eventTitle' => $event->getTitle(),
                        'trackingPixel' => $this->generateTrackingPixel($user->getId(), 'FeedbackRequestToParticipant')
                    )
                ),
                'text/html'
            )
        ;

        $this->mailer->send($message);
    }

    public function sendFeedbackRequestToOrganizer($event, $user)
    {
        $message = \Swift_Message::newInstance()
            ->setSubject('How did your event go? Share your feedback about '.$event->getTitle().'')
            ->setTo($user->getEmail())
            ->setFrom($this->getSenderInformation('system'))
            ->setBody(
                $this->templating->render(
                    'Email/feedback_organizer.html.twig',
                    array('firstName' => $user->getProfile()->getFirstname(),
                        'eventFeedbackLink' => $this->websiteConfiguration.'/events/'.$event->getId().'/feedback',
                        'eventLink' => $this->websiteConfiguration.'/events/'.$event->getId(),
                        'eventOrganizerFirstName' => $event->getUser()->getProfile()->getFirstname(),
                        'eventTitle' => $event->getTitle(),
                        'trackingPixel' => $this->generateTrackingPixel($user->getId(), 'FeedbackRequestToOrganizer')
                    )
                ),
                'text/html'
            )
        ;

        $this->mailer->send($message);
    }

    public function sendNotificationNewPrivateComment($registeredUser, $eventMessage)
    {
        $message = \Swift_Message::newInstance()
            ->setSubject('New private comment on '. $eventMessage->getEvent()->getTitle())
            ->setTo($registeredUser->getEmail())
            ->setFrom($this->getSenderInformation('system'))
            ->setBody(
                $this->templating->render(
                    'Email/notification_event_new_private_comment.html.twig',
                    array('firstName' => $registeredUser->getProfile()->getFirstName(),
                        'eventLink' => $this->websiteConfiguration.'/events/'.$eventMessage->getEvent()->getId(),
                        'eventTitle' => $eventMessage->getEvent()->getTitle(),
                        'commentContent' => $eventMessage->getContent(),
                        'commentAuthorFirstName' => $eventMessage->getUser()->getProfile()->getFirstName(),
                        'trackingPixel' => $this->generateTrackingPixel($registeredUser->getId(), 'NotificationNewPrivateComment')
                    )
                ),
                'text/html'
            )
        ;

        $this->mailer->send($message);
    }

    public function sendNotificationNewComment($registeredUser, $eventMessage)
    {
        $message = \Swift_Message::newInstance()
            ->setSubject('New comment on "'. $eventMessage->getEvent()->getTitle().'"')
            ->setTo($registeredUser->getEmail())
            ->setFrom($this->getSenderInformation('system'))
            ->setBody(
                $this->templating->render(
                    'Email/notification_event_new_comment.html.twig',
                    array('firstName' => $registeredUser->getProfile()->getFirstName(),
                        'eventLink' => $this->websiteConfiguration.'/events/'.$eventMessage->getEvent()->getId(),
                        'eventTitle' => $eventMessage->getEvent()->getTitle(),
                        'commentContent' => $eventMessage->getContent(),
                        'commentAuthorFirstName' => $eventMessage->getUser()->getProfile()->getFirstName(),
                        'trackingPixel' => $this->generateTrackingPixel($registeredUser->getId(), 'NotificationNewComment')
                    )
                ),
                'text/html'
            )
        ;

        $this->mailer->send($message);
    }

    public function sendNewPasswordResetToken($user, $token)
    {
        $message = \Swift_Message::newInstance()
            ->setSubject('[Comeety] Password reset request')
            ->setTo($user->getEmail())
            ->setFrom($this->getSenderInformation('team'))
            ->setBody(
                $this->templating->render(
                    'Email/password_reset_request.html.twig',
                    array('user' => $user,
                        'passwordResetLink' => $this->websiteConfiguration.'/password-reset?token='.$token->getToken(),
                        'trackingPixel' => $this->generateTrackingPixel($user->getId(), 'NewPasswordResetToken')
                    )
                ),
                'text/html'
            )
        ;

        $this->mailer->send($message);
    }

    public function sendPasswordModified($user)
    {
        $message = \Swift_Message::newInstance()
            ->setSubject('[Comeety] Password successfully changed')
            ->setTo($user->getEmail())
            ->setFrom($this->getSenderInformation('team'))
            ->setBody(
                $this->templating->render(
                    'Email/password_modified.html.twig',
                    array(
                        'user' => $user,
                        'trackingPixel' => $this->generateTrackingPixel($user->getId(), 'PasswordModified')
                    )
                ),
                'text/html'
            )
        ;

        $this->mailer->send($message);
    }

    public function sendCommunityInvitation($communityInvitation)
    {
        $linkData = [
                'token'=> $communityInvitation->getToken(),
                'invite'=>$communityInvitation->getId(),
                'email'=>$communityInvitation->getEmail()
        ];
        $message = \Swift_Message::newInstance()
            ->setSubject($communityInvitation->getSender()->getProfile()->getFirstName().' would like to connect with you')
            ->setTo($communityInvitation->getEmail())
            ->setFrom($this->getSenderInformation('system'))
            ->setBody(
                $this->templating->render(
                    'Email/community_invitation.html.twig',
                    array(
                        'sender' => $communityInvitation->getSender(),
                        'firstName' => $communityInvitation->getFirstName(),
                        'event' => $communityInvitation->getEvent(),
                        'registrationLink' => $this->websiteConfiguration.'/registration?'.http_build_query($linkData),
                        'trackingPixel' => $this->generateTrackingPixel('invitedUnknownUser', 'CommunityInvitation')
                        )
                ),
                'text/html'
            )
        ;

        $this->mailer->send($message);
    }

    public function sendEventCreationUserNeverCreatedRequest($user, $participationCount)
    {
        $data = $this->generateSendGridData(
            $this->getSenderInformation('system'),
            $user->getEmail(),
            [
                'firstName' => $user->getProfile()->getFirstName(),
                'eventParticipationCount' => $participationCount,
                'trackingPixel' => $this->generateTrackingPixel($user->getId(), 'eventCreationRequestUserNeverCreated')
            ],
            $this->sendGridTemplateEmailService->getTemplateIdByType('event_creation_request_user_never_created')
        );

        $this->sendGridTemplateEmailService->send($data);
        $this->mailLogger->log($user, 'eventCreationRequestUserNeverCreated');
    }

    public function sendEventCreationUserAlreadyCreatedRequest($user, $participationCount)
    {
        $data = $this->generateSendGridData(
            $this->getSenderInformation('system'),
            $user->getEmail(),
            [
                'firstName' => $user->getProfile()->getFirstName(),
                'eventParticipationCount' => $participationCount,
                'trackingPixel' => $this->generateTrackingPixel($user->getId(), 'eventCreationRequestUserAlreadyCreated')
            ],
            $this->sendGridTemplateEmailService->getTemplateIdByType('event_creation_request_user_already_created')
        );

        $this->sendGridTemplateEmailService->send($data);
        $this->mailLogger->log($user, 'eventCreationRequestUserAlreadyCreated');
    }

    public function sendZeroTrustScoreReachedMail($user)
    {
        $data = $this->generateSendGridData(
            $this->getSenderInformation('system'),
            $user->getEmail(),
            [
                'firstName' => $user->getProfile()->getFirstName(),
                'trackingPixel' => $this->generateTrackingPixel($user->getId(), 'zeroTrustScoreMail')
            ],
            $this->sendGridTemplateEmailService->getTemplateIdByType('zero_trust_score')
        );

        $this->sendGridTemplateEmailService->send($data);
        $this->mailLogger->log($user, 'zeroTrustScoreMail');
    }

    public function sendNoShowReportedWarning($reportedUser, $userFeedback)
    {
        $data = $this->generateSendGridData(
            $this->getSenderInformation('system'),
            $reportedUser->getEmail(),
            [
                'firstName' => $reportedUser->getProfile()->getFirstName(),
                'eventTitle' => $userFeedback->getEvent()->getTitle(),
                'trackingPixel' => $this->generateTrackingPixel($reportedUser->getId(), 'noShowReportedWarning')
            ],
            $this->sendGridTemplateEmailService->getTemplateIdByType('no_show_reported_warning')
        );

        $this->sendGridTemplateEmailService->send($data);
        $this->mailLogger->log($reportedUser, 'noShowReportedWarning');
    }

    public function sendInvitationToEventByOrganiser($event, $user, $invitedUser)
    {
        $data = $this->generateSendGridData(
            $this->getSenderInformation('system'),
            $invitedUser->getEmail(),
            [
                'invitedFirstName' => $invitedUser->getProfile()->getFirstName(),
                'inviterFirstName' => $user->getProfile()->getFirstName(),
                'inviterLastName' => $user->getProfile()->getLastName(),
                'eventTitle' => $event->getTitle(),
                'eventUrl' => $this->websiteConfiguration.'/events/'.$event->getId(),
            ],
            $this->sendGridTemplateEmailService->getTemplateIdByType('event_invitation_by_organiser')
        );

        $this->sendGridTemplateEmailService->send($data);
        $this->mailLogger->log($invitedUser, 'invitationToEventByOrganizer');
    }

    public function sendInvitationToEventByParticipant($event, $user, $invitedUser)
    {
        $data = $this->generateSendGridData(
            $this->getSenderInformation('system'),
            $invitedUser->getEmail(),
            [
                'invitedFirstName' => $invitedUser->getProfile()->getFirstName(),
                'inviterFirstName' => $user->getProfile()->getFirstName(),
                'inviterLastName' => $user->getProfile()->getLastName(),
                'eventTitle' => $event->getTitle(),
                'eventUrl' => $this->websiteConfiguration.'/events/'.$event->getId(),
            ],
            $this->sendGridTemplateEmailService->getTemplateIdByType('event_invitation_by_participant')
        );

        $this->sendGridTemplateEmailService->send($data);
        $this->mailLogger->log($invitedUser, 'invitationToEventByParticipant');
    }

    public function sendFounderWelcome($user)
    {
        $data = $this->generateSendGridData(
            $this->getSenderInformation('founder2'),
            $user->getEmail(),
            ['userFirstName' => $user->getProfile()->getFirstName()],
            $this->sendGridTemplateEmailService->getTemplateIdByType('founder_welcome')
        );

        $this->sendGridTemplateEmailService->send($data);
        $this->mailLogger->log($user, 'founder_welcome');
    }

    public function sendFounderNonParticipationEnquiry($user)
    {
        $data = $this->generateSendGridData(
            $this->getSenderInformation('founder2'),
            $user->getEmail(),
            ['userFirstName' => $user->getProfile()->getFirstName()],
            $this->sendGridTemplateEmailService->getTemplateIdByType('founder_non_participation_enquiry')
        );

        $this->sendGridTemplateEmailService->send($data);
        $this->mailLogger->log($user, 'founder_non_participation_enquiry');
    }

    public function sendEventReminder1Day($user, $event)
    {
        $data = $this->generateSendGridData(
            $this->getSenderInformation('system'),
            $user->getEmail(),
            [
                'firstName' => $user->getProfile()->getFirstName(),
                'eventTitle' => $event->getTitle(),
                'eventTime' => $event->getStartDateTime()->format('H:i'),
                'eventUrl' => $this->websiteConfiguration.'/events/'.$event->getId(),
            ],
            $this->sendGridTemplateEmailService->getTemplateIdByType('event_reminder_1day')
        );

        $this->sendGridTemplateEmailService->send($data);
    }

    public function sendEventReminderOrganiser1Day($user, $event)
    {
        $data = $this->generateSendGridData(
            $this->getSenderInformation('system'),
            $user->getEmail(),
            [
                'firstName' => $user->getProfile()->getFirstName(),
                'eventTitle' => $event->getTitle(),
                'eventTime' => $event->getStartDateTime()->format('H:i'),
                'eventUrl' => $this->websiteConfiguration.'/events/'.$event->getId(),
            ],
            $this->sendGridTemplateEmailService->getTemplateIdByType('event_reminder_organiser_1day'),
            self::DYNAMIC_TEMPLATE_MODE
        );

        $this->sendGridTemplateEmailService->send($data);
    }

    public function sendDiscordNameChange($userProfile, $oldDiscordName)
    {
        $message = \Swift_Message::newInstance()
            ->setSubject('Discord name access to change')
            ->setTo($this->getSenderInformation('team'))
            ->setFrom($this->getSenderInformation('team'))
            ->setBody(
                $this->templating->render(
                    'Email/discord_name_changed.html.twig',
                    array(
                        'userProfile' => $userProfile,
                        'oldDiscordName' => $oldDiscordName,
                        'trackingPixel' => $this->generateTrackingPixel($userProfile->getUser()->getId(), 'DiscordNameChanged')
                    )
                ),
                'text/html'
            )
        ;

        $this->mailer->send($message);
    }


}