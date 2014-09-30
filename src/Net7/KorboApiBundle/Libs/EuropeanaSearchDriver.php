<?php
/**
 * Created by JetBrains PhpStorm.
 * User: zitarosa
 */

namespace Net7\KorboApiBundle\Libs;


use Net7\KorboApiBundle\Utility\EuropeanaPaginator;

class EuropeanaSearchDriver extends AbstractSearchDriver {

    private $europeanaApiKey;

    private $europeanaBaseUrl;

    private $extraParameters;

    private $baseApiPath;

    private $defaultLanguage;

    private $configurationLanguage;

    public function __construct($europeanaBaseUrl, $europeanaApiKey, $baseApiPath, $extraParameters = array())
    {
        $this->europeanaApiKey      = $europeanaApiKey;
        $this->europeanaBaseUrl     = $europeanaBaseUrl;
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
     * Search over Europeana
     *
     * @param string $wordToSearch
     *
     * @return json results
     */
    public function search($wordToSearch)
    {

        // TODO add start / limit parameter to search url
        $results = array();

        $res = $this->doEuropeanaRequest($wordToSearch);
        $arrayResult = json_decode($res, true);

        // TODO: uniform data returned

        foreach ($arrayResult['items'] as $result) {

            $id = str_replace("/", "__", $result['id']);
            $metas = $this->getEntityMetadata("http://www.europeana.eu" . $result['id']);

            $results[] = array(
                'available_languages' => array("mul"),
                'id'                  => $id,
                'uri'                 => '',
                'basket_id'           => null,
                'depiction'           => $metas['depiction'],
                'abstract'            => $metas['abstract'],
                'resource'            => $metas['resource'],
                'uri'                 => "http://www.europeana.eu" . $result['id'],
                'label'               => $result['title'][0],
                'type'                => $result["type"]
            );
        }
//foreach ($arrayResult['items'] as $result) {
//            $results[] = array(
//                'available_languages' => 'mul',
//                'id'                  => str_replace("/", "__", $result['id']),
//                'label'               => $result['title'][0],
//            );
//        }

        $this->europeanaSearchHits = array(
            "totalCount" => $arrayResult['totalResults'],
            "itemsCount" => $arrayResult['itemsCount']
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
    public function getEntityMetadata($europeanaEntityUrl)
    {
        // checks if the url is valid
        if (strpos($europeanaEntityUrl, '//www.europeana.eu') === false){
            throw new \Exception("Invalid Europeana Resource URL");
        }

        $itemResponseContainer = new ItemResponseContainer();

        // TODO better replace
        $europeanaEntityId = str_replace('https://www.europeana.eu', '', $europeanaEntityUrl);
        $europeanaEntityId = str_replace('http://www.europeana.eu', '', $europeanaEntityId);

        $jsonEntity = json_decode( file_get_contents($europeanaEntityUrl), true );

        if (empty($jsonEntity['object'])) {
            // invalid europeanaUrl
           throw new \Exception("Invalid Europeana Resource ID");
        }

        // iterating over languages and types
        foreach ($jsonEntity['object']['title'] as $label){
            $itemResponseContainer->setLabel($label, 'mul');
        }

        $types = array();
        if (array_key_exists(0, $jsonEntity['object']['proxies']) && array_key_exists('dcDescription', $jsonEntity['object']['proxies'][0])) {
            foreach ($jsonEntity['object']['proxies'][0]["dcDescription"]['def'] as $type) {
                $types[] = $type;
            }
        }
        $itemResponseContainer->setTypes($types);


        // TODO:  stored only the first description...

        if (array_key_exists(0, $jsonEntity['object']['proxies']) && array_key_exists('dcDescription', $jsonEntity['object']['proxies'][0])) {
            $itemResponseContainer->setDescription($jsonEntity['object']['proxies'][0]["dcDescription"]['def'][0], 'mul');
        } else {
            // FIXME: the default description is the same as the label
            $labels = $itemResponseContainer->getLabels();
            $itemResponseContainer->setDescription($labels['mul'], 'mul');
        }


        if (array_key_exists("europeanaAggregation", $jsonEntity['object']) && array_key_exists('edmPreview', $jsonEntity['object']['europeanaAggregation'])) {
            $itemResponseContainer->setDepiction($jsonEntity['object']['europeanaAggregation']["edmPreview"]);
        }

        // format the result in a standard format that has to be passed to ItemPersister
        return $itemResponseContainer;
    }

    public function getPaginationMetadata()
    {
        $offset = (isset($params['offset'])) ? $params['offset'] : 0;
        $limit  = (isset($params['limit'])) ? $params['limit'] : 10;
        $totalPages = ($limit > 0) ? ceil( $this->europeanaSearchHits['totalCount'] / $limit ) : 0;

        $europeanaPagination = new EuropeanaPaginator($this->baseApiPath, $this->defaultLanguage, $limit, $offset);

        $paginationMetadata = array(
            "pageCount"  => $totalPages,
            "totalCount" => $this->europeanaSearchHits['totalCount'],
            "offset"     => $offset,
            "limit"      => $limit,
            "links"      => $europeanaPagination->getLinksMetadata($totalPages, $this->europeanaSearchHits['totalCount']),
            "allLanguagesCount" => $this->europeanaSearchHits['totalCount']
        );

        return $paginationMetadata;
    }


    public function getEntityDetails($europeanaEntityId)
    {
        $europeanaUri = "http://www.europeana.eu/api/v2/record/" . $europeanaEntityId . ".json?wskey=" . $this->europeanaApiKey;
        $entityMetadata = $this->getEntityMetadata($europeanaUri);
        $descriptions = $entityMetadata->getDescriptions();
        $labels = $entityMetadata->getLabels();

        // TODO: fix the language selection
        $label       = (array_key_exists('mul', $labels)) ? $labels["mul"] : $labels[$this->configurationLanguage];
        $description = (array_key_exists('mul', $descriptions)) ? $descriptions['mul'] : $descriptions[$this->configurationLanguage];

        return array(
            'id'                  => $europeanaEntityId,
//            "available_languages" => array_keys($descriptions),
            "available_languages" => 'mul',
            "label"               => $label,
            "resource"            => $europeanaUri,
            'abstract'            => $description,
            'depiction'           => $entityMetadata->getDepiction(),
            "type"                => $entityMetadata->getTypes(),
            "basket_id"           => null,
        );
    }

    protected function doEuropeanaRequest($word) {
        $params = $this->extraParameters;

        $word = urlencode(str_replace('"', '', trim($word)));
        $requestUrl = $this->europeanaBaseUrl . $this->europeanaApiKey . '&query=' . $word ;

        $contentType = (isset($params['content-type'])) ? $params['content-type'] : 'text/html';

        //limit
        $requestUrl .= (isset($params['limit'])) ? "&rows=" . $params['limit'] : '';
        $requestUrl .= (isset($params['offset'])) ? "&start=" . $params['offset'] : '';

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