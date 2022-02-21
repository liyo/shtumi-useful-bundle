<?php

namespace Shtumi\UsefulBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\JsonResponse;

class AjaxFileController extends AbstractController
{

    public function uploadAction(Request $request )
    {
        $filesBag = $request->files->all();

        $files = array();
        $filesResult = array();
        //foreach ($filesBag as $form){
            foreach ($filesBag as $file){
                $files []= $file;
                $filesResult []=  array(
                    'path' => $file->getPathname(),
                    'url'  => 'ddd'
                );
            }
        //}

        $filesResult ['length'] = count($files);

        return new JsonResponse(array(
            'result' => array(
                'files' => $filesResult
            )
        ));
    }
}
