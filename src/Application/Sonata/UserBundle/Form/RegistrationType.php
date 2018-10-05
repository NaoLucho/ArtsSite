<?php

namespace Application\Sonata\UserBundle\Form;


use Application\Sonata\UserBundle\Entity\User;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\EntityManagerInterface;

use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Validator\Constraints\IsTrue;

use Gregwar\CaptchaBundle\Type\CaptchaType;


class RegistrationType extends AbstractType
{

    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }


    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
        //->remove('username')
        // ->add('imageFile', FileType::class, array(
        //     'label' => 'Chargez votre avatar',
        //     'required' => false
        // ))
        // ->add('firstname', null, array(
        //     'label' => 'Prénom *',
        //     'required' => true
        // ))
        // ->add('lastname', null, array(
        //     'label' => 'Nom *',
        //     'required' => true
        // ))
        ->add('cgu', CheckboxType::class, array(
            'mapped' => false,
            'constraints' => array(
                new IsTrue(array(
                    'message' => 'Vous devez accepter les CGU avant de pouvoir passer à l\'étape suivante'
                )
                )),
                'label' => 'Je valide les CGU et la charte du site.'
        ))
        // ->add('emailCheck', CheckboxType::class, array(
        //         'mapped' => false,
        //         'required' => false,
        //         // 'constraints' => array(
        //         //     new IsTrue(array(
        //         //         'message' => 'Vous devez accepter de rendre public votre adresse Email à tous les professionnels avant de pouvoir passer à l\'étape suivante'
        //         //     ))
        //         // ),
        //         'label' => false,
        //     ))
        ->add('captcha', CaptchaType::class, array(
            'label' => 'Recopiez les lettres figurant dans l\'image avant de soumettre le formulaire :',
            'attr' => ['class' => 'captcha'],
            'label_attr' => ['class' => 'label_captcha']
        ));

        // $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
        //     $data = $event->getData();
        //     $form = $event->getForm();
        //     $event->setData($data);
        // }); 
    }

    public function getParent()
    {
        return 'FOS\UserBundle\Form\Type\RegistrationFormType';
    }

    public function getBlockPrefix()
    {
        return 'app_user_registration';
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }
    
    public function setDefaultOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => User::class
        ));
        
    }

}