<?php
/**
 * Created by JetBrains PhpStorm.
 * User: zitarosa
 */

namespace Net7\KorboApiBundle\Libs;


use Net7\KorboApiBundle\Utility\FreebasePaginator;
use Net7\OpenpalApiBundle\Tests\Controller\FreebaseSearchDriverTest;

class FreebaseSearchDriver extends AbstractSearchDriver {

    private $freebaseApiKey;

    private $freebaseBaseUrl;

    private $defaultLanguage;

    private $configurationLanguage;

    private $extraParameters;

    private $freebaseTopicBaseUrl;

    private $freebaseMqlBaseUrl;

    private $languagesToRetrieve;

    private $freebaseImageUrl;

    private $freebaseSearchHits;

    private $baseApiPath;

    public function __construct($freebaseBaseUrl, $freebaseApiKey, $freebaseTopicBaseUrl, $freebaseMqlBaseUrl, $freebaseImageUrl, $languagesToRetrieve, $baseApiPath, $extraParameters = array())
    {
        $this->freebaseApiKey       = $freebaseApiKey;
        $this->freebaseBaseUrl      = $freebaseBaseUrl;
        $this->freebaseTopicBaseUrl = $freebaseTopicBaseUrl;
        $this->freebaseMqlBaseUrl   = $freebaseMqlBaseUrl;
        $this->languagesToRetrieve  = $languagesToRetrieve;
        $this->freebaseImageUrl     = $freebaseImageUrl;
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
     * @param string $lang
     */
    public function setDefaultLanguage($lang, $configurationLanguage = '')
    {
        $this->defaultLanguage = $lang;
        $this->configurationLanguage = $configurationLanguage;
    }


    /**
     * Search over Freebase
     *
     * @param string $wordToSearch
     */
    public function search($wordToSearch)
    {

        // TODO add start / limit parameter to search url
        $results = array();

        $res = $this->doFreebaseRequest($wordToSearch);
        $arrayResult = json_decode($res, true);

        foreach ($arrayResult['result'] as $result) {
            $results[] = array(
                'available_languages' => "",//array_keys($descriptions),
                'id' => str_replace("/", "__", $result['mid']),
                'label' => $result['name'],
            );
        }

        $this->freebaseSearchHits = $arrayResult['hits'];

        return $results;
    }

    /**
     * Retrieves all the metadata available from freebase in all the languages
     *
     * @param string $freebaseEntityUrl
     *
     * @return ItemResponseContainer
     *
     * @throws \Exception
     */
    public function getEntityMetadata($freebaseEntityUrl)
    {
        // checks if the url is valid
        if (strpos($freebaseEntityUrl, '//www.freebase.com') === false){
            throw new \Exception("Invalid Freebase Resource URL");
        }

        $itemResponseContainer = new ItemResponseContainer();

        // TODO better replace
        $freebaseEntityId = str_replace('https://www.freebase.com', '', $freebaseEntityUrl);
        $freebaseEntityId = str_replace('http://www.freebase.com', '', $freebaseEntityId);

        /*
            two steps import:

            1) getting first attributes name and type using mql

            2) getting the descriptions for each language (one call X language)
        */

        // setting up language array in "freebase like syntax"
        //$freebaseFormatLanguagesArray = $this->languagesToRetrieve;
        $freebaseFormatLanguagesArray = array($this->defaultLanguage);
        array_walk($freebaseFormatLanguagesArray, function(&$value, $key) { $value = '/lang/' . $value; });

        $mqlQuery = array(
            'id'   => $freebaseEntityId,
            "name" => array(array(
              'value' => null,
              'lang|=' => $freebaseFormatLanguagesArray,
              "lang" => null
          )),
            "type" => array()
        );

        $jsonEntity = json_decode( file_get_contents($this->freebaseMqlBaseUrl . urlencode(json_encode($mqlQuery))), true );

        if (empty($jsonEntity['result'])) {
            // invalid freebaseUrl
           throw new \Exception("Invalid Freebase Resource ID");
        }

        // iterating over languages and types
        foreach ($jsonEntity['result']['name'] as $label){
            $itemResponseContainer->setLabel($label['value'], str_replace('/lang/', '', $label['lang']));
        }

        $types = $jsonEntity['result']['type'];
        $types_new = array();
        if (count($types)>0) {
           foreach ($types as $type)
               $types_new[]='http://www.freebase.com'.$type;
        }
        $itemResponseContainer->setTypes($types_new);

        // STEP 2
        //$langs = $this->languagesToRetrieve;
        $langs = array($this->defaultLanguage);
        foreach ($langs as $languageToRetrieve){
            $params = array(
                'key' => $this->freebaseApiKey,
                'filter' => '/common/topic/description'
            );

            $jsonDescription = json_decode(file_get_contents($this->freebaseTopicBaseUrl . $freebaseEntityId . "?lang={$languageToRetrieve}&key={$this->freebaseApiKey}&filter=" . urlencode($params['filter'])), true);
            $itemResponseContainer->setDescription($jsonDescription['property']['/common/topic/description']['values'][0]['value'], $languageToRetrieve);
        }

        $itemResponseContainer->setDepiction($this->freebaseImageUrl . $freebaseEntityId);

        // format the result in a standard format that has to be passed to ItemPersister
        return $itemResponseContainer;
    }

    public function getPaginationMetadata()
    {
        $offset = (isset($params['offset'])) ? $params['offset'] : 0;
        $limit  = (isset($params['limit'])) ? $params['limit'] : 10;
        $totalPages = ($limit > 0) ? ceil( $this->freebaseSearchHits / $limit ) : 0;

        $freebasePagination = new FreebasePaginator($this->baseApiPath, $this->defaultLanguage, $limit, $offset);

        $paginationMetadata = array(
            "pageCount"  => $this->freebaseSearchHits,
            "totalCount" => $this->freebaseSearchHits,
            "offset"     => $offset,
            "limit"      => $limit,
            "links"      => $freebasePagination->getLinksMetadata($totalPages, $this->freebaseSearchHits),
            "allLanguagesCount" => $this->freebaseSearchHits
        );

        return $paginationMetadata;
    }


    public function getEntityDetails($freebaseEntityId)
    {
        $freebaseUri = "http://www.freebase.com" . $freebaseEntityId;
        $entityMetadata = $this->getEntityMetadata($freebaseUri );
        $descriptions = $entityMetadata->getDescriptions();
        $labels = $entityMetadata->getLabels();
        $label       = (array_key_exists($this->defaultLanguage, $labels)) ? $labels[$this->defaultLanguage] : $labels[$this->configurationLanguage];
        $description = (array_key_exists($this->defaultLanguage, $descriptions)) ? $descriptions[$this->defaultLanguage] : $descriptions[$this->configurationLanguage];

        return array(
            'id'                  => $freebaseEntityId,
//            "available_languages" => array_keys($descriptions),
            "available_languages" => $this->languagesToRetrieve,
            "label"               => $label,
            "resource"            => $freebaseUri,
            'abstract'            => $description,
            'depiction'           => $entityMetadata->getDepiction(),
            "type"                => $entityMetadata->getTypes(),
            "basket_id"           => null,
        );
    }

    protected function doFreebaseRequest($word) {
        $params = $this->extraParameters;

        $word = urlencode(str_replace('"', '', trim($word)));
        $requestUrl = $this->freebaseBaseUrl . $word ;

        $requestUrl .=  '&key=' . $this->freebaseApiKey;

        $contentType = (isset($params['content-type'])) ? $params['content-type'] : 'text/html';

        $requestUrl .= '&lang=' . $this->defaultLanguage;

        //limit
        $requestUrl .= (isset($params['limit'])) ? "&limit=" . $params['limit'] : '';
        $requestUrl .= (isset($params['offset'])) ? "&cursor=" . $params['offset'] : '';

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