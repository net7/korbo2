<?php
/**
 * Created by JetBrains PhpStorm.
 * User: zitarosa
 */

namespace Net7\KorboApiBundle\Controller;

use Doctrine\ORM\AbstractQuery;
use FOS\RestBundle\Controller\FOSRestController;

use Net7\KorboApiBundle\Entity\Basket;

use Net7\KorboApiBundle\Entity\Item;
use Net7\KorboApiBundle\Libs\FreebaseSearchDriver;
use Net7\KorboApiBundle\Libs\ItemExternalImport;
use Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\Response,
    Symfony\Component\HttpFoundation\AcceptHeader,
    Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use FOS\RestBundle\Routing\ClassResourceInterface,
    FOS\RestBundle\Controller\Annotations\RouteResource,
    FOS\RestBundle\Request\ParamFetcherInterface,
    FOS\RestBundle\Controller\Annotations as Rest;

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
class BasketsController extends KorboController
{

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
     */
    public function cgetAction(Request $request)
    {
        // TODO: only json accepted at the moment
        // no content: there is no representation for the requested resource
        /*if (!$this->accept->has('application/json')) {
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
        */
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


    /**
     * Returns the representation of the basket
     *
     * @param integer $id
     * @param Request $request
     *
     * @return Response
     */
    public function getAction($id, Request $request)
    {
        return $this->response;
    } // "get_basket"      [GET] /baskets/{slug}


    /**
     * @param Request $request
     * 
     * @return Response
     *
     *  *  @SWG\Api(
     *   path="/baskets",
     *   description="Baskets entities",
     *   @SWG\Operations(@SWG\Operation(
     *          method="POST",
     *          summary="Creates a new Basket",
     *          notes="Creates a new Basket with the label passed as parameter.
     *                 Returns status code 201 and the resource url in the Location Header.
     *                 When a new Basket is created only the label parameter is mandatory.
     *                 When an Basket is modified both id and label parameter are mandatory.
     *                 Any ISO 639-1 value is supported.",
     *          nickname="createBasket",
     *          @SWG\Parameters(
     *              @SWG\Parameter(
     *                  name="id",
     *                  description="Id of the basket",
     *                  paramType="query",
     *                  format="integer",
     *                  type="string"
     *              ),
     *              @SWG\Parameter(
     *                  name="label",
     *                  description="Label of the basket,",
     *                  paramType="query",
     *                  format="string",
     *                  type="string"
     *              )
     *          ),
     *          @SWG\ResponseMessage(code=201, message="Resource created. New URL in the Location Header"),
     *          @SWG\ResponseMessage(code=204, message="Resource modified. Empty body"),
     *          @SWG\ResponseMessage(code=400, message="Empty request: no basket-id or id specified"),
     *          @SWG\ResponseMessage(code=405, message="Method not allowed")
     *     )
     *   )
     *  )
     */
    public function postAction(Request $request)
    {
        // empty request 400 - empty content and not edit mode
        if ($request->get("label", false) === false && $request->get("id", false) === false) {
            $this->response->setStatusCode(400);

            return $this->response;
        }

        $em = $this->getDoctrine()->getManager();

        // if the id parameter is present we modify an existing basket...otherwise we will create a new basket
        $basket = ( ($id = $request->get("id", false) ) === false) ? new Basket() : $em->find("Net7KorboApiBundle:Basket", $id);

        $this->checkAndSetField('label', $request, $basket);

        $em->persist($basket);
        $em->flush();

        // new basket persisted
        if ($id === false) {
            $this->response->setStatusCode(201);

            $this->response->headers->set('Location',
                $this->generateUrl(
                    'get_basket', array('id' => $basket->getId()),
                    true // absolute
                )
            );
        } else {
            // modified basket
            $this->response->setStatusCode(204);
        }

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