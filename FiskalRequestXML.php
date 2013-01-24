<?php

require_once('UUID.php');

class FiskalRequestXML {

    private $doc;

    public function __construct(DOMDocument $doc, Certificate $cert){
        $this->doc = $doc;
        $this->cert = $cert;
    }

    public function getZKI() {
        if ($node = $this->doc->getElementsByTagName('ZastKod')->item(0)){
            return $node->nodeValue;
        }
    }

    /**
     * Adds "ZastKod" if it is not already set
     */
    public function setupZKI() {
        if ($this->doc->getElementsByTagName('ZastKod')->item(0)){
            return true;
        }
        $prefix =  $this->doc->documentElement->prefix;
        $zki = $this->calculateZKI();
        $NakDost = $this->doc->getElementsByTagName('NakDost')->item(0);
        $ZKINode = new DOMElement("{$prefix}:ZastKod", $zki, $this->doc->documentElement->namespaceURI);
        $invoice = $this->doc->getElementsByTagName('Racun')->item(0);
        $invoice->insertBefore($ZKINode, $NakDost);

        return true;
    }

    /**
     * @return string|bool ZKI or FALSE on failure
     */
    private function calculateZKI() {

        $nodes = array('Oib','DatVrijeme','BrOznRac','OznPosPr','OznNapUr','IznosUkupno');
        $vals = array();
        foreach ($nodes as $node){
            $vals[$node] = $this->doc->getElementsByTagName($node)->item(0)->nodeValue;
        }

        $vals['DatVrijeme'] = str_replace('T', ' ', $vals['DatVrijeme']);
        $temp = implode('', $vals);

        try {
            $zki_temp = $this->cert->calculateSignature($temp);
            return md5($zki_temp);
        } catch (Exception $e) {
            return false;
        }
    }

    public function wrapSoapEnvelope() {

    }

    public function insertHeadInRequest() {

        $prefix =  $this->doc->documentElement->prefix;

        $dateTime = date('d.m.Y').'T'.date('H:i:s');


        $header = new DOMElement("{$prefix}:Zaglavlje", '', $this->doc->documentElement->namespaceURI);
        $req_dateTime = new DOMElement("{$prefix}:DatumVrijeme", $dateTime, $this->doc->documentElement->namespaceURI);
        $req_UUID = new DOMElement("{$prefix}:IdPoruke", UUID::v4(), $this->doc->documentElement->namespaceURI);

        $refElem = $this->doc->documentElement->firstChild;

        $this->doc->documentElement->insertBefore($header, $refElem);

        $header->appendChild($req_UUID);
        $header->appendChild($req_dateTime);

    }

}