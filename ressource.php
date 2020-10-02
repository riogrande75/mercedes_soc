#!/usr/bin/php
<?php
$debug=0;
$logfilename = "/etc/eauto/log/api_"; //Path for logfiles, needs to be created
if($argc>1) //if called with "info" display debug output
        {
        if($argv[1] == "info") $debug=1;
        }
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
                exit($status);
                }
        if($status==402 || $status==403 || $status==404)
                {
                if($debug) echo "Received status code $status\n";
                $exVeErrorId = json_decode($responseS,true)['exVeErrorId'];
                $exVeErrorMsg = json_decode($responseS,true)['exVeErrorMsg'];
                if($debug) echo "ErrorID:$exVeErrorId, $exVeErrorMsg\n";
                logging("ERROR: ErrorID: $exVeErrorId, $exVeErrorMsg");
                exit($status);
                }
        if($status==500 || $status==502 || $status==502 || $status==503)
                {
                if($debug) echo "Received status code $status\n";
                $fault =  json_decode($responseS,true)['fault']['faultstring'];
                if($debug) echo "Fault: $fault\n";
                logging("FAULT: $fault");
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
