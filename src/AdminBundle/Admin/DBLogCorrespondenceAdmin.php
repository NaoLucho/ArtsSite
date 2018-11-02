<?php
// src/Admin/CarAdmin.php

namespace AdminBundle\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Sonata\CoreBundle\Form\Type\DatePickerType;
use Sonata\CoreBundle\Form\Type\DateTimePickerType;
use Sonata\CoreBundle\Form\Type\DateRangeType;
use Sonata\DoctrineORMAdminBundle\Filter\DateRangeFilter;

class DBLogCorrespondenceAdmin extends AbstractAdmin
{

    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper->add('env')
            ->add('createdAt', 'doctrine_orm_datetime_range', array(
                'field_type' => 'sonata_type_datetime_range_picker',
            ))
            ->add('entityName')
            ->add('entityIdDistant')
            ->add('entityIdLocal')
            ;
    }

    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper->add('env')
            ->add('createdAt', DateTimePickerType::class)
            ->add('entityName')
            ->add('entityIdDistant')
            ->add('entityIdLocal');
    }

    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('id')
            ->add('env')
            ->add('createdAt', 'datetime', array(
                'format' => 'd/m/Y H:i:s'
            ))
            ->add('entityName')
            ->add('entityIdDistant')
            ->add('entityIdLocal')
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

}
