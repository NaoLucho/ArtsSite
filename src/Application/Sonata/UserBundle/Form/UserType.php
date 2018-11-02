<?php

namespace Application\Sonata\UserBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\EntityManagerInterface;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;


class UserType extends AbstractType {

    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
        //->remove('username')
        ->add('lastname', null, array(
            'label' => 'Nom'
        ))
        ->add('firstname',null, array(
            'label' => 'Prénom'
        ))
        ->add('email')
        ->add('imageFile', FileType::class, array(
            'required' => false,
            'label' => 'Avatar'
        ))
        ->add('bannerFile', FileType::class, array(
            'required' => false,
            'label' => 'Bannière'
        ))
        ->add('description', null, array(
            'label' => 'Description'
        ));
    }


    public function getParent() {
        return 'FOS\UserBundle\Form\Type\ProfileFormType';
    }

    public function getBlockPrefix() {
        return 'app_user_profile';
    }
}