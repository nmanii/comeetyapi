<?php

namespace AppBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserProfileType extends AbstractType {
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
            ->add('firstName')
            ->add('lastName')
            ->add('birthDate', DateType::class, array('widget' => 'single_text'))
            ->add('gender')
            ->add('motto')
            ->add('nativeCountry')
            ->add('discordName')
            ->add('user', 'Symfony\Bridge\Doctrine\Form\Type\EntityType', array('class' => 'AppBundle:User'));
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults(
            [
                'data_class' => 'AppBundle\Entity\UserProfile',
                'csrf_protection' => false,
            ]
        );
    }

    /**
     * @return string
     */
    public function getName() {
        return 'user_profile_type';
    }
}