<?php

namespace AppBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use AppBundle\Entity\UserLink;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Exception\TransformationFailedException;

class UserLinkType extends AbstractType {
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
            ->add('type', TextType::class, ['invalid_message' => 'type is not valid'])
            ->add('targetUser', 'Symfony\Bridge\Doctrine\Form\Type\EntityType', ['class' => 'AppBundle:User'])
            ->add('user', 'Symfony\Bridge\Doctrine\Form\Type\EntityType', ['class' => 'AppBundle:User'])
            ->add('event', null, ['mapped' => false])
            ->get('type')
                ->addModelTransformer(new CallbackTransformer(
                    function ($typeAsId) {
                        return null;
                    },
                    function ($typeAsString) {
                        $type = strtoupper((string)$typeAsString);
                        if(!defined(UserLink::class.'::TYPE_'.$type)) {
                            throw new TransformationFailedException('type_not_valid'.$typeAsString);
                        }
                        return constant(UserLink::class.'::TYPE_'.$type);
                    }
                ))
            ;
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults(
            [
                'data_class' => 'AppBundle\Entity\UserLink',
                'csrf_protection' => false,
            ]
        );
    }

    /**
     * @return string
     */
    public function getName() {
        return 'user_link_type';
    }
}