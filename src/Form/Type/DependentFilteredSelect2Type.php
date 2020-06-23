<?php

namespace Shtumi\UsefulBundle\Form\Type;

use Doctrine\Common\Collections\Collection;
use Shtumi\UsefulBundle\Form\DataTransformer\EntityToIdTransformer;
use Shtumi\UsefulBundle\Form\DataTransformer\EntityToSelect2ValueTransformer;
use Symfony\Bridge\Doctrine\Form\DataTransformer\CollectionToArrayTransformer;
use Symfony\Bridge\Doctrine\Form\EventListener\MergeDoctrineCollectionListener;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DependentFilteredSelect2Type extends AbstractType
{

    private $container;

    public function __construct($container)
    {
        $this->container = $container;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'empty_value'       => '',
            'entity_alias'      => null,
            'parent_field'      => null,
            'compound'          => false,
            'multiple'          => 0
        ));
    }

    public function getBlockPrefix()
    {
        return 'shtumi_dependent_filtered_select2';
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $entities = $this->container->getParameter('shtumi.dependent_filtered_entities');
        $options['class'] = $entities[$options['entity_alias']]['class'];
        $options['property'] = $entities[$options['entity_alias']]['property'];

        $options['no_result_msg'] = $entities[$options['entity_alias']]['no_result_msg'];

        $builder->addViewTransformer(new EntityToSelect2ValueTransformer(
            $this->container->get('doctrine')->getManager(),
            $options['class']
        ), true);

        $builder->setAttribute("parent_field", $options['parent_field']);
        $builder->setAttribute("entity_alias", $options['entity_alias']);
        $builder->setAttribute("no_result_msg", $options['no_result_msg']);
        $builder->setAttribute("empty_value", $options['empty_value']);
        $builder->setAttribute("multiple", $options['multiple']);


        if ($options['multiple'] && interface_exists(Collection::class)) {
            $builder
                ->addEventSubscriber(new MergeDoctrineCollectionListener())
                //->addViewTransformer(new CollectionToArrayTransformer(), true)
            ;
        }

    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['parent_field'] = $form->getConfig()->getAttribute('parent_field');
        $view->vars['entity_alias'] = $form->getConfig()->getAttribute('entity_alias');
        $view->vars['no_result_msg'] = $form->getConfig()->getAttribute('no_result_msg');
        $view->vars['empty_value'] = $form->getConfig()->getAttribute('empty_value');
        $view->vars['multiple'] = $form->getConfig()->getAttribute('multiple', 0);
    }

}
