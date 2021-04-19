<?php

namespace AppBundle\Form\Type;

use AppBundle\Entity\EventUserState;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints\Valid;

class EventUserInvitationType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options) {
        parent::buildForm($builder, $options);
        $builder
            ->add('event', 'Symfony\Bridge\Doctrine\Form\Type\EntityType', ['class' => 'AppBundle:Event'])
            ->add('user', 'Symfony\Bridge\Doctrine\Form\Type\EntityType', ['class' => 'AppBundle:User'])
            ->add('invitedUser', 'Symfony\Bridge\Doctrine\Form\Type\EntityType', ['class' => 'AppBundle:User'])
        ;
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults(
            [
                'data_class' => 'AppBundle\Entity\EventUserInvitation',
                'csrf_protection' => false
            ]
        );
    }

    /**
     * @return string
     */
    public function getName() {
        return 'event_user_invitation_type';
    }
}