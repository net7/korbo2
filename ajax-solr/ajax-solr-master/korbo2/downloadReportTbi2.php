<?php

generateReport();



function generateReport() {

  $solrRequest = doCurlRequest('http://localhost:8080/korbo2-solr/collection1/select?facet=true&fq=basket_id_s:1&fq=type_ss:"http://www.freebase.com/visual_art/artwork"&fq=type_ss:"http://purl.org/net7/korbo2/types/in-progress"&fq=type_ss:"http://purl.org/net7/korbo2/types/bur-artwork-tbi-bi"&q=*:*&facet.field=resource_s&facet.field=type_ss&facet.field=label_ss&facet.limit=50&facet.mincount=1&f.resource_s.facet.limit=50&f.types_ss.facet.limit=50&f.label_ss.facet.limit=50&json.nl=map&fq=basket_id_s:1&df=abstract_txt&wt=json&rows=9999999');

$response = json_decode($solrRequest, true);

$fp = fopen('/tmp/tbi.csv', 'w');

fputcsv($fp, array("label", "korbo-uri", "artist", "location", "year", "zeri", "url", "description"));

$excludedIds = array(
946,1516,1174,1114,1155,1519,1501,1515,1157,1521,1499,1190,1493,1524,1526,1108,1489,1491,1490,1492,1513,1510,1511,1532,1498,1353,1347,1512,1494,1185,1184,959,953,1500,1128,1502,1527
);
 
foreach ($response["response"]["docs"] as $item) {

    if (in_array($item["id"], $excludedIds)) continue;
	
    $s =  $item["abstract_txt"][0];
	     
    preg_match_all("/^ARTIST:(.*)$/m",$s,$artist);
    preg_match_all("/^LOCATION:(.*)$/m",$s,$location);
    preg_match_all("/^YEAR:(.*)$/m",$s,$year);
    preg_match_all("/^ZERI:(.*)$/m",$s,$zeri);
    preg_match_all("/^URL:(.*)$/m",$s,$url);
    preg_match_all("/^DESCRIPTION:(.*)$/m",$s,$description);

//    print_r($artist);die;

    @$artist = (count($artist) > 0) ? $artist[0][0] : '';
    @$location = (count($location) > 0) ? $location[0][0] : '';
    @$year = (count($year) > 0) ? $year[0][0] : '';
    @$zeri = (count($zeri) > 0) ? $zeri[0][0] : '';
    @$url = (count($url) > 0) ? $url[0][0] : '';
    @$description = (count($description) > 0) ? $description[0][0] : '';
    
    @$fields = array($item["label_ss"][0], "http://purl.org/net7/korbo/item/" . $item["id"], $artist, $location, $year, $zeri, $url, $description);
    
    fputcsv($fp, $fields);
}

fclose($fp);

header("Content-disposition: attachment; filename=tbi.csv");
header("Content-Type: text/csv");
readfile('/tmp/tbi.csv');

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
