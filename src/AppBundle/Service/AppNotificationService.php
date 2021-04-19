<?php

namespace AppBundle\Service;

use AppBundle\Entity\User;
use AppBundle\Entity\UserProfile;

class AppNotificationService
{
    private $appNotifier;
    private $entityManager;
    private $websiteConfiguration;

    public function __construct($appNotifier, $entityManager, $websiteConfiguration)
    {
        $this->appNotifier = $appNotifier;
        $this->entityManager = $entityManager;
        $this->websiteConfiguration = $websiteConfiguration;
    }


    public function sendEventCreationToFollower($follower, $followed, $event)
    {
        $tokens = $this->getUserTokens($follower);

        if(empty($tokens)) {
            return;
        }

        $message = $this->formatNotification(
            $followed->getProfile()->getFirstName(). ' has created a new event',
            $event->getTitle(),
            $this->websiteConfiguration.'/events/'.$event->getId()
        );

        $this->sendToUser($message, $tokens);
    }

    private function formatNotification($title, $content, $link) {
        return [
            'notifications' => [
                'title' => $title,
                'body' => $content,
                'icon' => 'apple-touch-icon.png',
                'click_action' => $link
            ],
            'data' => [
                'title' => $title,
                'body' => $content,
                'icon' => 'apple-touch-icon.png',
                'click_action' => $link
            ]
        ];
    }

    private function getUserTokens($user)
    {
        $userAppNotificationTokenRepository = $this->entityManager->getRepository('AppBundle:UserAppNotificationToken');
        $tokens = $userAppNotificationTokenRepository->findBy(['user' => $user->getId()]);

        return $tokens;
    }

    private function sendToUser($data, $tokens)
    {
        foreach($tokens as $token) {
            $data['to'] = $token->getToken();
            $this->appNotifier->send($data);
        }
    }
}