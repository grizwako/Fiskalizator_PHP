<?php

require_once('Fiskalizator.php');

//Init XML
$doc = new DOMDocument();
$doc->formatOutput = true;
$xml_string = file_get_contents('racun.xml');
$doc->loadXML($xml_string);


$fis = new _Fiskalizator('certificates/demo/my_private.pfx', 'pass');

#UNCOMMENT FOLLOWING LINE AFTER YOU THOROUGHLY TESTED DEMO MODE (service provider says 2 days minimum)
#$fis->setProductionMode();
#Also, do not forget to change certPath and certPass to match your production certificate

try {
    $fis->doRequest($doc);
    #custom timeout and number of retries on network error, default is 3 retries and 5 seconds timeout tolerance
    #$fis->doRequest($doc, 10, 5.2);
} catch (Exception $e) {
    echo $e->getMessage();
    die();
}

echo 'Success<br>';
if ($fis->getRequestType() == 'RacunZahtjev'){
    echo 'JIR: '.$fis->getJIR().'<br>';
    echo 'ZKI: '.$fis->getZKI().'<br>';
}

/**
 * For those without auto complete :)
 *
var_dump(
    $fis->getRequestType(),
    $fis->getResponseType(),
    $fis->getRequest(),
    $fis->getResponse(),
    $fis->getRawRequest(),
    $fis->getRawResponse()

);
*/