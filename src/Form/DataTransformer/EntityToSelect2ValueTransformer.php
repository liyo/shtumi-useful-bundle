<?php

namespace Shtumi\UsefulBundle\Form\DataTransformer;

use Doctrine\Common\Collections\ArrayCollection;
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
	protected $multiple;
	protected $property;
	protected $extraProperty;


    public function __construct(EntityManager $em, $class, $multiple = false, $property = null, $extraProperty = null)
    {
        $this->em = $em;
        $this->unitOfWork = $this->em->getUnitOfWork();
        $this->class = $class;
		$this->multiple = $multiple;
		$this->property = $property;
		$this->extraProperty = $extraProperty;
    }

    public function transform($entity)
    {
		$getter =  $this->property ? $this->getGetterName($this->property) : '';
		$extraGetter =  $this->extraProperty ? $this->getGetterName($this->extraProperty) : '';

        if($this->multiple){
            if(is_array($entity)){
                $entity = new ArrayCollection($entity);
            }

            if($entity instanceof Collection){
                if ($entity->count() == 0){
                    return json_encode([]);
                }else{
                    $items = [];
                    foreach ($entity as $item) {

						if ($this->property)
							$text = $item->$getter();
						else $text = (string)$entity;

						if ($this->extraProperty)
							$extra = $item->$extraGetter();
						else $extra = '';

                        $items[] = [
                            'id' => $item->getId(),
							'text' => $text,
							'extra_property' => $extra
                        ];
                    }

                    return json_encode($items);
                }
            }else{
                return json_encode([]);
            }
        }else {


            if (null === $entity || '' === $entity || (is_array($entity) && count($entity) == 0)) {
                return json_encode(array(
                    'id' => null,
                    'text' => '== Choose value =='
                ));
            }
            if (!is_object($entity)) {
                throw new UnexpectedTypeException($entity, 'object');
            }
            if (!$this->unitOfWork->isInIdentityMap($entity)) {
                throw new ErrorMappingException('Entities passed to the choice field must be managed');
            }

			if ($this->property)
				$text = $entity->$getter();
			else $text = (string)$entity;

			if ($this->extraProperty)
				$extra = $entity->$extraGetter();
			else $extra = '';

            return json_encode(array(
                'id' => $entity->getId(),
                'text' => $text,
				'extra_property' => $extra
            ));
        }
    }

    public function reverseTransform($id)
    {
        if ('' === $id || null === $id) {
            if($this->multiple) {
                return [];
            }else{
                return null;
            }
        }

        if($this->multiple){
            $ids = explode(',', $id);

            $entities = $this->em->getRepository($this->class)->findBy(['id' => $ids]);

            if(!count($entities)){
                throw new TransformationFailedException(sprintf('The entity with key "%s" could not be found', $id));
            }

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

	private function getGetterName($property)
	{
		$name = "get";
		$name .= mb_strtoupper($property[0]) . substr($property, 1);

		while (($pos = strpos($name, '_')) !== false){
			$name = substr($name, 0, $pos) . mb_strtoupper(substr($name, $pos+1, 1)) . substr($name, $pos+2);
		}

		return $name;

	}
}
