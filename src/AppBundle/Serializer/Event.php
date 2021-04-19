<?php

namespace AppBundle\Serializer;

use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\ObjectEvent;

/**
 * Created by PhpStorm.
 * User: manii
 * Date: 15/05/2018
 * Time: 23:09
 */
class Event implements EventSubscriberInterface
{
    private $imagePath;

    public function __construct($imagePath)
    {
        $this->imagePath = $imagePath;
    }

    public static function getSubscribedEvents()
    {
        return [
            [
                'event' => 'serializer.post_serialize',
                'method' => 'onPostSerialize',
                'class' => \AppBundle\Entity\Event::class,
            ],
        ];
    }
    public function onPostSerialize(ObjectEvent $event)
    {
        /** @var News $object */
        $object = $event->getObject();
        $uri = $object->getCover();
        $url = null;
        if ($uri) {
            $url = $this->imagePath.$uri;
        }
        /** @var \JMS\Serializer\JsonSerializationVisitor $visitor */
        $visitor = $event->getVisitor();
        $visitor->setData('coverUrl', $url);
    }
}