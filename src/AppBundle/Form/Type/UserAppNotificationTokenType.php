<?php

namespace AppBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserAppNotificationTokenType extends AbstractType {
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
            ->add('token')
            ->add('user', 'Symfony\Bridge\Doctrine\Form\Type\EntityType', array('class' => 'AppBundle:User'));
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults(
            [
                'data_class' => 'AppBundle\Entity\UserAppNotificationToken',
                'csrf_protection' => false,
            ]
        );
    }

    /**
     * @return string
     */
    public function getName() {
        return 'user_app_notification_type';
    }
}