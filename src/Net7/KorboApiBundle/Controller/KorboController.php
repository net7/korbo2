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
class KorboController extends Controller {

    /**
     * @var Response
     */
    protected $response;

    /**
     * Function always called before any controller function is called
     *
     * @param Request $request
     */
    public function initialize(Request $request)
    {
        $this->accept         = AcceptHeader::fromString($request->headers->get('Accept'));

        $this->acceptLanguage = ($request->headers->has('Accept-Language')) ?
            $this->sortAndGetHeaderLanguage((string) AcceptHeader::fromString($request->headers->get('Accept-Language'))) :
            $this->container->getParameter('korbo_default_locale');

        $this->contentLanguage = ($request->headers->has('Content-Language')) ?
            $this->sortAndGetHeaderLanguage((string) AcceptHeader::fromString($request->headers->get('Content-Language'))) :
            $this->container->getParameter('korbo_default_locale');

        $this->response = new Response();
        $this->response->headers->set('Access-Control-Allow-Origin', "*");
    }

    /**
     * Gets and sort language headers
     *
     * @param $acceptedLanguage
     *
     * @return mixed|string
     */
    private function sortAndGetHeaderLanguage($acceptedLanguage)
    {
        preg_match_all('/([a-z]{1,8}(-[a-z]{1,8})?)\s*(;\s*q\s*=\s*(1|0\.[0-9]+))?/i', $acceptedLanguage, $langParse);

        if (count($langParse[1])) {
            // create a list like "en" => 0.8
            $langs = array_combine($langParse[1], $langParse[4]);

            // set default to 1 for any without q factor
            foreach ($langs as $lang => $val) {
                if ($val === '') {
                    $langs[$lang] = 1;
                }
            }
            // sort list based on value
            arsort($langs, SORT_NUMERIC);
        }

        // only the first two chars are returned!
        if (count($langs) > 0) {
            reset($langs);

            return (substr(key($langs), 0, 2));
        }

        return $this->container->getParameter('openpal_default_locale');
    }
}