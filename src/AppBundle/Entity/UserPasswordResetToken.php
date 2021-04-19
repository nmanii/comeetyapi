<?php
namespace AppBundle\Entity;

use Symfony\Component\Security\Core\User\UserInterface;
use Doctrine\ORM\Mapping as ORM;
use AppBundle\Entity\User;

/**
 * @ORM\Entity(repositoryClass="AppBundle\Repository\PasswordResetTokenRepository")
 */
class UserPasswordResetToken
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=24)
     */
    private $token;

    /**
     * @ORM\Column(type="datetime")
     */
    private $expirationDateTime;

    /**
     * @ORM\ManyToOne(targetEntity="User")
     */
    private $user;

    /**
     * @ORM\Column(type="boolean")
     */
    private $active;

    /**
     * @return mixed
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @param mixed $token
     */
    public function setToken($token)
    {
        $this->token = $token;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param mixed $user
     */
    public function setUser(User $user)
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getExpirationDateTime()
    {
        return $this->expirationDateTime;
    }

    /**
     * @param mixed $expirationDateTime
     */
    public function setExpirationDateTime($expirationDateTime)
    {
        $this->expirationDateTime = $expirationDateTime;
        return $this;
    }

    /**
     * @return mixed
     */
    public function isActive()
    {
        return $this->active;
    }

    /**
     * @param mixed $active
     */
    public function setActive($active)
    {
        $this->active = $active;

        return $this;
    }
}
