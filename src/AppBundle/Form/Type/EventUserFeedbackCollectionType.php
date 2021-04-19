<?php

namespace AppBundle\Form\Type;

use AppBundle\Entity\EventFeedback;
use AppBundle\Entity\EventUserState;
use AppBundle\Entity\VenueFeedback;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Valid;

class EventUserFeedbackCollectionType extends AbstractType {
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
            ->add('venue',CollectionType::class,
                array(
                    'entry_type' => VenueFeedbackType::class,
                    'allow_add' => true,
                    'by_reference' => false,
                    'constraints' => array(new Valid())
                ))
            ->add('event',CollectionType::class,
                array(
                    'entry_type' => EventFeedbackType::class,
                    'allow_add' => true,
                    'by_reference' => false,
                    'constraints' => array(new Valid(), new NotBlank()),
                    'error_bubbling'=> false,
                ))
            ->add('users',CollectionType::class,
                array(
                    'entry_type' => UserFeedbackType::class,
                    'allow_add' => true,
                    'by_reference' => false,
                    'constraints' => array(new Valid())
                ));
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults(
            [
                'csrf_protection' => false
            ]
        );
    }

    /**
     * @return string
     */
    public function getName() {
        return 'event_user_feedback_collection_type';
    }
}