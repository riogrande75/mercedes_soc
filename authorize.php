#!/usr/bin/php
<?php
$debug=0;
$authorize_url = "https://id.mercedes-benz.com/as/authorization.oauth2";
$token_url = "https://id.mercedes-benz.com/as/token.oauth2";
$FIN = "WDD242xxxx";
$acc_token = "";

// callback URL specified when the application was defined--has to match what the application says
$callback_uri = "https://localhost";

$api_url_soc = "https://api.mercedes-benz.com/vehicledata/v2/vehicles/".$FIN."/resources/soc"; //StateOfCharge only
$api_url_range = "https://api.mercedes-benz.com/vehicledata/v2/vehicles/".$FIN."/resources/rangeelectric"; //range only

//      client (application) credentials - located at apim.byu.edu
$client_id = "<your-client-id>";
$client_secret = "<your-client-secret>";

//Main calls
getAuthorizationCode();
getAccessToken($autho_code);

//Needed functions
function getAuthorizationCode() {
        global $authorize_url, $client_id, $callback_uri, $autho_code;
        $authorization_redirect_url = $authorize_url . "?response_type=code&client_id=" . $client_id . "&redirect_uri=" . $callback_uri . "&scope=mb:vehicle:mbdata:evstatus offline_access";
        header("Location: " . $authorization_redirect_url);
        // if you don't want to redirect
        echo "Geh in einem Browser zu\n---   \033[1m$authorization_redirect_url\033[0m   ---\nkopiere den code aus dem link und fuege ihn hier ein:\n";
        $autho_code = fgets(STDIN);
}
function getAccessToken($authorization_code) {
        global $debug, $token_url, $client_id, $client_secret, $callback_uri, $acc_token;

        $authorization = base64_encode("$client_id:$client_secret");
        $header = array("Authorization: Basic {$authorization}","Content-Type: application/x-www-form-urlencoded");
        $content = "grant_type=authorization_code&code=$authorization_code&redirect_uri=$callback_uri";
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

        if ($response === false) {
                echo "Failed";
                echo curl_error($curl);
                echo "Failed";
        } elseif (json_decode($response)) {
                if($debug){
                        echo "Code:";
                        echo $authorization_code;
                        echo $response;
                        }
        }
        $acc_token = json_decode($response)->access_token;
        $ref_token = json_decode($response)->refresh_token;
        $expires = json_decode($response)->expires_in;
        $token_type =  json_decode($response)->token_type;
        if($debug)
        {
                echo "\n*** AccessToken:".$acc_token."\n";
                echo "*** RefreshToken:".$ref_token."\n";
                echo "*** TokenType:".$token_type ."\n";
                $ablauf = time() + $expires;
                echo "*** Expires in:".$expires." Sekunden, also um ".date('H:i:s', $ablauf)."\n";
        }
        //Schreibe Access Token in file
        $fp1 = fopen ("/etc/eauto/access_token", "w");
        fwrite ($fp1, $acc_token);
        fclose ($fp1);
        //Schreibe Refresh Token in file
        $fp2 = fopen ("/etc/eauto/refresh_token", "w");
        fwrite ($fp2, $ref_token);
        fclose ($fp2);
}
