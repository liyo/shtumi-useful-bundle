<?php

namespace Shtumi\UsefulBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Response;

class DependentFilteredEntityController extends Controller
{

    public function getOptionsAction(Request $request)
    {

        $em = $this->get('doctrine')->getManager();
        //$request = $this->getRequest();
        $translator = $this->get('translator');

        $entity_alias = $request->get('entity_alias');
        $parent_id    = $request->get('parent_id');
        $empty_value  = $request->get('empty_value');

        $entities = $this->get('service_container')->getParameter('shtumi.dependent_filtered_entities');
        $entity_inf = $entities[$entity_alias];

        if ($entity_inf['role'] !== 'IS_AUTHENTICATED_ANONYMOUSLY'){
            $this->denyAccessUnlessGranted($entity_inf['role']);
        }

        $qb = $this->getDoctrine()
            ->getRepository($entity_inf['class'])
            ->createQueryBuilder('e')
            ->where('e.' . $entity_inf['parent_property'] . ' = :parent_id')
            ->orderBy('e.' . $entity_inf['order_property'], $entity_inf['order_direction'])
            ->setParameter('parent_id', $parent_id);


        if (null !== $entity_inf['callback']) {
            $repository = $qb->getEntityManager()->getRepository($entity_inf['class']);

            if (!method_exists($repository, $entity_inf['callback'])) {
                throw new \InvalidArgumentException(sprintf('Callback function "%s" in Repository "%s" does not exist.', $entity_inf['callback'], get_class($repository)));
            }

            call_user_func(array($repository, $entity_inf['callback']), $qb);
        }

        $results = $qb->getQuery()->getResult();

        if (empty($results)) {
            return new Response('<option value="">' . $translator->trans($entity_inf['no_result_msg']) . '</option>');
        }

        $html = '';
        if ($empty_value !== false)
            $html .= '<option value="">' . $translator->trans($empty_value) . '</option>';

        $getter =  $this->getGetterName($entity_inf['property']);

        foreach($results as $result)
        {
            if ($entity_inf['property'])
                $res = $result->$getter();
            else $res = (string)$result;

            $html = $html . sprintf("<option value=\"%d\">%s</option>",$result->getId(), $res);
        }

        return new Response($html);

    }


    public function getJSONAction(Request $request)
    {
        /** @var \Doctrine\ORM\EntityManager $em */
        $em = $this->get('doctrine.orm.entity_manager');
        //$request = $this->get('request');

        $entity_alias = $request->get('entity_alias');
        $parent_id    = $request->get('parent_id');
        $empty_value  = $request->get('empty_value');

        $entities = $this->get('service_container')->getParameter('shtumi.dependent_filtered_entities');
        $entity_inf = $entities[$entity_alias];

        if ($entity_inf['role'] !== 'IS_AUTHENTICATED_ANONYMOUSLY'){
            $this->denyAccessUnlessGranted($entity_inf['role']);
        }

        $term = $request->get('term');
        $maxRows = $request->get('maxRows', 20);

        $like = '%' . $term . '%';

        $property = $entity_inf['property'];
        if (!$entity_inf['property_complicated']) {
            $property = 'e.' . $property;
        }

        $qb = $em->createQueryBuilder()
            ->select('e')
            ->from($entity_inf['class'], 'e')
            ->where('e.' . $entity_inf['parent_property'] . ' = :parent_id')
            ->setParameter('parent_id', $parent_id)
            ->orderBy('e.' . $entity_inf['order_property'], $entity_inf['order_direction'])
            ->setParameter('like', $like )
            ->setMaxResults($maxRows);

        if ($entity_inf['case_insensitive']) {
            $qb->andWhere('LOWER(' . $property . ') LIKE LOWER(:like)');
        } else {
            $qb->andWhere($property . ' LIKE :like');
        }

        $results = $qb->getQuery()->getResult();

		$getter =  $this->getGetterName($entity_inf['property']);
		$extraGetter =  $this->getGetterName($entity_inf['extra_property']);

        $res = array();
        foreach ($results AS $r) {
			if ($entity_inf['property'])
				$text = $r->$getter();
			else $text = (string)$r;

			if ($entity_inf['extra_property'])
				$extra = $r->$extraGetter();
			else $extra = '';

            $res[] = array(
                'id' => $r->getId(),
                'text' => $text,
				'extra_property' => $extra,
            );
        }

        return new Response(json_encode($res));
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
