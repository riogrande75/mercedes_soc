# mercedes_soc
Read StateOfCharge and electric range for my car from mercedes api

Run authorize.php once to get a valid access and refresh token.
Then add refresh.php to crontab and run it every hour to keep access_token up to date and valid.
Run ressource.php to get SoC and electric range for your mercedes.

Everyhing else you need to know is state @ https://developer.mercedes-benz.com/ and https://developer.mercedes-benz.com/products/electric_vehicle_status
