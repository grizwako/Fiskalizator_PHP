<?php

require_once('Fiskalizator.php');
$fis = new Fiskalizator();

$fis->certPath = 'demo.pfx';
$fis->certPass = 'pass';
$fis->CisUrl = 'https://cistest.apis-it.hr:8449/FiskalizacijaServiceTest';

$doc = new DOMDocument();
$xml_string = file_get_contents('racun.xml');
$doc->loadXML($xml_string);

$response = $fis->doRequest($doc);

if ($errors = $fis->getErrors() or $response === false ) {
	foreach ($errors as $error){
		echo 'Error ==> "'.htmlspecialchars($error).'"<br>';
	}
} else {
	echo 'Zahtjev uspješno izvršen.<br>';
	if ($jir = $fis->getJIR($response)){
		echo 'JIR: '.$jir;
	}
}
