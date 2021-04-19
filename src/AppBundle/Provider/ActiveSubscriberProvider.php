<?php
/**
 * Created by PhpStorm.
 * User: manii
 * Date: 30/08/2017
 * Time: 13:41
 */

namespace AppBundle\Provider;


use Doctrine\ORM\EntityRepository;
use Welp\MailchimpBundle\Provider\ProviderInterface;
use Welp\MailchimpBundle\Subscriber\Subscriber;
use AppBundle\Entity\User;

class ActiveSubscriberProvider implements ProviderInterface
{
    // these tags should match the one you added in MailChimp's backend
    const TAG_FIRSTNAME =          'FNAME';
    const TAG_LASTNAME =           'LNAME';
    const TAG_ID =                 'ID';
    const TAG_GENDER =             'GENDER';
    const TAG_BIRTHDATE =          'BIRTHDATE';
    const TAG_COUNTRY =            'COUNTRY';
    const TAG_REGISTRATION_DATE =  'DATE_REGIS';

    protected $userRepository;

    public function __construct(EntityRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function getSubscribers()
    {
        $users = $this->userRepository->findBy(['active' => true, 'confirmed' => true]);

        $subscribers = array_map(function(User $user) {

            $subscriber = new Subscriber($user->getEmail(), [
                self::TAG_ID    => $user->getId(),
                self::TAG_GENDER => $user->getProfile()===null?:$user->getProfile()->getGender(),
                self::TAG_FIRSTNAME => $user->getProfile()===null?:$user->getProfile()->getFirstName(),
                self::TAG_LASTNAME => $user->getProfile()===null?:$user->getProfile()->getLastName(),
                self::TAG_BIRTHDATE => $user->getProfile()===null?:$user->getProfile()->getBirthDate()->format('Y-m-d'),
                self::TAG_COUNTRY => $user->getProfile()===null?:$user->getProfile()->getNativeCountry(),
                self::TAG_REGISTRATION_DATE => $user->getCreationDateTime()->format('Y-m-d')
            ],[
                'language'   => 'en',
                'email_type' => 'html'
            ]);

            return $subscriber;
        }, $users);

        return $subscribers;
    }
}