<?php

namespace AppBundle\Form\Type;

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
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

class EventType extends AbstractType {
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
            ->add('id')
            ->add('title')
            ->add('startDateTime', DateTimeType::class, array('widget' => 'single_text', 'model_timezone' => 'UTC', 'view_timezone' => 'UTC'))
            ->add('address')
            ->add('placeExternalId', null, ['mapped' => false])
            ->add('description')
            ->add('maximumCapacity', IntegerType::class )
            ->add('user', 'Symfony\Bridge\Doctrine\Form\Type\EntityType', array('class' => 'AppBundle:User'))
            ->add('public', TextType::class);
        ;
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults(
            [
                'data_class' => 'AppBundle\Entity\Event',
                'csrf_protection' => false
            ]
        );
    }

    /**
     * @return string
     */
    public function getName() {
        return 'event_type';
    }
}