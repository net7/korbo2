<?php
/**
 * Created by JetBrains PhpStorm.
 * User: zitarosa
 */

namespace Net7\KorboApiBundle\Libs;


use Net7\KorboApiBundle\Utility\GeonamesPaginator;

class GeonamesSearchDriver extends AbstractSearchDriver {

    private $geonamesUsername;

    private $geonamesBaseUrl;

    private $extraParameters;

    private $baseApiPath;

    private $defaultLanguage;

    private $configurationLanguage;

    public function __construct($geonamesBaseUrl, $geonamesUsername, $baseApiPath, $extraParameters = array())
    {
        $this->geonamesUsername      = $geonamesUsername;
        $this->geonamesBaseUrl       = $geonamesBaseUrl;
        $this->baseApiPath           = $baseApiPath;

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

        $coords = explode("||", $wordToSearch);

        // TODO add start / limit parameter to search url
        $results = array();

        $res = $this->doGeonamesRequest($coords[0], $coords[1], $wordToSearch);

        $arrayResult = json_decode($res, true);

        // TODO: uniform data returned
//        print_r($arrayResult);die;
        foreach ($arrayResult['geonames'] as $result) {

            $results[] = array(
                'available_languages' => array("mul"),  // available languages for dandelion - de | en | fr | it | pt -
                'id'                  => $result['wikipediaUrl'],
                'uri'                 => $result['wikipediaUrl'],
                'basket_id'           => null,
                'depiction'           => (array_key_exists("thumbnail", $result)) ? $result['thumbnail'] : '',
                'abstract'            => $result['summary'],
                'resource'            => $result['wikipediaUrl'],
                'label'               => $result['title'],
                'type'                => (!empty($result["types"])) ? $result["types"] : array("http://www.w3.org/2002/07/owl#Thing")
            );
        }

        $this->geonamesSearchHits = array(
            "totalCount" => 15,
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
        $totalPages = ($limit > 0) ? ceil( $this->geonamesSearchHits['totalCount'] / $limit ) : 0;

        $geonamesPagination = new GeonamesPaginator($this->baseApiPath, $this->defaultLanguage, $limit, $offset);

        $paginationMetadata = array(
            "pageCount"  => $totalPages,
            "totalCount" => $this->geonamesSearchHits['totalCount'],
            "offset"     => $offset,
            "limit"      => $limit,
            "links"      => $geonamesPagination->getLinksMetadata($totalPages, $this->geonamesSearchHits['totalCount']),
            "allLanguagesCount" => $this->geonamesSearchHits['totalCount']  // is not possible to have all language count...the api does not support this feature
        );

        return $paginationMetadata;
    }


    public function getEntityDetails($europeanaEntityId)
    {

    }

    protected function doGeonamesRequest($lat, $lng, $word = '') {
        $params = $this->extraParameters;

        $word = urlencode(str_replace('"', '', trim($word)));
        $requestUrl = $this->geonamesBaseUrl . '?lat=' . $lat
            . '&lng=' . $lng .
            '&username=' . $this->geonamesUsername .
            '&radius=15'   // TODO set radius as parameter
            ;

        $contentType = (isset($params['content-type'])) ? $params['content-type'] : 'application/json';

        //limit
        $requestUrl .= (isset($params['limit'])) ? "&limit=" . $params['limit'] : '';
        $requestUrl .= (isset($params['offset'])) ? "&offset=" . $params['offset'] : '';

        //die ("a " . $requestUrl);

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