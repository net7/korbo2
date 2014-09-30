<?php
/**
 * Created by JetBrains PhpStorm.
 * User: zitarosa
 * Date: 5/14/14
 * Time: 3:45 PM
 * To change this template use File | Settings | File Templates.
 */

namespace Net7\KorboApiBundle\Libs;


class SearchDriverFactory {

    public static function createInstance($type = 'freebase', $container, $baseApiPath = '', $limit = 0, $offset = 0) {
        $instance = null;
        $type = strtolower($type);

        $options = array();
        if ($limit != 0) {
            $options['limit'] = $limit;
        }

        if ($offset != 0 ) {
            $options['offset'] = $offset;
        }

        switch ($type) {
            case "freebase":

                $instance = new FreebaseSearchDriver(
                    $container->getParameter('freebase_search_base_url'),
                    $container->getParameter('freebase_api_key'),
                    $container->getParameter('freebase_topic_base_url'),
                    $container->getParameter('freebase_base_mql_url'),
                    $container->getParameter('freebase_image_search'),
                    $container->getParameter('freebase_languages_to_retrieve'),
                    $baseApiPath,
                    $options
                );
                break;
            case "europeana":
                $instance = new EuropeanaSearchDriver(
                    $container->getParameter('europeana_search_base_url'),
                    $container->getParameter('europeana_api_key'),
                    $baseApiPath,
                    $options
                );
                break;
        }

        return $instance;
    }

}