<?php
namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class EventUserFeedback
 * @package AppBundle\Entity
 *
 * @ORM\Table(uniqueConstraints={@ORM\UniqueConstraint(name="event_user_type_category", columns={"event_id", "user_id", "category"})})
 * @ORM\MappedSuperclass
 */
abstract class EventUserFeedback
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
     * @ORM\Column(type="string", length=191)
     */
    protected $category;

    /**
     * @ORM\Column(type="string", length=191)
     * bad, good, neutral
     */
    protected $rating;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    protected $comment;

    /**
     * @ORM\Column(type="datetime")
     */
    protected $creationDateTime;




    /**
     * @return mixed
     */
    public function getEvent()
    {
        return $this->event;
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

        return $this;
    }

    /**
     * @return mixed
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * @param mixed $typeCategory
     */
    public function setCategory($category)
    {
        if(in_array($category, $this->getAllowedCategory())) {
            $this->category = $category;

            return $this;
        } else {
            throw new \Exception('Non authorized category '.$category.' for '. get_class($this));
        }
    }

    /**
     * @return mixed
     */
    public function getRating()
    {
        return $this->rating;
    }

    /**
     * @param mixed $rating
     */
    public function setRating($rating)
    {
        $this->rating = $rating;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * @param mixed $comment
     */
    public function setComment($comment)
    {
        $this->comment = $comment;

        return $this;
    }

    protected function getAllowedCategory() {
        return [];
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