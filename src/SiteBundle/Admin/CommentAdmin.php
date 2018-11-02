<?php

namespace SiteBundle\Admin;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Show\ShowMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;

use Symfony\Component\OptionsResolver\OptionsResolver;

use Doctrine\ORM\EntityRepository;
use Application\Sonata\UserBundle\Entity\User;


use Builder\FormBundle\Manager\Form\FormBuilder;
//use AdminBundle\Form\Type\LocalisationType;
use Sonata\CoreBundle\Form\Type\DatePickerType;
use Sonata\CoreBundle\Form\Type\DateTimePickerType;
use Sonata\CoreBundle\Form\Type\DateRangeType;
use Sonata\DoctrineORMAdminBundle\Filter\DateRangeFilter;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;


class CommentAdmin extends AbstractAdmin
{

    //to add template for fields
    public function getTemplate($name)
    {
        switch ($name) {
            case 'edit':
                //var_dump( parent::getTemplate($name));
                return 'BuilderFormBundle::Admin\formbuilder_admin_template.html.twig';
                break;
            default:
                return parent::getTemplate($name);
                break;
        }
    }

    // Fields to be shown on create/edit forms
    protected function configureFormFields(FormMapper $formMapper)
    {

        //$programId = $this->getSubject()->getId();

        $em = $this->getConfigurationPool()->getContainer()->get('doctrine.orm.entity_manager');

        $f_form = $em
            ->getRepository('BuilderFormBundle:F_Form')
            ->findOneBy(array('name' => 'comment'));
        
        //$user =  $this->get('security.context')->getToken()->getUser();
        $user = $this->getConfigurationPool()->getContainer()->get('security.token_storage')->getToken()->getUser();
        //$formMapper->with($f_form->getTitle());
        
        // $formMapper->add('article', EntityType::class, array(
        //     'class' => 'SiteBundle\Entity\Article',
        //     'choice_label' => 'title',
        // ));

        $formMapper = FormBuilder::createForm($f_form, $formMapper, $user, $em);
        //$formMapper->end();
        //$entity = $formMapper->getAdmin()->getSubject();
        

        // $formMapper
        // ->add('publishedAt', DatePickerType::class, array(
        //     'label' => 'Date de publication'
        // ))

        // ->add('isActive',null,array(
        //     'label' => 'Visible'
        // ));
    }
    
    // Fields to be shown on filter forms
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('title')
            ->add('publishedAt', DateRangeFilter::class, array(
                'field_type' => DateRangeType::class,
                'label' => 'Date de publication'
            ));
    }
    
    // Fields to be shown on lists
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('id')
            ->addIdentifier('title', null, array(
                'label' => "Titre"
            ))
            ->add('actu', null, array(
                'associated_property' => 'title'
            ))
            ->add('authorName', null, array(
                'label' => "Auteur"
            ))
            ->add('content')
            // ->add('author.firstname', null, array(
            //     'label' => "Auteur (User)"
            // ))
            ->add('publishedAt', 'date', array(
                'label' => 'Date de publication',
                'format' => 'd/m/Y',
                'locale' => 'fr'
            ))
            ->add('isActive',null,array(
                'label' => 'Visible',
                'editable' => true
            ));
    }
                
    // Fields to be shown on show action
    protected function configureShowFields(ShowMapper $showMapper)
    {
        $showMapper
            ->add('title');
    }
}