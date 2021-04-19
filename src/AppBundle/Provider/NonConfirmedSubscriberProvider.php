<?php
/**
 * Created by PhpStorm.
 * User: manii
 * Date: 30/08/2017
 * Time: 13:41
 */

namespace AppBundle\Provider;


use AppBundle\Entity\UserConfirmation;
use Doctrine\ORM\EntityRepository;
use Welp\MailchimpBundle\Provider\ProviderInterface;
use Welp\MailchimpBundle\Subscriber\Subscriber;

class NonConfirmedSubscriberProvider implements ProviderInterface
{
    // these tags should match the one you added in MailChimp's backend
    const TAG_ID =                 'ID';
    const TAG_REGISTRATION_DATE =  'DATE_REGIS';
    const TAG_CONFIRMATION_KEY  =  'KEY_CONFI';

    private $userConfirmationRepository;

    public function __construct(EntityRepository $userConfirmationRepository)
    {
        $this->userConfirmationRepository = $userConfirmationRepository;
    }

    public function getSubscribers()
    {
        $userConfirmations = $this->userConfirmationRepository->findNonConfirmedUsers();

        $subscribers = array_map(function(UserConfirmation $userConfirmation) {

            $subscriber = new Subscriber($userConfirmation->getUser()->getEmail(), [
                self::TAG_ID    => $userConfirmation->getUser()->getId(),
                self::TAG_REGISTRATION_DATE => $userConfirmation->getUser()->getCreationDateTime()->format('Y-m-d'),
                self::TAG_CONFIRMATION_KEY => $userConfirmation->getToken()
            ],[
                'language'   => 'en',
                'email_type' => 'html'
            ]);

            return $subscriber;
        }, $userConfirmations);

        return $subscribers;
    }
}