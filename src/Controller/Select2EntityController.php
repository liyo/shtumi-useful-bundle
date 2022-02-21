<?php

namespace Shtumi\UsefulBundle\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Translation\TranslatorInterface;

class Select2EntityController extends AbstractController
{


    /**
     * @var ManagerRegistry $em
     */
    private $em;

    /**
     * @var TranslatorInterface $translator
     */
    private $translator;


    private $filteredEntities;


    public function __construct(ManagerRegistry $em, TranslatorInterface $translator, ParameterBagInterface $parameterBag){
        $this->em = $em;
        $this->translator = $translator;

        $this->filteredEntities = $parameterBag->get('shtumi.autocomplete_entities');
    }


    /**
     * @param Request $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function getJson(Request $request) :JsonResponse
    {
        $entity_alias = $request->get('entity_alias');
        $entity_inf = $this->filteredEntities[$entity_alias];

        if ($entity_inf['role'] !== 'IS_AUTHENTICATED_ANONYMOUSLY'){
            $this->denyAccessUnlessGranted($entity_inf['role']);
        }

        $letters = $request->get('term');
        $maxRows = $request->get('maxRows');
        $page = $request->get('page', 1);

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

        if ($entity_inf['case_insensitive']) {
                $where_clause_lhs = 'WHERE LOWER(e.' . $property . ')';
                $where_clause_rhs = 'LIKE LOWER(:like)';
        } else {

                $where_clause_lhs = 'WHERE e.' . $property;
                $where_clause_rhs = 'LIKE :like';
        }

        $results = $this->em->createNativeQuery(
            'SELECT e as entity, e.' . $property . ' as property
             FROM ' . $entity_inf['class'] . ' e ' .
             $where_clause_lhs . ' ' . $where_clause_rhs . ' ' .
            'ORDER BY e.' . $property)
            ->setParameter('like', $like )
            ->setMaxResults($maxRows)
            ->setFirstResult(($page - 1) * $maxRows)
            ->getResult();

        $res = array();
        foreach ($results AS $r){
            $res[] = array(
                'id' => $r['entity']->getId(),
                'text' => $r['property']
            );
        }

        return new JsonResponse($res);
    }
}
