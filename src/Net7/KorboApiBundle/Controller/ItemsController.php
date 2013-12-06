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
 * @RouteResource("Item")
 *
 * @SWG\Model(id="Item")
 *
 * @SWG\Resource(
 *    resourcePath="/items",
 *    swaggerVersion="1.2",
 *    apiVersion="0.0.1",
 *    description="Basket entities",
 *    basePath="http://korbo2.local"
 * )
 *
 */
class ItemsController extends KorboController{

    /**
     * The method is called when OPTIONS Header is set.
     *
     * Returns the accepted HTTP Methods related to items API.
     * Json API return documentation body...only GET and POST implemented
     *
     * @return Response
     *
     */
    public function coptionsAction()
    {
    } // "options_items" [OPTIONS] /items

    /**
     * The method is called when OPTIONS Header is set.
     *
     * Returns the accepted HTTP Methods related to /items/{id} API.
     * Json API return documentation body...only GET is implemented
     *
     * @return Response
     *
     */
    public function optionsAction()
    {
    } // "options_items" [OPTIONS] /items/{id}

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
     *          @SWG\ResponseMessage(code=204, message="There is no representation for the requested item - only JSON is supported"),
     *     )
     *   )
     *  )
     *
     */
    public function cgetAction(Request $request)
    {
       return $this->response;
    } // "get_items"     [GET] /items



    /**
     * Empty action
     *
     * @param String $content
     */
    public function newAction($content)
    {
    } // "new_items"     [GET] /items/new



    public function getAction($id, Request $request)
    {
        return $this->response;
    } // "get_item"      [GET] /items/{slug}



    public function postAction(Request $request)
    {
        return $this->response;
    } // "post_items"    [POST] /items



    /**
     * Empty function
     */
    public function patchAction()
    {
    } // "patch_items"   [PATCH] /items

    /**
     * Empty function
     *
     * @param String $slug
     */
    public function editAction($slug)
    {
    } // "edit_item"     [GET] /items/{slug}/edit

    /** Empty function
     *
     * @param string $slug
     */
    public function putAction($slug)
    {
    } // "put_item"      [PUT] /items/{slug}

    /** Empty function
     *
     * @param string $slug
     */
    public function cpatchAction($slug)
    {
    } // "patch_item"    [PATCH] /items/{slug}

    /** Empty function
     *
     * @param string $slug
     */
    public function lockAction($slug)
    {
    } // "lock_item"     [PATCH] /items/{slug}/lock

    /** Empty function
     *
     * @param string $slug
     */
    public function banAction($slug)
    {
    } // "ban_item"      [PATCH] /items/{slug}/ban

    /** Empty function
     *
     * @param string $slug
     */
    public function removeAction($slug)
    {
    } // "remove_item"   [GET] /items/{slug}/remove

    /** Empty function
     *
     * @param string $slug
     */
    public function deleteAction($slug)
    {
    } // "delete_item"   [DELETE] /items/{slug}


}