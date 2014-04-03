<?php
/**
 * Created by JetBrains PhpStorm.
 * User: zitarosa
 */

namespace Net7\KorboApiBundle\Controller;

use Doctrine\ORM\AbstractQuery;
use FOS\RestBundle\Controller\FOSRestController;

use Net7\KorboApiBundle\Entity\Basket;
use Net7\KorboApiBundle\Libs\FreebaseSearchDriver;
use Net7\KorboApiBundle\Utility\SearchPaginator;

use Net7\KorboApiBundle\Entity\Item;
use Net7\KorboApiBundle\Entity\ItemRepository;
use Net7\KorboApiBundle\Libs\ItemExternalImport;
use Net7\KorboApiBundle\Utility\ItemsPaginator;
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
class ItemsController extends KorboI18NController
{

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
        $this->response->setContent('{}');
        $this->response->headers->set('Content-Type', 'application/json');

        return $this->response;
    } // "options_items" [OPTIONS] /items/{id}


    /**
     * Lists all the items
     *
     * @param Request $request - web request
     *
     * @return Response
     *
     *
     *  @SWG\Api(
     *   path="/baskets/{basketId}/items",
     *   @SWG\Operations(
     *      @SWG\Operation(
     *          produces="['application/json', 'text/html']",
     *          method="GET",
     *          type="array",
     *          @SWG\Items("Item"),
     *          summary="Retrieves the items index",
     *          notes="Retrieves the list of all the items present in the store. All the item attributes are contained into the response",
     *          nickname="retrieveItems",
     *          @SWG\ResponseMessage(code=204, message="There is no representation for the requested item - only JSON is supported"),
     *           @SWG\Parameters(
     *              @SWG\Parameter(
     *                  name="basketId",
     *                  description="Basket id",
     *                  paramType="path",
     *                  required=true,
     *                  format="integer",
     *                  type="string"
     *              ),
     *              @SWG\Parameter(
     *                  name="limit",
     *                  description="Number of results per page",
     *                  paramType="query",
     *                  required="false",
     *                  format="string",
     *                  type="string"
     *              ),
     *             @SWG\Parameter(
     *                  name="offset",
     *                  description="Result offset",
     *                  paramType="query",
     *                  required="false",
     *                  format="string",
     *                  type="string"
     *              )
     *          )
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

        $offset = $request->get('offset', 0);
        // if no limit is passed set default page size
        $limit  = $request->get('limit', $this->container->getParameter('korbo_api_default_page_size'));
        $baseApiPath = 'http://' . $request->getHttpHost() . $request->getPathInfo();

        $em = $this->getDoctrine()->getManager();

        $items = $em->createQuery(
            'SELECT i
             FROM Net7KorboApiBundle:Item i')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getResult();

        $serializer  = $this->container->get('serializer');

        $jsonItemArray = array();
        foreach ($items as $item){
            $item->setTranslatableLocale($this->acceptLanguage);
            $em->refresh($item);

            $jsonItemArray[] =  $serializer->serialize($item, 'json');
        }

        $paginator = new ItemsPaginator($em->createQuery(ItemRepository::getItemsCountQueryString()), $baseApiPath, $limit, $offset);
        $metadata = $paginator->getPaginationMetadata();

        $jsonContent = '{"data":[' . implode(',', $jsonItemArray) . '], "metadata":' . json_encode($metadata, JSON_UNESCAPED_SLASHES) . '}';

        $this->response->setContent($jsonContent);
        $this->response->headers->set('Content-Type', 'application/json');

        return $this->response;
    } // "get_letters"     [GET] /baskets/{basket-id}/items

    /**
     * Empty action
     *
     * @param String $content
     */
    public function newAction($content)
    {
    } // "new_items"     [GET] /items/new



     /**
     * @param int     $id      - Item Id
     * @param Request $request - web request
     *
     * @return Response
     * @throws NotFoundHttpException
     *
     *  @SWG\Api(
     *   path="/baskets/{basketId}/items/{id}",
     *   @SWG\Operations(
     *      @SWG\Operation(
     *          produces="['application/json', 'text/html']",
     *          method="GET",
     *          type="Item",
     *          summary="Find an item by ID",
     *          notes="Gets the JSON representation of the item. Only application/json Accept value is supported.
     *                 The header 'Accept-Language' specifies the preferred language to retrieve the item.
     *                 Any ISO 639-1 value is supported.",
     *          nickname="getItemById",
     *          @SWG\Parameters(
     *              @SWG\Parameter(
     *                  name="id",
     *                  description="ID of the item that needs to be fetched",
     *                  paramType="path",
     *                  required="true",
     *                  format="int64",
     *                  type="integer"
     *              ),
     *              @SWG\Parameter(
     *                  name="Accept-Language",
     *                  description="Language representation of the $item, ISO 639-1 codes (en, it, de, ..)",
     *                  paramType="header",
     *                  required="false",
     *                  type="string",
     *                  enum="['en', 'it', 'de']"
     *              ),
      *             @SWG\Parameter(
      *                  name="Provider",
      *                  description="The provider to send the request to. By default the provider is korbo.",
      *                  paramType="header",
      *                  required="false",
      *                  type="string",
      *                  enum="['korbo', 'freebase']"
      *              )
     *          ),
     *          @SWG\ResponseMessage(code=405, message="Method not allowed"),
     *          @SWG\ResponseMessage(code=404, message="Item not found"),
     *          @SWG\ResponseMessage(code=204, message="There is no representation for the requested item - only JSON is supported...at the moment")
     *     )
     *   )
     *  )
     */
    public function getAction($id, Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $provider = $request->get("p", 'korbo');

        if ($provider == 'freebase') {
            // TODO: this portion needs a full refactoring
            $freebaseDriver = new FreebaseSearchDriver(
                 $this->container->getParameter('freebase_search_base_url'),
                 $this->container->getParameter('freebase_api_key'),
                 $this->container->getParameter('freebase_topic_base_url'),
                 $this->container->getParameter('freebase_base_mql_url'),
                 $this->container->getParameter('freebase_image_search'),
                 $this->container->getParameter('freebase_languages_to_retrieve'),
                 ''
            );
            $freebaseDriver->setDefaultLanguage($this->acceptLanguage, $this->container->getParameter('korbo_default_locale'));

            $id = str_replace("__", "/", $id);

            $jsonContent = json_encode($freebaseDriver->getEntityDetails($id), JSON_UNESCAPED_SLASHES);
            $this->response->setContent($jsonContent);
            $this->response->headers->set('Content-Type', 'application/json');

            return $this->response;
        }


        $item = $this->getDoctrine()
            ->getRepository('Net7KorboApiBundle:Item')
            ->find($id);

        // 404 not found
        if (!$item instanceof Item) {
            throw new NotFoundHttpException('Item not found');
        }

        // if the language requested is not available the default language will be returned
        if (!$item->hasLanguageAvailable($this->acceptLanguage)) {
            $this->acceptLanguage = $this->container->getParameter('korbo_default_locale');
        }

        // TODO: vedi https://github.com/FriendsOfSymfony/FOSRestBundle/blob/master/Resources/doc/3-listener-support.md
        // per configurazione content neg

        // loading the  in the corrent locale
        $item->setTranslatableLocale($this->acceptLanguage);
        $em->refresh($item);

        $item->setLanguageCode($this->acceptLanguage);

        if ($this->accept->has('application/json')) {
            $serializer  = $this->container->get('serializer');
            $jsonContent = $serializer->serialize($item, 'json');

            $this->response->setContent($jsonContent);
            $this->response->headers->set('Content-Type', 'application/json');
        } else {
            // no content: there is no representation for the requested resource
            $this->response->setStatusCode(204);
        }
        return $this->response;
    } // "get_item"      [GET] /baskets/{basket-id}/items/{id}



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
     *                  name="resourceUrl",
     *                  description="Resource you want to import",
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
        if ( ($request->get("basketId", false) === false || !is_numeric($request->get("basketId", false))) &&
            $request->get("id", false) === false
        ) {
            $this->response->setStatusCode(400);

            return $this->response;
        }

        $resourceToImport            = $request->get("resourceUrl", false);
        $importResourceSynchronously = $request->get("sync", true);

        $em = $this->getDoctrine()->getManager();

        $basket = $em->find("Net7KorboApiBundle:Basket", $request->get('basketId'));

        /** if the id parameter is present we modify an existing item...otherwise we will create a new one @var Item */
        $item = ( ($id = $request->get("id", false) ) === false) ? new Item() : $em->find("Net7KorboApiBundle:Item", $id);

        $item->setBasket($basket);
        //no resource to import passed as parameter
        if ($resourceToImport === false) {
            $this->checkAndSetTranslation($item, 'label', $request);
            $this->checkAndSetTranslation($item, 'abstract', $request);

            // at the beginning the depiction is an url
            $this->checkAndSetField('depiction', $request, $item);
            $this->checkAndSetField('type', $request, $item, array());
        } else {
            // new resource to import
            $itemImporter = new ItemExternalImport($resourceToImport, $item, $importResourceSynchronously, $this->container);

            try{
                $itemImporter->importResource();
            } catch (\Exception $e) {
                // TODO improve error message
                $this->response->setStatusCode(400);
                $this->response->setContent(json_encode(array("error" => $e->getMessage())));

                return $this->response;
            }
            $item->setResource($resourceToImport);
        }

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
     * Search API
     *
     * @param Request $request
     *
     * @return Response
     *
     *  @SWG\Api(
     *   path="/search/items",
     *   @SWG\Operations(
     *      @SWG\Operation(
     *          produces="['application/json']",
     *          method="GET",
     *          type="array",
     *          @SWG\Items("Item"),
     *          summary="Search",
     *          notes="Retrieves the list of all the Item present in the store matching the search criteria. All the item attributes are contained into the response",
     *          nickname="retrieveItems",
     *          @SWG\ResponseMessage(code=204, message="There is no representation for the requested search parameters - only JSON is supported"),
     *          @SWG\Parameters(
     *              @SWG\Parameter(
     *                  name="q",
     *                  description="Text pattern to search",
     *                  paramType="query",
     *                  required="false",
     *                  format="string",
     *                  type="string"
     *              ),
     *             @SWG\Parameter(
     *                  name="limit",
     *                  description="Number of results per page",
     *                  paramType="query",
     *                  required="false",
     *                  format="string",
     *                  type="string"
     *              ),
     *             @SWG\Parameter(
     *                  name="offset",
     *                  description="Result offset",
     *                  paramType="query",
     *                  required="false",
     *                  format="string",
     *                  type="string"
     *              ),
     *            @SWG\Parameter(
     *                  name="lang",
     *                  description="Language",
     *                  paramType="query",
     *                  required="false",
     *                  format="string",
     *                  type="string",
     *                  enum="['en', 'it', 'de']"
     *              )

     *
     *          )
     *     )
     *   )
     *  )
     *
     */
    public function searchItemsAction(Request $request)
    {
        // TODO: only json accepted at the moment
        if (!$this->accept->has('application/json')) {
            $this->response->setStatusCode(204);

            return $this->response;
        }
        $em = $this->getDoctrine()->getManager();

        $offset = $request->get('offset', 0);
        // if no limit is passed set default page size
        $limit  = $request->get('limit', $this->container->getParameter('korbo_api_default_page_size'));

        $queryString = $request->get('q', false);
        $locale = $request->get("lang", $this->container->getParameter('korbo_default_locale'));

        // Search driver
        $searchDriver = $request->get("p", 'korbo');

        $baseApiPath = 'http://' . $request->getHttpHost() . $request->getPathInfo();

        if ($searchDriver === 'freebase') {
            $driver = new FreebaseSearchDriver(
                $this->container->getParameter('freebase_search_base_url'),
                $this->container->getParameter('freebase_api_key'),
                $this->container->getParameter('freebase_topic_base_url'),
                $this->container->getParameter('freebase_base_mql_url'),
                $this->container->getParameter('freebase_image_search'),
                $this->container->getParameter('freebase_languages_to_retrieve'),
                $baseApiPath,
                array("limit" => $limit, 'offset' => $offset)
            );
            $driver->setDefaultLanguage($locale);
            $jsonItemsArray = $driver->search($queryString);
            $metadata = $driver->getPaginationMetadata($baseApiPath);
            $jsonContent = '{"data":' . json_encode($jsonItemsArray, JSON_UNESCAPED_SLASHES) . ', "metadata":' . json_encode($metadata, JSON_UNESCAPED_SLASHES) . '}';
        } else {
            $items = $em->getRepository('Net7KorboApiBundle:Item')->findByLocaleAndQueryString($locale, $queryString, $limit, $offset);

            $serializer  = $this->container->get('serializer');

            $jsonItemsArray = array();
            foreach ($items as $item){
                $item->setTranslatableLocale($this->acceptLanguage);
                $em->refresh($item);

                $jsonItemsArray[] =  $serializer->serialize($item, 'json');
            }


            $paginator = new SearchPaginator($em, $baseApiPath, $locale, $queryString, $limit, $offset);

            $metadata = $paginator->getPaginationMetadata();
            $jsonContent = '{"data":[' . implode(',', $jsonItemsArray) . '], "metadata":' . json_encode($metadata, JSON_UNESCAPED_SLASHES) . '}';
        }

        $this->response->setContent($jsonContent);
        $this->response->headers->set('Content-Type', 'application/json');

        return $this->response;
    }



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