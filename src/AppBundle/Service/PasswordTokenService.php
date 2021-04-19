<?php
/**
 * Created by PhpStorm.
 * User: manii
 * Date: 30/12/16
 * Time: 18:59
 */

namespace AppBundle\Service;


use AppBundle\Entity\User;
use AppBundle\Entity\UserPasswordResetToken;
use Doctrine\ORM\EntityManager;

class PasswordTokenService
{
    private $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param User $user
     * @return UserPasswordResetToken
     *
     * Create a new password token and cancel all the previous one
     */
    public function resetAndCreateNewsPasswordToken(User $user)
    {
        $this->deactivateAllPasswordResetToken($user);
        return $this->createNewPasswordResetToken($user);
    }

    /**
     * Deactivate all the password reset token for the specified user
     */
    public function deactivateAllPasswordResetToken(User $user)
    {
        $passwordResetTokenRepository = $this->entityManager->getRepository('AppBundle:UserPasswordResetToken');
        $passwordResetTokenRepository->resetAllUserPasswordResetTokenByUserId($user->getId());
    }

    /**
     * Create a new password token
     */
    public function createNewPasswordResetToken(User $user)
    {
        $userPasswordResetToken = new UserPasswordResetToken();
        $date = new \DateTime();
        //Token valid for one day
        $endValidityDateTime = $date->add(new \DateInterval('PT24H'));
        $userPasswordResetToken->setUser($user)
            ->setToken(base64_encode(openssl_random_pseudo_bytes(16)))
            ->setExpirationDateTime($endValidityDateTime)
            ->setActive(true);
        $this->entityManager->persist($userPasswordResetToken);
        $this->entityManager->flush();
        return $userPasswordResetToken;
    }

    private function generatePasswordToken()
    {
        return base64_encode(openssl_random_pseudo_bytes(16));
    }
}