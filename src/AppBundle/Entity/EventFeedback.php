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
 * Class EventFeedback
 * @package AppBundle\Entity
 *
 * @ORM\Entity()
 */
class EventFeedback extends EventUserFeedback
{
    protected function getAllowedCategory()
    {
        return ['general'];
    }
}