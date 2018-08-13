<?php
## Copyright (C) 2018, The WRKZCoin developers
## php script to create checkpoints data for CryptoNoteCheckpoints.h


## Inspired from:
## https://github.com/turtlecoin/turtlecoin-docs/blob/master/api/daemon-json-rpc-api.md
## https://github.com/turtlecoin/turtlecoin/blob/development/scripts/checkpointer/full-checkpoints.py

## Sample usgae:
## php php-generate-checkpoints-CN.php http://127.0.0.1:17856

## This needs php-curl
if (!function_exists('curl_version')) {
	die("Curl extension is not installed or enabled.");
}

## need argument for remote RPC address
if (empty($argv[1])) {
	die("Please input remote RPC address without /. Example: php me.php http://127.0.0.1:17856");
}

if (filter_var(trim($argv[1]), FILTER_VALIDATE_URL) === FALSE) {
    die("Please input a valid remote RPC address without /. Example: php me.php http://127.0.0.1:17856");
}

// Generated by curl-to-PHP: http://incarnate.github.io/curl-to-php/
$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, trim($argv[1])."/getheight");
curl_setopt($ch, CURLOPT_TIMEOUT, 5); //timeout in seconds
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

$result = curl_exec($ch);
if (curl_errno($ch)) {
    echo 'Error:' . curl_error($ch);
}
curl_close ($ch);

$data_json = json_decode($result, true);

## If return empty
if (empty($data_json['height'])) {
	die("Invalid height result.");
} else {
	$height = (int)$data_json['height'];
}

## checkpoints every ?
$at = 500;

## Let's start data dump
if (!empty($height)) {
	$height = $height - 1;
	
	$x = 1; 
	$blockhashes = "";
	while($x < $height) {
		if ((($x - 1) % $at) == 0) {
			// Generated by curl-to-PHP: http://incarnate.github.io/curl-to-php/
			$ch = curl_init();

			curl_setopt($ch, CURLOPT_URL, "http://127.0.0.1:17856/json_rpc");
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, "{\"jsonrpc\":\"2.0\",\"method\":\"on_getblockhash\",\"params\":[".$x."]}");
			curl_setopt($ch, CURLOPT_POST, 1);

			$headers = array();
			$headers[] = "Content-Type: application/x-www-form-urlencoded";
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

			$result = curl_exec($ch);
			if (curl_errno($ch)) {
				die ('Error:' . curl_error($ch));
			}
			curl_close ($ch);
			
			$data_json = json_decode($result, true);
			if (strlen($data_json['result']) == 64) {
				if (($x - 1)+$at < $height) {
					$blockhashes .= '{'.($x - 1) . ',"' . $data_json["result"] . '"},'.PHP_EOL;
				} else {
					$blockhashes .= '{'.($x - 1) . ',"' . $data_json["result"] . '"}';
				}
				
			}
		}
		$x++;
	}
	
	## Write to file
	$checkpointfile = fopen("CryptoNoteCheckpoints_data_".date('Y-m-d-his'), "w") or die("Unable to create checkpoint data file!");
	fwrite($checkpointfile, $blockhashes);
	fclose($checkpointfile);
}
?>
