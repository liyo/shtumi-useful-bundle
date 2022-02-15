<?php

namespace Shtumi\UsefulBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\JsonResponse;

class AjaxAutocompleteJSONController extends Controller
{

    public function getJSONAction(Request $request)
    {

        $em = $this->get('doctrine')->getManager();

        $entities = $this->get('service_container')->getParameter('shtumi.autocomplete_entities');

        $entity_alias = $request->get('entity_alias');
        $entity_inf = $entities[$entity_alias];

        if ($entity_inf['role'] !== 'IS_AUTHENTICATED_ANONYMOUSLY'){
            $this->denyAccessUnlessGranted($entity_inf['role']);
        }

        $letters = $request->get('letters');
        $maxRows = $request->get('maxRows');

        switch ($entity_inf['search']){
            case "begins_with":
                $like = $letters . '%';
            break;
            case "ends_with":
                $like = '%' . $letters;
            break;
            case "contains":
                $like = '%' . $letters . '%';
            break;
            default:
                throw new \Exception('Unexpected value of parameter "search"');
        }

	    $property = $entity_inf['property'];

        $qb = $this->getDoctrine()
            ->getRepository($entity_inf['class'])
            ->createQueryBuilder('e')
            ->select('e.' . $property)
        ;


        if ($entity_inf['case_insensitive']) {
            $qb->where('e.' . $property .' LIKE LOWER(:like)');
        } else {
            $qb->where('e.' . $property .' LIKE :like');
        }

        $qb->setParameter('like', $like);


        if (null !== $entity_inf['callback']) {
            $repository = $qb->getEntityManager()->getRepository($entity_inf['class']);

            if (!method_exists($repository, $entity_inf['callback'])) {
                throw new \InvalidArgumentException(sprintf('Callback function "%s" in Repository "%s" does not exist.', $entity_inf['callback'], get_class($repository)));
            }

            call_user_func(array($repository, $entity_inf['callback']), $qb);
        }

        $results = $qb->getQuery()
            ->setMaxResults($maxRows)
            ->getScalarResult();
        ;

        //var_dump($results);die;

        $res = array();
        foreach ($results AS $r){
            $res[] = $r[$property];
        }

        return new JsonResponse($res);

    }
}
