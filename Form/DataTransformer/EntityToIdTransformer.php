<?php

namespace Shtumi\UsefulBundle\Form\DataTransformer;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Form\DataTransformerInterface;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Exception\ErrorMappingException;

class EntityToIdTransformer implements DataTransformerInterface
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
                return [];
            }else{
                $items = [];
                foreach ($entity as $item) {
                    $items[] = $item->getId();
                }

                return $items;
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

        return $entity->getId();
    }

    public function reverseTransform($id)
    {
        if ('' === $id || null === $id) {
            return null;
        }

        $ids = explode(',', $id);
        if(count($ids)>1){
            $entities = $this->em->getRepository($this->class)->findBy(['id' => $ids]);

            if(!$entities->count()){
                throw new TransformationFailedException(sprintf('The entity with key "%s" could not be found', $id));
            }

            var_dump($entities);die;
            return $entities;

        }else{
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
}
