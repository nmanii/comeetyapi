<?php
/**
 * Created by PhpStorm.
 * User: manii
 * Date: 05/06/2017
 * Time: 19:18
 */

namespace AppBundle\Service;

use AppBundle\Entity\Billing\Plan;
use AppBundle\Entity\Billing\Subscription;
use Symfony\Component\Config\Definition\Exception\Exception;

class SubscriptionService
{
    private $entityManager;

    private $planRepository;

    private $subscriptionRepository;

    const DEFAULT_PLAN_NAME = 'Free';

    public function __construct($entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function createSubscriptionForNewUser($user)
    {

        $this->createNewUserSubscription($user, static::DEFAULT_PLAN_NAME);
        $this->entityManager->flush();
    }

    public function createNewUserSubscription($user, $planName)
    {
        $activeSubscription = $this->getSubscriptionRepository()->findOneBy(['user' => $user, 'active' => 1]);
        if(!empty($activeSubscription)) {
            $this->deactivateUserActiveSubscription($user, $activeSubscription);
        }

        $plan = $this->getPlanRepository()->findOneByName($planName);
        if(empty($plan)) {
            throw new \Exception('plan_not_found');
        }

        if(!empty($activeSubscription && $activeSubscription->getPlan() === $plan)) {
            throw new  \Exception('already_subscribed_to_plan');
        }
        $subscription = $this->addNewSubscriptionToUser($user, $plan);

        $this->entityManager->flush();

        return $subscription;
    }

    private function deactivateUserActiveSubscription($user, $activeSubscription)
    {
        $activeSubscription->setActive(false);
        $activeSubscription->setEndDate($this->getCurrentDateTimeUTC());
        //remove user role
        $user->removeRole($activeSubscription->getPlan()->getRoleName());
        $this->entityManager->persist($activeSubscription);
        $this->entityManager->persist($user);
    }

    private function addNewSubscriptionToUser($user, $plan)
    {
        $subscription = new Subscription();
        $subscription->setUser($user);
        $subscription->setActive(true);
        $subscription->setCreationDateTime($this->getCurrentDateTimeUTC());
        $subscription->setStartDate($this->getCurrentDateTimeUTC());
        $subscription->setPlan($plan);

        //add role to user
        $user->addRole($subscription->getPlan()->getRoleName());
        $this->entityManager->persist($subscription);
        $this->entityManager->persist($user);
        return $subscription;

    }

    private function getCurrentDateTimeUTC()
    {
        return new \DateTime('now', new \DateTimeZone('UTC'));
    }

    private function getSubscriptionRepository()
    {
        return $this->entityManager->getRepository(Subscription::class);
    }

    private function getPlanRepository()
    {
        return $this->entityManager->getRepository(Plan::class);
    }
}