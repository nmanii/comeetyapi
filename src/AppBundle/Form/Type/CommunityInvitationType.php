<?php

namespace AppBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\EmailType;

class CommunityInvitationType extends AbstractType {
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
            ->add('firstName')
            ->add('lastName')
            ->add('email')
            ->add('event')
            ->add('sended')
            ->add('sender', 'Symfony\Bridge\Doctrine\Form\Type\EntityType', array('class' => 'AppBundle:User'));
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults(
            [
                'data_class' => 'AppBundle\Entity\CommunityInvitation',
                'csrf_protection' => false,
            ]
        );
    }

    /**
     * @return string
     */
    public function getName() {
        return 'communtiy_invitation_type';
    }
}