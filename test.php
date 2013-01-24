<?php

#Kad uhvatis vremena, pocni koristit unit testove

require_once('Certificate.php');

$c = new Certificate();
$c->loadFile('certificates/demo/my_private.pfx','pass');

/*
var_dump(
    $c->getX509Cert(),
    $c->getPrivateKey(),
    $c->getIssuer(),
    $c->getCertData(),
    $c->getIssuerAsString(),
    $c->getSerialNumber()
);
*/

$doc = new DOMDocument();
$xml_string = file_get_contents('racun.xml');
$doc->loadXML($xml_string);

require_once("FiskalRequestXML.php");

$fis = new FiskalRequestXML($doc, $c);

$fis->setupZKI();
$zki = $fis->getZKI();
echo $zki;

