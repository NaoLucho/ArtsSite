<?php
// src/Admin/CarAdmin.php

namespace AdminBundle\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Route\RouteCollection;
use Sonata\AdminBundle\Show\ShowMapper;
use Sonata\CoreBundle\Form\Type\DatePickerType;
use Sonata\CoreBundle\Form\Type\DateTimePickerType;
use Sonata\CoreBundle\Form\Type\DateRangeType;
use Sonata\DoctrineORMAdminBundle\Filter\DateRangeFilter;

class DBLogAdmin extends AbstractAdmin
{
    // protected $baseRoutePattern = 'custom_show';
    // protected $baseRouteName = 'custom_show';

    // protected function configureRoutes(RouteCollection $collection)
    // {
    //     //dump($collection);
    //     $collection->add('clone', $this->getRouterIdParameter() . '/clone');
    //     //$collection->clearExcept(['show']);
    // }

    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper->add('env')
        // ->add('createdAt', DateRangeFilter::class, array(
        //     'field_type' => DateRangeType::class,
        // ))
            ->add('createdAt', 'doctrine_orm_datetime_range', array(
                'field_type' => 'sonata_type_datetime_range_picker',
            ))
        // ->add('createdAt', 
        // //fonctionne mais type pourri (5fields...)
        // 'doctrine_orm_datetime_range', array(
        //     'widget' => 'single_text',
        // ), DateTimePickerType::class)

        //'doctrine_orm_datetime_range', array(), 'sonata_type_date_range', array('format' => 'dd-MM-yyyy', 'widget' => 'single_text', 'attr' => array('class' => 'datepicker')))
        
        // 'doctrine_orm_date_range', array(
        //     'field_type' => 'sonata_type_datetime_range',
        //     'html5' => false,
        //     'format' => 'dd-MM-yyyy', 
        //     'widget' => 'single_text', 
        //     'attr' => array('class' => 'datepicker')
        // ))
            ->add('userName')
            ->add('action', 'doctrine_orm_string', [], 'choice', [
                'choices' => [
                    'create' => 'create',
                    'remove' => 'remove',
                    'update' => 'update'
                ]
            ])
            ->add('entityName')
            ->add('entityId')
            ->add('propertyName')
            //->add('oldValue')
            ->add('newValue')
            ->add('status', 'doctrine_orm_string', [], 'choice', array(
                'choices' => [
                    'En attente' => 'waiting',
                    'Effectif' => 'done',
                    'Annulé' => 'cancelled'
                ]
            ));
    }

    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper->add('env')
            ->add('createdAt', DateTimePickerType::class)
            ->add('userName')
            ->add('action', 'choice', [
                'choices' => [
                    'create' => 'create',
                    'remove' => 'remove',
                    'update' => 'update'
                ]
            ])
            ->add('entityName')
            ->add('entityId')
            ->add('propertyName')
            //->add('oldValue')
            ->add('newValue')
            ->add('status', 'choice', [
                'choices' => [
                    'Log' => 'done',
                    'En attente' => 'waiting',
                    'Appliqué' => 'migrated',
                    'Annulé' => 'cancelled'
                ]
            ]);
    }

    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('id')
            ->add('env')
            ->add('createdAt', 'datetime', array(
                'format' => 'd/m/Y H:i:s'
            ))
            ->add('username')
            ->add('action')
            ->add('entityName')
            ->add('entityId')
            ->add('propertyName')
            ->add('oldValue')
            ->add('newValue')
            ->add('status', 'choice', [
                'choices' => [
                    'done' => 'Log',
                    'waiting' => 'En attente',
                    'migrated' => 'Appliqué',
                    'cancelled' => 'Annulé'
                ]
            ])
            // ->add('_action', null, [
            //     'actions' => [
            //         // 'show' => [],
            //         // 'edit' => [],
            //         // 'delete' => [],
            //         'clone' => [
            //             'template' => '@Admin/Migration/list__action_clone.html.twig'
            //         ]
            //     ]
            // ])
            ;
    }

    public function getDataSourceIterator()
    {
        $datagrid = $this->getDatagrid();
        $datagrid->buildPager();

        $datasourceit = $this->getModelManager()->getDataSourceIterator($datagrid, $this->getExportFields());
        $datasourceit->setDateTimeFormat('d/m/Y H:m:s'); //change this to suit your needs

        return $datasourceit;
    }

    protected function configureShowFields(ShowMapper $showMapper)
    {
        // ...
    }

    public function configureBatchActions($actions)
    {
        if ($this->hasRoute('edit') && $this->hasAccess('edit') &&
            $this->hasRoute('delete') && $this->hasAccess('delete')) {
            $actions['executer'] = [
                'ask_confirmation' => false
            ];

        }

        return $actions;
    }
}
