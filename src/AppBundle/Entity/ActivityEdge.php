<?php
namespace AppBundle\Entity;

use Symfony\Component\Security\Core\User\UserInterface;
use Doctrine\ORM\Mapping as ORM;
use AppBundle\Entity\User;
use \Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Entity()
 * https://medium.com/@bansal_ankur/design-a-news-feed-system-6bf42e9f03fb#.exzimcqrb
 * user 15 follow user 1 (user=15, activityType=user,verb=follow, sourceId, 1, parentType=parentId=null
 * user 15 create event 2 (user=15, activityType=event,verb=create, sourceId, 2, parentType=parentId=null
 * user 15 comment (1) event (2) (user=15, activityType=event,verb=comment, sourceId, 2, parentType=parentId=null
 * user 15 comment (3) comment (1) of event 2 (user=15, activityType=comment,verb=comment, sourceId, 1, parentType=event parentId=2
 */
class ActivityEdge
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * user who created the activity
     * @ORM\ManyToOne(targetEntity="User")

     */
    private $user;

    /**
     *  photo, status, video, event,comment ...
     * @ORM\Column(type="string", length=191)
     */
    private $activityType;

    /**
     * comment, add, follow, etc..
     * @ORM\Column(type="string", length=191)
     */
    private $verb;

    /**
     * The record that the activity is related to.
     * @ORM\Column(type="integer")
     */
    private $sourceId;

    /**
     * photo, status, video, event, comment ...
     * @ORM\Column(type="string", nullable=true, length=191)
     */
    private $parentType;

    /**
     * The parent record that the activity is related to.
     * @ORM\Column(type="integer", nullable=true)
     */
    private $parentId;

    /**
     * @ORM\Column(type="datetime")
     */
    private $creationDateTime;

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
    public function getActivityType()
    {
        return $this->activityType;
    }

    /**
     * @param mixed $activityType
     */
    public function setActivityType($activityType)
    {
        $this->activityType = $activityType;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getVerb()
    {
        return $this->verb;
    }

    /**
     * @param mixed $verb
     */
    public function setVerb($verb)
    {
        $this->verb = $verb;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getSourceId()
    {
        return $this->sourceId;
    }

    /**
     * @param mixed $sourceId
     */
    public function setSourceId($sourceId)
    {
        $this->sourceId = $sourceId;

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
    public function setCreationDateTime(\DateTime $creationDateTime)
    {
        $this->creationDateTime = $creationDateTime;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getParentType()
    {
        return $this->parentType;
    }

    /**
     * @param mixed $parentType
     */
    public function setParentType($parentType)
    {
        $this->parentType = $parentType;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getParentId()
    {
        return $this->parentId;
    }

    /**
     * @param mixed $parentId
     */
    public function setParentId($parentId)
    {
        $this->parentId = $parentId;

        return $this;
    }
}
