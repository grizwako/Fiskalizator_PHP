<?php

#Kad uhvatis vremena, pocni koristit unit testove

require_once('Certificate.php');
require_once('FiskalRequestXML.php');
require_once('CIS_Service.php');

//Init XML
$doc = new DOMDocument();
#$doc->formatOutput = true;
#$doc->preserveWhiteSpace = false;
$xml_string = file_get_contents('racun.xml');
$doc->loadXML($xml_string);


$c = new Certificate();
$c->loadFile('certificates/demo/my_private.pfx','pass');

$req = new FiskalRequestXML($doc, $c);

$req->setupZKI();

$req->insertHeadInRequest();
$req->sign();
$req->wrapSoapEnvelope();

$xml = $req->saveXML();
$cis = new CIS_Service();
#$zki = $req->getZKI();
#die($xml);
$response_xml = $cis->call($xml);

echo $response_xml;


