<?php

namespace Net7\KorboApiBundle\Controller;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\Response,
    Symfony\Component\HttpFoundation\AcceptHeader;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
 * Utils controller
 *
 *
 */
class UtilsController extends Controller
{


    /**
     * Redirect swagger
     *
     * @param Request $request
     *
     * @return RedirectResponse
     */
    public function swaggerAction(Request $request)
    {
        $filename = $request->get('filename', false);

        if ($filename === false) {
            return new RedirectResponse('http://' . $request->getHost() . '/swagger/ui/index.html');
        } else {
            return new RedirectResponse('http://' . $request->getHost() . "/swagger/{$filename}");
        }

    }

    /**
     * Redirect apidocs
     *
     * @param Request $request
     *
     * @return Response
     */
    public function apidocsAction(Request $request)
    {
        $filename = $request->get('filename', "api-docs.json");
        $response = new Response(file_get_contents($this->container->get('kernel')->getRootDir() . '/../swagger/' . $filename));
        $response->headers->set('Access-Control-Allow-Origin', "*");

        return $response;
    }



}
