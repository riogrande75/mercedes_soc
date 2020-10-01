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
$acc_token = file_get_contents('/etc/eauto/access_token', true);
if($acc_token) getResource($acc_token);

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
        $status = curl_getinfo($curls, CURLINFO_HTTP_CODE);
        if($debug) echo "SoC_Abfrage_STATUS: $status\n";
        curl_close($curls);
        if($status==204)
                {
                if($debug) echo "Received empty response with status code 204. Vehicle did not provide update for >12h. Keeping soc unchanged.\n";
                exit(204);
                }
        logging("Status $status empfangen. Emfangen wurde $responseS");
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
        $soctime = substr(json_decode($responseS,true)['soc']['timestamp'],0,10);
        $range = json_decode($responseR,true)['rangeelectric']['value'];
        $rangetime = substr(json_decode($responseR,true)['rangeelectric']['timestamp'],0,10);
        if($debug)
                {
                echo "SoC_Abfrage_Response: $responseS\n";
                $dateInLocal = date("Y-m-d H:i:s", $soctime);
                echo "SocTime: ".$dateInLocal."\n";
                print_r(json_decode($responseS, true));
                echo "Range_Abfrage_Response: $responseR\n";
                $dateInLocal = date("Y-m-d H:i:s", $rangetime);
                echo "RangeTime: ".$dateInLocal."\n";
                echo "RangeResp: $responseR\n";
                print_r(json_decode($responseR, true));
                }
        if($soc>0 and $soc<100)
                {
                $fp = fopen ("/tmp/eauto.txt", "w");
                fwrite ($fp, $soc);
                fclose ($fp);
        }
}
function logging($txt, $write2syslog=false)
{
        global $debug,$logfilename;
        $fp_log = @fopen($logfilename.date("Y-M-d").".log", "a");
        {
        $dt = new DateTime(date("Y-m-d H:i:s"));
        $logdate = $dt->format("Y-m-d H:i:s");
        fwrite($fp_log, $logdate." $txt\n");
        }
}
?>
