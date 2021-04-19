<?php
namespace AppBundle\Entity;

use AppBundle\Entity\User;

use Doctrine\ORM\Mapping as ORM;

/**
 * user follow targetUser
 * @ORM\Table(uniqueConstraints={@ORM\UniqueConstraint(name="user_link_user", columns={"user_id", "target_user_id"})})
 * @ORM\Entity(repositoryClass="AppBundle\Repository\UserLinkRepository")
 */
class UserLink
{
    const TYPE_FOLLOW = 1;
    const TYPE_CRUSH = 2;
    const TYPE_BLOCK = 3;
    const TYPE_DELETE = 4;
    const TYPE_PAUSE = 5;

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="User")

     */
    private $user;

    /**
     * @ORM\ManyToOne(targetEntity="User")
     */
    private $targetUser;

    /**
     * * @ORM\Column(type="boolean")
     */
    private $isCrush;

    /**
     * @ORM\Column(type="datetime")
     */
    private $creationDateTime;

    /**
     * @ORM\Column(type="integer")
     */
    private $type;

    public function __construct()
    {
        $this->isCrush = 0;
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
    public function getTargetUser()
    {
        return $this->targetUser;
    }

    /**
     * @param mixed $targetUser
     */
    public function setTargetUser(User $targetUser)
    {
        $this->targetUser = $targetUser;

        return $this;
    }

    /**
     * @return mixed
     */
    public function isCrush()
    {
        return $this->isCrush;
    }

    /**
     * @param mixed $isCrush
     */
    public function setIsCrush($isCrush)
    {
        $this->isCrush = $isCrush;

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

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param mixed $type
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }
}