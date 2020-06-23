<?php

namespace Shtumi\UsefulBundle\Form\DataTransformer;

use Doctrine\Common\Collections\Collection;
use Symfony\Component\Form\DataTransformerInterface;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Exception\ErrorMappingException;

class EntityToSelect2ValueTransformer implements DataTransformerInterface
{

    protected $em;
    protected $class;
    protected $unitOfWork;

    public function __construct(EntityManager $em, $class)
    {
        $this->em = $em;
        $this->unitOfWork = $this->em->getUnitOfWork();
        $this->class = $class;
    }

    public function transform($entity)
    {
        if($entity instanceof Collection){
            if ($entity->count() == 0){
                return json_encode([]);
            }else{
                $items = [];
                foreach ($entity as $item) {
                    $items[] = [
                        'id' => $item->getId(),
                        'text' => (string)$item
                        ];
                }

                return json_encode($items);
            }
        }

        if (null === $entity || '' === $entity) {
            return 'null';
        }
        if (!is_object($entity)) {
            throw new UnexpectedTypeException($entity, 'object');
        }
        if (!$this->unitOfWork->isInIdentityMap($entity)) {
            throw new ErrorMappingException('Entities passed to the choice field must be managed');
        }

        return json_encode(array(
            'id' => $entity->getId(),
            'text' => (string)$entity
        ));
    }

    public function reverseTransform($id)
    {
        if ('' === $id || null === $id) {
            return null;
        }

        if (!is_numeric($id)) {
            throw new UnexpectedTypeException($id, 'numeric' . $id);
        }

        $entity = $this->em->getRepository($this->class)->findOneById($id);

        if ($entity === null) {
            throw new TransformationFailedException(sprintf('The entity with key "%s" could not be found', $id));
        }

        return $entity;
    }
}
