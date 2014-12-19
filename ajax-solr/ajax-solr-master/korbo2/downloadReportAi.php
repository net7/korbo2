<?php

generateReport();



function generateReport() {

  $solrRequest = doCurlRequest('http://localhost:8080/korbo2-solr/collection1/select?facet=true&fq=basket_id_s%3A1&fq=type_ss%3A%22http%3A%2F%2Fwww.freebase.com%2Fvisual_art%2Fartwork%22&fq=type_ss%3A%22http%3A%2F%2Fpurl.org%2Fnet7%2Fkorbo2%2Ftypes%2Fin-progress%22&fq=type_ss%3A%22http%3A%2F%2Fpurl.org%2Fnet7%2Fkorbo2%2Ftypes%2Fbur-artwork-ai-bi%22&q=*%3A*&facet.field=resource_s&facet.field=type_ss&facet.field=label_ss&facet.limit=20&facet.mincount=1&f.resource_s.facet.limit=50&f.types_ss.facet.limit=50&f.label_ss.facet.limit=50&json.nl=map&fq=basket_id_s:1&df=abstract_txt&wt=json&rows=9999999');

$response = json_decode($solrRequest, true);

$fp = fopen('/tmp/ai.csv', 'w');

fputcsv($fp, array("label", "korbo-uri", "resource", "description"));

foreach ($response["response"]["docs"] as $item) {
//    print_r($item);die;

    $fields = array($item["label_ss"][0], "http://purl.org/net7/korbo/item/" . $item["id"], $item["resource_s"], $item["abstract_txt"][0]);
    
    fputcsv($fp, $fields);
}

fclose($fp);

header("Content-disposition: attachment; filename=ai.csv");
header("Content-Type: text/csv");
readfile('/tmp/ai.csv');

}

    function doCurlRequest($url) {
    $contentType = 'application/json';

    $request = curl_init();
        curl_setopt($request, CURLOPT_URL, $url);
        curl_setopt($request, CURLOPT_HTTPHEADER, array("Accept: {$contentType}"));
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
?>
