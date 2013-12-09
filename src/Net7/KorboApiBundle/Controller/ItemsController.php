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
class ItemsController extends KorboI18NController {

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
     */
    public function cgetAction(Request $request)
    {
        die("asdf");
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
    {die("asdf111");
        return $this->response;
    } // "get_item"      [GET] /items/{slug}



    /**
     * @param Request $request
     *
     * @return Response
     *
     *  @SWG\Api(
     *   path="/baskets/{basketId}/items",
     *   description="Item entities",
     *   @SWG\Operations(@SWG\Operation(
     *          method="POST",
     *          summary="Creates a new Item",
     *          notes="Creates a new Item with the content passed as parameter.
     *                 Returns status code 201 and the resource url in the Location Header.
     *                 The header 'Content-Language' specifies the language of the item being sent.
     *                 If id is specified the resource identified by the id will be modified.
     *                 When a new Item is created only the content parameter is mandatory.
     *                 When an Item is modified only the id parameter is mandatory.
     *                 Any ISO 639-1 value is supported.",
     *          nickname="createItem",
     *          @SWG\Parameters(
     *              @SWG\Parameter(
     *                  name="basketId",
     *                  description="Basket id",
     *                  paramType="path",
     *                  required=true,
     *                  format="integer",
     *                  type="string"
     *              ),
     *              @SWG\Parameter(
     *                  name="id",
     *                  description="Id of the item",
     *                  paramType="query",
     *                  required=false,
     *                  format="integer",
     *                  type="string"
     *              ),
     *              @SWG\Parameter(
     *                  name="label",
     *                  description="Label of the item,",
     *                  paramType="query",
     *                  required=false,
     *                  format="string",
     *                  type="string"
     *              ),
     *              @SWG\Parameter(
     *                  name="Content-Language",
     *                  description="Language of the item, ISO 639-1 code (en, it, de, ..)",
     *                  paramType="header",
     *                  required=false,
     *                  type="string",
     *                  enum="['en', 'it', 'de']"
     *              ),
     *              @SWG\Parameter(
     *                  name="abstract",
     *                  description="Abstract of the item",
     *                  paramType="query",
     *                  required=false,
     *                  format="string",
     *                  type="string"
     *              ),
     *              @SWG\Parameter(
     *                  name="depiction",
     *                  description="Depiction of the item...should be an URL",
     *                  paramType="query",
     *                  required=false,
     *                  format="string",
     *                  type="string"
     *              ),
     *              @SWG\Parameter(
     *                  name="type",
     *                  description="Types of the Item",
     *                  paramType="query",
     *                  required=false,
     *                  format="string",
     *                  type="array"
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
        // empty request 400 - basket not specified and not edit mode
        if ($request->get("basketId", false) === false && $request->get("id", false) === false) {
            $this->response->setStatusCode(400);

            return $this->response;
        }

        $em = $this->getDoctrine()->getManager();

        $basket = $em->find("Net7KorboApiBundle:Basket", $request->get('basketId'));

        // if the id parameter is present we modify an existing item...otherwise we will create a new one
        $item = ( ($id = $request->get("id", false) ) === false) ? new Item() : $em->find("Net7KorboApiBundle:Item", $id);

        $item->setBasket($basket);
        $this->checkAndSetTranslation($item, 'label', $request);
        $this->checkAndSetTranslation($item, 'abstract', $request);

        // at the beginning the depiction is an url
        $this->checkAndSetField('depiction', $request, $item);
        $this->checkAndSetField('type', $request, $item, array());

        $em->persist($item);
        $em->flush();

        // new item persisted
        if ($id === false) {
            $this->response->setStatusCode(201);

            $this->response->headers->set('Location',
                $this->generateUrl(
                    'get_item', array('id' => $item->getId()),
                    true // absolute
                )
            );
        } else {
            // modified item
            $this->response->setStatusCode(204);
        }
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