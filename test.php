<?php

#Kad uhvatis vremena, pocni koristit unit testove

require_once('Certificate.php');
require_once('FiskalRequestXML.php');
require_once('FiskalResponseXML.php');
require_once('CIS_Service.php');

//Init XML
$doc = new DOMDocument();
$doc->formatOutput = true;
$xml_string = file_get_contents('racun.xml');
$doc->loadXML($xml_string);


$c = new Certificate();
$c->loadFile('certificates/demo/my_private.pfx', 'pass');

$req = new FiskalRequestXML($doc, $c);

$req->setupZKI();

$req->insertHeadInRequest();
$req->sign();
$req->wrapSoapEnvelope();

$xml = $req->saveXML();
$cis = new CIS_Service();

try {
    $response_xml = $cis->doRequest($xml);
} catch (Exception $e) {
    echo 'Connection error! ' . $e->getMessage();
    die();
}
$res = new FiskalResponseXML($response_xml);
if ($e = $res->getErrorMessage()) {
    echo 'Error! ==> <br>' . $e;
} else {
    echo 'Success. <br>';
    if ($res->getType() === 'RacunOdgovor') {
        echo 'JIR: ' . $res->getJIR() . '<br>';
        echo 'ZKI: ' . $req->getZKI();

    }
}

