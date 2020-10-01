#!/usr/bin/php
<?php
$debug=0;
$token_url = "https://id.mercedes-benz.com/as/token.oauth2";

//      client (application) credentials - located at apim.byu.edu
$client_id = "<your-client-id>";
$client_secret = "<your-client-secret>";

//Main calls
refreshTokens();

//Needed function
function refreshTokens()
        {
        global $debug, $token_url, $client_id, $client_secret;
        $refresh_token = file_get_contents('/etc/eauto/refresh_token',true);
        if(!$refresh_token)
                {
                echo "ACHTUNG: Kein Refresh Token vorhanden! - Bitte authorize.php ausführen!\n";
        }
        $authorization = base64_encode("$client_id:$client_secret");
        $header = array("Authorization: Basic {$authorization}","Content-Type: application/x-www-form-urlencoded");
        $content = "grant_type=refresh_token&refresh_token=$refresh_token";
        $curl = curl_init();
        curl_setopt_array($curl, array(
                CURLOPT_URL => $token_url,
                CURLOPT_HTTPHEADER => $header,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $content
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        if($debug) echo "JSON:json_decode($response)\n";

        $acc_token = json_decode($response)->access_token;
        $ref_token = json_decode($response)->refresh_token;
        $expires = json_decode($response)->expires_in;
        if($debug)
        {
                echo "\n*** AccessToken:".$acc_token."\n";
                echo "*** RefreshToken:".$ref_token."\n";
                $ablauf = time() + $expires;
                echo "*** Expires in:".$expires." Sekunden, also um ".date('H:i:s', $ablauf)."\n";
        }
        //Schreibe neues Access Token in file
        $fp1 = fopen ("/etc/eauto/access_token", "w");
        fwrite ($fp1, $acc_token);
        fclose ($fp1);
        //Schreibe neues Refresh Token in file
        $fp2 = fopen ("/etc/eauto/refresh_token", "w");
        fwrite ($fp2, $ref_token);
        fclose ($fp2);
}
?>
