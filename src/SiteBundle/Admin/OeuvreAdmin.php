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
use Symfony\Component\Validator\Constraints\IsTrueValidator;
use Sonata\CoreBundle\Form\Type\CollectionType;

// use Doctrine\ORM\Event\LifecycleEventArgs;
// use Doctrine\ORM\Event\PostUpdateEventArgs;

class OeuvreAdmin extends AbstractAdmin
{

    //to add template for fields
    public function getTemplate($name)
    {
        switch ($name) {
            case 'edit':
                //var_dump( parent::getTemplate($name));
                return 'AdminBundle::Field\Admin\Form\admin_fields_article_template.html.twig';
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
            ->findOneBy(array('name' => 'oeuvre'));
        
        //$user =  $this->get('security.context')->getToken()->getUser();
        $user = $this->getConfigurationPool()->getContainer()->get('security.token_storage')->getToken()->getUser();
        //$formMapper->with($f_form->getTitle());
        $formMapper = FormBuilder::createForm($f_form, $formMapper, $user, $em);
        //$formMapper->end();
        //$entity = $formMapper->getAdmin()->getSubject();

        
        $formMapper->add('publishedAt', DatePickerType::class, array(
            'label' => 'Date de publication'
        ))

        ->add('isActive',null,array(
            'label' => 'Visible'
        ))
        ;

        //NOT WORK CORRECTLY 4H de tentative échouée
        // POURQUOI??? pourtant cela fonctionne très bien avec GList et Menu, Page et Formulaire..
        // DECISION => ARRETER D'utiliser Sonata Admin, infernal gouffre incompréhensible..
        // $formMapper->add('comments', 'sonata_type_collection', 
        //     array(
        //         'by_reference' => false,
        //         'btn_add' => 'Ajouter',
        //         'type_options' => array(
        //             'delete' => true
        //         )
        //     ), array(
        //         'edit' => 'inline',
        //         'allow_add' => true,
        //         'inline' => 'table',
        //         'sortable' => 'publishedAt',
        // ));
        
    }
    
    // Fields to be shown on filter forms
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('name')
            ->add('publishedAt', DateRangeFilter::class, array(
                'field_type' => DateRangeType::class,
                'label' => 'Date de publication'
            ));
    }
    
    // Fields to be shown on lists
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('name', null, array(
                'label' => "Nom"
            ))
            ->add('slug')
            ->add('publishedAt', 'date', array(
                'label' => 'Date de publication',
                'format' => 'd/m/Y',
                'locale' => 'fr'
            ))
            ->add('artist.firstname', null, array(
                'label' => "artiste"
            ))
            ->add('typesOeuvre', null, array(
                'associated_property' => 'name',
                'label' => "Type d'oeuvre"
            ))
            ->add('categoriesArt', null, array(
                'associated_property' => 'name',
                'label' => "Categorie d'art"
            ))
            ->add('isActive',null,array(
                'label' => 'Visible',
                'editable' => true
            ))
            ;
    }
                
    // Fields to be shown on show action
    protected function configureShowFields(ShowMapper $showMapper)
    {
        $showMapper
            ->add('name');
    }
}