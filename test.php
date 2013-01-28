<?php

require_once('_Fiskalizator.php');

//Init XML
$doc = new DOMDocument();
$doc->formatOutput = true;
$xml_string = file_get_contents('racun.xml');
$doc->loadXML($xml_string);

$fis = new _Fiskalizator('certificates/demo/my_private.pfx', 'pass');

#$fis->setProductionMode();

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