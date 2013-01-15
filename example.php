<?php

require_once('Fiskalizator.php');
$fis = new Fiskalizator();

#UNCOMMENT FOLLOWING LINE AFTER YOU THOROUGHLY TESTED DEMO MODE (service provider says 2 days minimum)
#$fis->setProductionMode();
#Also, do not forget to change certPath and certPass to match your production certificate

#Private key used to add your signature to xml request
$fis->certPath = 'demo.pfx';
$fis->certPass = 'pass';


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
