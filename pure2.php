<?php

require_once('Fiskalizator.php');
$fis = new Fiskalizator();

$fis->certPath = 'path/to/demo/demo.pfx';
$fis->certPass = 'mypass';
$fis->CisUrl = 'https://cistest.apis-it.hr:8449/FiskalizacijaServiceTest';

$xml_string = file_get_contents('racun.xml');
$doc = DOMDocument::loadXML($xml_string);

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
