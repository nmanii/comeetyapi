<?php
/**
 * Created by PhpStorm.
 * User: manii
 * Date: 24/09/16
 * Time: 19:54
 */

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class EventUserInvitation
 * @package AppBundle\Entity
 *
 * @ORM\Table(uniqueConstraints={@ORM\UniqueConstraint(name="event_user", columns={"event_id", "user_id", "invited_user_id"})})
 * @ORM\Entity()
 */
class EventUserInvitation
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="Event")
     */
    protected $event;

    /**
     * @ORM\ManyToOne(targetEntity="User")
     */
    protected $user;

    /**
     * @ORM\Column(type="datetime")
     */
    protected $creationDateTime;

    /**
     * @ORM\ManyToOne(targetEntity="User")
     */
    protected $invitedUser;

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
    public function setUser($user)
    {
        $this->user = $user;
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
    }

    /**
     * @return mixed
     */
    public function getInvitedUser()
    {
        return $this->invitedUser;
    }

    /**
     * @param mixed $invitedUser
     */
    public function setInvitedUser($invitedUser)
    {
        $this->invitedUser = $invitedUser;
    }
}