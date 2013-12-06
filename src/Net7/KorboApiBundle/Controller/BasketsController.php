<?php
/**
 * Created by JetBrains PhpStorm.
 * User: zitarosa
 */

namespace Net7\KorboApiBundle\Controller;

use Doctrine\ORM\AbstractQuery;
use FOS\RestBundle\Controller\FOSRestController;
use Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\Response,
    Symfony\Component\HttpFoundation\AcceptHeader,
    Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Swagger\Annotations as SWG;
use Symfony\Component\Security\Acl\Exception\Exception;

/**
 * Baskets Api Controller
 *
 * @RouteResource("Basket")
 *
 * @SWG\Model(id="Basket")
 *
 * @SWG\Resource(
 *    resourcePath="/baskets",
 *    swaggerVersion="1.2",
 *    apiVersion="0.0.1",
 *    description="Basket entities",
 *    basePath="http://korbo2.local"
 * )
 *
 */
class BasketsController extends KorboController{

    /**
     * The method is called when OPTIONS Header is set.
     *
     * Returns the accepted HTTP Methods related to /baskets API.
     * Json API return documentation body...only GET and POST implemented
     *
     * @return Response
     *
     */
    public function coptionsAction()
    {
    } // "options_baskets" [OPTIONS] /baskets

    /**
     * The method is called when OPTIONS Header is set.
     *
     * Returns the accepted HTTP Methods related to /baskets/{id} API.
     * Json API return documentation body...only GET is implemented
     *
     * @return Response
     *
     */
    public function optionsAction()
    {
    } // "options_baskets" [OPTIONS] /baskets/{id}

    /**
     * Lists all the baskets
     *
     * @param Request $request - web request
     *
     * @return Response
     *
     *
     *  @SWG\Api(
     *   path="/baskets",
     *   @SWG\Operations(
     *      @SWG\Operation(
     *          produces="['application/json', 'text/html']",
     *          method="GET",
     *          type="array",
     *          @SWG\Items("Basket"),
     *          summary="Retrieves the baskets index",
     *          notes="Retrieves the list of all the baskets present in the store. All the baskets attributes are contained into the response",
     *          nickname="retrieveBaskets",
     *          @SWG\ResponseMessage(code=204, message="There is no representation for the requested basket - only JSON is supported"),
     *     )
     *   )
     *  )
     *
     */
    public function cgetAction(Request $request)
    {
        // TODO: only json accepted at the moment
        // no content: there is no representation for the requested resource
        if (!$this->accept->has('application/json')) {
            $this->response->setStatusCode(204);

            return $this->response;
        }

        $baseApiPath = 'http://' . $request->getHttpHost() . $request->getPathInfo();

        $em = $this->getDoctrine()->getManager();

        $baskets = $em->createQuery(
            'SELECT l
             FROM Net7KorboApiBundle:Basket b')
            ->getResult();

        $serializer  = $this->container->get('serializer');

        $jsonBasketArray = array();
        foreach ($baskets as $basket){
            $jsonBasketArray[] =  $serializer->serialize($basket, 'json');
        }

        $metadata = array();

        //$metadata = $this->processPagination($offset, $limit, $baseApiPath);

        $jsonContent = '{"data":[' . implode(',', $jsonBasketArray) . '], "metadata":' . json_encode($metadata, JSON_UNESCAPED_SLASHES) . '}';

        $this->response->setContent($jsonContent);
        $this->response->headers->set('Content-Type', 'application/json');

        return $this->response;
    } // "get_baskets"     [GET] /baskets



    /**
     * Empty action
     *
     * @param String $content
     */
    public function newAction($content)
    {
    } // "new_baskets"     [GET] /baskets/new



    public function getAction($id, Request $request)
    {
        return $this->response;
    } // "get_basket"      [GET] /baskets/{slug}



    public function postAction(Request $request)
    {
        return $this->response;
    } // "post_baskets"    [POST] /baskets



    /**
     * Empty function
     */
    public function patchAction()
    {
    } // "patch_baskets"   [PATCH] /baskets

    /**
     * Empty function
     *
     * @param String $slug
     */
    public function editAction($slug)
    {
    } // "edit_basket"     [GET] /baskets/{slug}/edit

    /** Empty function
     *
     * @param string $slug
     */
    public function putAction($slug)
    {
    } // "put_basket"      [PUT] /baskets/{slug}

    /** Empty function
     *
     * @param string $slug
     */
    public function cpatchAction($slug)
    {
    } // "patch_basket"    [PATCH] /baskets/{slug}

    /** Empty function
     *
     * @param string $slug
     */
    public function lockAction($slug)
    {
    } // "lock_basket"     [PATCH] /baskets/{slug}/lock

    /** Empty function
     *
     * @param string $slug
     */
    public function banAction($slug)
    {
    } // "ban_basket"      [PATCH] /baskets/{slug}/ban

    /** Empty function
     *
     * @param string $slug
     */
    public function removeAction($slug)
    {
    } // "remove_basket"   [GET] /baskets/{slug}/remove

    /** Empty function
     *
     * @param string $slug
     */
    public function deleteAction($slug)
    {
    } // "delete_basket"   [DELETE] /baskets/{slug}



}