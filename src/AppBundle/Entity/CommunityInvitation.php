<?php
namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class CommunityInvitation
 * @package AppBundle\Entity
 * @ORM\Table(name="community_invitation", uniqueConstraints={@ORM\UniqueConstraint(name="unique_invitation_user",columns={"sender_id", "email"})})
 * @ORM\Entity()
 */
class CommunityInvitation
{
    /**
     * @ORM\Column(type="guid")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="UUID")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=191)
     */
    private $firstName;

    /**
     * @ORM\Column(type="string", nullable=true, length=191)
     */
    private $lastName;

    /**
     * @ORM\Column(type="string", length=60)
     */
    private $email;

    /**
     * @ORM\ManyToOne(targetEntity="User")
     */
    private $sender;

    /**
     * @ORM\ManyToOne(targetEntity="Event")
     */
    private $event;

    /**
     * @ORM\Column(type="boolean")
     */
    private $sended;

    /**
     * @ORM\Column(type="string", length=24)
     */
    private $token;

    /**
     * @ORM\ManyToOne(targetEntity="User")
     */
    private $registeredUser;

    /**
     * @ORM\Column(type="datetime")
     */
    private $creationDateTime;

    public function __construct() {
        $this->sended = false;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * @param mixed $firstName
     */
    public function setFirstName($firstName)
    {
        $this->firstName = $firstName;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getLastName()
    {
        return $this->lastName;
    }

    /**
     * @param mixed $lastName
     */
    public function setLastName($lastName)
    {
        $this->lastName = $lastName;
    }

    /**
     * @return mixed
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param mixed $email
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getSender()
    {
        return $this->sender;
    }

    /**
     * @param mixed $sender
     */
    public function setSender($sender)
    {
        $this->sender = $sender;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getEvent()
    {
        return $this->event;
    }

    /**
     * @param mixed $event
     */
    public function setEvent($event)
    {
        $this->event = $event;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getSended()
    {
        return $this->sended;
    }

    /**
     * @param mixed $sended
     */
    public function setSended($sended)
    {
        $this->sended = $sended;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getRegisteredUser()
    {
        return $this->registeredUser;
    }

    /**
     * @param mixed $registeredUser
     */
    public function setRegisteredUser($registeredUser)
    {
        $this->registeredUser = $registeredUser;

        return $this;
    }

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
    public function getCreationDateTime()
    {
        return $this->creationDateTime;
    }

    /**
     * @param mixed $creationDateTime
     */
    public function setCreationDateTime($creationDateTime)
    {
        $this->creationDateTime = $creationDateTime;

        return $this;
    }
}