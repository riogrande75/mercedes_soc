#!/usr/bin/php
<?php
$debug=0;

$FIN = "WDD242xxx";
$api_url_soc = "https://api.mercedes-benz.com/vehicledata/v2/vehicles/".$FIN."/resources/soc"; //StateOfCharge only
$api_url_range = "https://api.mercedes-benz.com/vehicledata/v2/vehicles/".$FIN."/resources/rangeelectric"; //range only

//      client (application) credentials - located at apim.byu.edu
$client_id = "<your-client-id>";
$client_secret = "<your-client-secret>";

//Main calls
//getAuthorizationCode();
//getAccessToken($autho_code);
$acc_token = file_get_contents('/etc/eauto/access_token', true);
//if($debug) echo "ACCESS_TOKEN:$acc_token\n";
getResource($acc_token);

//      we can now use the access_token as much as we want to access protected resources
function getResource($access_token) {
        global $debug, $api_url_soc, $api_url_range;
        $header = array("Authorization: Bearer {$access_token}");
        $curls = curl_init();
        curl_setopt_array($curls, array(
                CURLOPT_URL => $api_url_soc,
                CURLOPT_HTTPHEADER => $header,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_RETURNTRANSFER => true
        ));
        $responseS = curl_exec($curls);
        curl_close($curls);
        sleep(3);
        $curlr = curl_init();
        curl_setopt_array($curlr, array(
                CURLOPT_URL => $api_url_range,
                CURLOPT_HTTPHEADER => $header,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_RETURNTRANSFER => true
        ));
        $responseR = curl_exec($curlr);
        curl_close($curlr);
        // debugging
        $soc =  json_decode($responseS,true)['soc']['value'];
        $range = json_decode($responseR,true)['rangeelectric']['value'];
        if($debug)
        {
                echo "SocReso:$responseS\n";
                print_r(json_decode($responseS, true));
                echo "RangeResp:$responseR\n";
                print_r(json_decode($responseR, true));
        echo "SOC:$soc\n";
        echo "RANGE:$range\n";
        }
        $fp = fopen ("/tmp/eauto.txt", "w");
        fwrite ($fp, $soc);
        fclose ($fp);
}
?>
