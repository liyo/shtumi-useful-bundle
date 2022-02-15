<?php

/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shtumi\UsefulBundle\Filter;

use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Sonata\AdminBundle\Filter\Model\FilterData;
use Sonata\AdminBundle\Form\Type\BooleanType;
use Sonata\DoctrineORMAdminBundle\Filter\Filter;
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQueryInterface;

class AjaxAutocompleteFilter extends Filter
{

    private $container;

    public function __construct($container)
    {
        $this->container = $container;
    }

    /**
     * @param ProxyQueryInterface $query
     * @param string $alias
     * @param string $field
     * @param FilterData $data
     */
    public function filter(ProxyQueryInterface $query, $alias, $field, FilterData $data): void
    {
        if (!$data->hasValue()) {
            return;
        }

        $entities = $this->container->getParameter('shtumi.autocomplete_entities');
        $field = $entities[$this->getOption('entity_alias')]['property'];

        $this->handleScalar($query, $alias, $field, $data);
    }

    /**
     * @param ProxyQueryInterface $query
     * @param $alias
     * @param $field
     * @param FilterData $data
     */
    protected function handleScalar(ProxyQueryInterface $query, $alias, $field, FilterData $data): void
    {

        $callback = $this->getOption('callback');
        if ($callback){

            call_user_func($callback, $query, $alias, $field, $data);

        } else {
            if (isset($data['type']) && $data['type'] == BooleanType::TYPE_NO) {
                $this->applyWhere($query, sprintf('%s.%s != :%s', $alias, 'id', $this->getName()));
            } else {
                $this->applyWhere($query, sprintf('%s.%s = :%s', $alias, 'id', $this->getName()));
            }

            $query->setParameter($this->getName(), $data['value']->getId());
        }


    }

    protected function association(ProxyQueryInterface $query, FilterData $data): array
    {

        $types = array(
            ClassMetadataInfo::ONE_TO_ONE,
            ClassMetadataInfo::ONE_TO_MANY,
            ClassMetadataInfo::MANY_TO_MANY,
            ClassMetadataInfo::MANY_TO_ONE,
        );

        if (!in_array($this->getOption('mapping_type'), $types)) {
            throw new \RunTimeException('Invalid mapping type');
        }

        if (!$this->getOption('field_name')) {
            throw new \RunTimeException('please provide a field_name options');
        }

        if (!$this->getOption('callback')){

            $alias = 's_'.$this->getName();
            $query->leftJoin(sprintf('%s.%s', $query->getRootAlias(), $this->getFieldName()), $alias);
            return array($alias, 'id');

        } else {

            return array($this->getOption('alias', $query->getRootAlias()), false);

        };

    }

    public function getDefaultOptions(): array
    {
        return array(
            'mapping_type' => ClassMetadataInfo::MANY_TO_ONE,
            'field_name'   => false,
            'field_type'   => 'shtumi_ajax_autocomplete',
            'field_options' => array(),
            'operator_type' => 'sonata_type_boolean',
            'operator_options' => array(),
            'callback'      => null,
        );
    }

    public function getRenderSettings(): array
    {
        $options = array_merge($this->getFieldOptions(), array('entity_alias' => $this->getOption('entity_alias')));

        return array('sonata_type_filter_default', array(
            'field_type'    => $this->getFieldType(),
            'field_options' => $options,
            'operator_type' => $this->getOption('operator_type'),
            'operator_options' => $this->getOption('operator_options'),
            'label'         => $this->getLabel()
        ));
    }
}
