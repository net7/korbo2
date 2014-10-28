<?php

generateReport();

function encodeCSV(&$value, $key){
    $value = iconv('UTF-8', 'WINDOWS-1252', html_entity_decode($value,ENT_COMPAT,'utf-8'));
}


function generateReport() {


$user="root";
$password="mda9858ip";
$database="korbo2";

$fp = fopen('/tmp/tbi.csv', 'w');
//fprintf($fp, chr(0xEF).chr(0xBB).chr(0xBF));
//fwrite($fp, "xEFxBBxBF");
fputcsv($fp, array("label", "korbo-uri", "artist", "location", "year", "zeri", "url", "description"));

$con=mysqli_connect("localhost",$user,$password,"korbo2");

$result = mysqli_query($con, "SELECT" . 
"    t.content as LABEL,".
"    CONCAT('http://purl.org/net7/korbo2/item/',i.id) as korbo_uri,".
"    t1.content as content".
" FROM item as i". 
"  INNER JOIN item_translation as t on i.id=t.object_id  ".
"  INNER JOIN item_translation as t1 on i.id=t1.object_id  ".
" WHERE i.basket_id=1 ". 
"  AND i.type like '%artwork-tbi-bi%' ".
"  AND t.field='label' AND t.locale='EN'".
"  AND t1.field='abstract' AND t1.locale='EN'".
"  AND i.id NOT IN  (946,1516,1174,1114,1155,1519,1501,1515,1157,1521,1499,1190,1493,1524,1526,1108,1489,1491,1490,1492,1513,1510,1511,1532,1498,1353,1347,1512,1494,1185,1184,959,953,1500,1128,1502,1527) ORDER BY LABEL") or die( "Impossibile effettuare la quer.");

 while($row = mysqli_fetch_array($result)) {
    $s = $row['content'];
    preg_match_all("/^ARTIST:(.*)$/m",$s,$artist);
    preg_match_all("/^LOCATION:(.*)$/m",$s,$location);
    preg_match_all("/^YEAR:(.*)$/m",$s,$year);
    preg_match_all("/^ZERI:(.*)$/m",$s,$zeri);
    preg_match_all("/^URL:(.*)$/m",$s,$url);
    preg_match_all("/^DESCRIPTION:(.*)$/m",$s,$description);

//    print_r($artist);die;

    @$artist = (count($artist) > 0) ? str_replace("ARTIST:", "", $artist[0][0]) : '';
    @$location = (count($location) > 0) ? str_replace("LOCATION:", "", $location[0][0]) : '';
    @$year = (count($year) > 0) ? str_replace("YEAR:", "", $year[0][0]) : '';
    @$zeri = (count($zeri) > 0) ? str_replace("ZERI:", "", $zeri[0][0]) : '';
    @$url = (count($url) > 0) ? str_replace("URL:", "", $url[0][0]) : '';
    @$description = (count($description) > 0) ? str_replace("DESCRIPTION:", "", $description[0][0]) : '';

    //$label = iconv('UTF-8', 'WINDOWS-1252', html_entity_decode($row['LABEL'],ENT_COMPAT,'utf-8'));    
    //echo $label;
    @$fields = array($row['LABEL'], $row['korbo_uri'], $artist, $location, $year, $zeri, $url, $description);

//    array_walk($fields, 'encodeCSV');    

    fputcsv($fp, $fields);

}
//die;
fclose($fp);

header('Content-Type: text/html; charset=UTF-8');
header("Content-disposition: attachment; filename=tbi.csv");
header("Content-Type: text/csv");
readfile('/tmp/tbi.csv');


die;

$response = json_decode($solrRequest, true);





$excludedIds = array(
946,1516,1174,1114,1155,1519,1501,1515,1157,1521,1499,1190,1493,1524,1526,1108,1489,1491,1490,1492,1513,1510,1511,1532,1498,1353,1347,1512,1494,1185,1184,959,953,1500,1128,1502,1527
);
 
foreach ($response["response"]["docs"] as $item) {

    if (in_array($item["id"], $excludedIds)) continue;
	
    $s =  $item["abstract_txt"][0];
	     

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
