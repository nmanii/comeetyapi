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
 * Class VenueFeedback
 * @package AppBundle\Entity
 *
 * @ORM\Table(uniqueConstraints={@ORM\UniqueConstraint(name="event_user_type_category", columns={"event_id", "user_id", "reference_id", "category"})})
 * @ORM\Entity(repositoryClass="AppBundle\Repository\UserFeedbackRepository")
 */
class UserFeedback extends EventUserFeedback
{
    /**
     * @ORM\ManyToOne(targetEntity="User")
     */
    protected $reference;

    /**
     * @return mixed
     */
    public function getReference()
    {
        return $this->reference;
    }

    /**
     * @param mixed $reference
     */
    public function setReference($reference)
    {
        $this->reference = $reference;

        return $this;
    }



    protected function getAllowedCategory()
    {
        return ['general', 'follow'];
    }
}