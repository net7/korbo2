<?php
/**
 * Created by JetBrains PhpStorm.
 * User: zitarosa
 */

namespace Net7\KorboApiBundle\Libs;


class FreebaseSearchDriver extends AbstractSearchDriver {

    private $freebaseApiKey;

    private $freebaseBaseUrl;

    private $defaultLanguage;

    private $extraParameters;

    private $freebaseTopicBaseUrl;

    private $freebaseMqlBaseUrl;

    private $languagesToRetrieve;

    public function __construct($freebaseBaseUrl, $freebaseApiKey, $freebaseTopicBaseUrl, $freebaseMqlBaseUrl, $languagesToRetrieve, $extraParameters = array())
    {
        $this->freebaseApiKey       = $freebaseApiKey;
        $this->freebaseBaseUrl      = $freebaseBaseUrl;
        //$this->defaultLanguage      = ($defaultLanguage === false) ? 'en' : $defaultLanguage;
        $this->freebaseTopicBaseUrl = $freebaseTopicBaseUrl;
        $this->freebaseMqlBaseUrl   = $freebaseMqlBaseUrl;
        $this->languagesToRetrieve = $languagesToRetrieve;

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
    public function setDefaultLanguage($lang)
    {
        $this->defaultLanguage = $lang;
    }


    /**
     * Search over Freebase
     *
     * @param string $wordToSearch
     */
    public function search($wordToSearch)
    {

        // TODO add start / limit parameter to search url

        $res = $this->doFreebaseRequest($wordToSearch);

        print_r($res);die;

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
        $freebaseFormatLanguagesArray = $this->languagesToRetrieve;
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
           throw new \Exception("Invalid Url");
        }

        // iterating over languages and types
        foreach ($jsonEntity['result']['name'] as $label){
            $itemResponseContainer->setLabel($label['value'], str_replace('/lang/', '', $label['lang']));
        }

        $itemResponseContainer->setTypes($jsonEntity['result']['type']);

        // STEP 2
        foreach ($this->languagesToRetrieve as $languageToRetrieve){
            $params = array(
                'key' => $this->freebaseApiKey,
                'filter' => '/common/topic/description'
            );

            $jsonDescription = json_decode(file_get_contents($this->freebaseTopicBaseUrl . $freebaseEntityId . "?lang={$languageToRetrieve}&key={$this->freebaseApiKey}&filter=" . urlencode($params['filter'])), true);
            $itemResponseContainer->setDescription($jsonDescription['property']['/common/topic/description']['values'][0]['value'], $languageToRetrieve);
        }

        // format the result in a standard format that has to be passed to ItemPersister
        return $itemResponseContainer;
    }


    public function getEntityDetails($freebaseEntityId)
    {


    }

    protected function doFreebaseRequest($word, $params = array()) {

        $word = urlencode(str_replace('"', '', trim($word)));
        $requestUrl = $this->freebaseBaseUrl . $word ;

        $requestUrl .=  '&key=' . $this->freebaseApiKey;

        $contentType = (isset($params['content-type'])) ? $params['content-type'] : 'text/html';

        $requestUrl .= '&lang=' . $this->defaultLanguage;

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