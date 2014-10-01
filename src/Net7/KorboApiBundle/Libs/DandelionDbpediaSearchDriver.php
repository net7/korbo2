<?php
/**
 * Created by JetBrains PhpStorm.
 * User: zitarosa
 */

namespace Net7\KorboApiBundle\Libs;


use Net7\KorboApiBundle\Utility\DandelionDbpediaPaginator;
use Net7\KorboApiBundle\Utility\EuropeanaPaginator;

class DandelionDbpediaSearchDriver extends AbstractSearchDriver {

    private $dandelionAppKey;

    private $dandelionBaseUrl;

    private $extraParameters;

    private $baseApiPath;

    private $defaultLanguage;

    private $configurationLanguage;

    public function __construct($dandelionBaseUrl, $dandelionAppKey, $dandelionAppId, $baseApiPath, $extraParameters = array())
    {
        $this->dandelionAppKey      = $dandelionAppKey;
        $this->dandelionAppId      = $dandelionAppId;
        $this->dandelionBaseUrl     = $dandelionBaseUrl;
        $this->baseApiPath          = $baseApiPath;

        if (empty($extraParameters)){
            $extraParameters = array(
                "content-type" => "application/json"
            );
        }

        $this->extraParameters = $extraParameters;

    }


    /**
     * Sets the default language
     *
     * @param $lang
     * @param string $configurationLanguage
     */
    public function setDefaultLanguage($lang, $configurationLanguage = '')
    {
        $this->defaultLanguage = $lang;
        $this->configurationLanguage = $configurationLanguage;
    }



    /**
     * Search over dandelion
     *
     * @param string $wordToSearch
     *
     * @return json results
     */
    public function search($wordToSearch)
    {

        // TODO add start / limit parameter to search url
        $results = array();

        $res = $this->doDandelionRequest($wordToSearch);
        $arrayResult = json_decode($res, true);

        // TODO: uniform data returned

        foreach ($arrayResult['entities'] as $result) {

            $results[] = array(
                'available_languages' => array("mul"),  // available languages for dandelion - de | en | fr | it | pt -
                'id'                  => $result['id'],
                'uri'                 => $result['uri'],
                'basket_id'           => null,
                'depiction'           => (array_key_exists("thumbnail", $result['image'])) ? $result['image']['thumbnail'] : '',
                'abstract'            => $result['abstract'],
                'resource'            => $result['uri'],
                'label'               => $result['title'],
                'type'                => (!empty($result["types"])) ? $result["types"] : array("http://www.w3.org/2002/07/owl#Thing")
            );
        }

        $this->dandelionSearchHits = array(
            "totalCount" => $arrayResult['count'],
            "itemsCount" => (isset($params['limit'])) ? $params['limit'] : 10
        );

        return $results;
    }

    /**
     * Retrieves all the metadata available from europeana in all the languages
     *
     * @param string $europeanaEntityUrl
     *
     * @return ItemResponseContainer
     *
     * @throws \Exception
     */
    public function getEntityMetadata($dandelionDbpediaEntityUrl)
    {
        //TODO: is not possible to integrate it with korboEE

    }

    public function getPaginationMetadata()
    {
        $offset = (isset($params['offset'])) ? $params['offset'] : 0;
        $limit  = (isset($params['limit'])) ? $params['limit'] : 10;
        $totalPages = ($limit > 0) ? ceil( $this->dandelionSearchHits['totalCount'] / $limit ) : 0;

        $dandelionPagination = new DandelionDbpediaPaginator($this->baseApiPath, $this->defaultLanguage, $limit, $offset);

        $paginationMetadata = array(
            "pageCount"  => $totalPages,
            "totalCount" => $this->dandelionSearchHits['totalCount'],
            "offset"     => $offset,
            "limit"      => $limit,
            "links"      => $dandelionPagination->getLinksMetadata($totalPages, $this->dandelionSearchHits['totalCount']),
            "allLanguagesCount" => $this->dandelionSearchHits['totalCount']  // is not possible to have all language count...the api does not support this feature
        );

        return $paginationMetadata;
    }


    public function getEntityDetails($europeanaEntityId)
    {

    }

    protected function doDandelionRequest($word) {
        $params = $this->extraParameters;

        $word = urlencode(str_replace('"', '', trim($word)));
        $requestUrl = $this->dandelionBaseUrl . $word .
            '&$app_id=' . $this->dandelionAppId .
            '&$app_key=' . $this->dandelionAppKey .
            '&lang=' . $this->defaultLanguage .
            '&include=image,abstract,types'                                           // needed to expand the search
        ;


        $contentType = (isset($params['content-type'])) ? $params['content-type'] : 'application/json';

        //limit
        $requestUrl .= (isset($params['limit'])) ? "&limit=" . $params['limit'] : '';
        $requestUrl .= (isset($params['offset'])) ? "&offset=" . $params['offset'] : '';

        $request = curl_init();
        curl_setopt($request, CURLOPT_URL, $requestUrl);
        curl_setopt($request, CURLOPT_HTTPHEADER, array("Content-Type: {$contentType}"));
        curl_setopt($request, CURLOPT_CONNECTTIMEOUT, 20);
        curl_setopt($request, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($request, CURLOPT_FOLLOWLOCATION, 1);

        $response = curl_exec($request);
        $error = curl_error($request);
        $http_code = curl_getinfo($request, CURLINFO_HTTP_CODE);


        if (!curl_errno($request)) {
          $result = $response;
        } else {
          $result = $error;
        }

        curl_close($request);

        return $result;
    }

}