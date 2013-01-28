<?php

require_once('UUID.php');

class FiskalRequestXML
{

    private $doc;
    private $allowedTypes;

    public function __construct(DOMDocument $doc, Certificate $cert) {
        $this->doc = $doc;
        $this->cert = $cert;
        $this->allowedTypes = array('RacunZahtjev','PoslovniProstorZahtjev');
    }

    public function getZKI() {
        if ($node = $this->doc->getElementsByTagName('ZastKod')->item(0)) {
            return $node->nodeValue;
        }
        return false;
    }

    /**
     * Adds "ZastKod" if it is not already set
     */
    public function setupZKI() {
        if ($this->doc->getElementsByTagName('ZastKod')->item(0)) {
            return true;
        }
        $prefix = $this->doc->documentElement->prefix;
        $zki = $this->calculateZKI();
        $nakDost = $this->doc->getElementsByTagName('NakDost')->item(0);
        $zkiNode = new DOMElement("{$prefix}:ZastKod", $zki, $this->doc->documentElement->namespaceURI);
        $invoice = $this->doc->getElementsByTagName('Racun')->item(0);
        $invoice->insertBefore($zkiNode, $nakDost);

        return true;
    }

    /**
     * @return string|bool ZKI or FALSE on failure
     */
    private function calculateZKI() {

        $nodes = array('Oib', 'DatVrijeme', 'BrOznRac', 'OznPosPr', 'OznNapUr', 'IznosUkupno');
        $vals = array();
        foreach ($nodes as $node) {
            $vals[$node] = $this->doc->getElementsByTagName($node)->item(0)->nodeValue;
        }

        $vals['DatVrijeme'] = str_replace('T', ' ', $vals['DatVrijeme']);
        $temp = implode('', $vals);

        try {
            $zkiTemp = $this->cert->calculateSignature($temp);
            return md5($zkiTemp);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Wraps SOAP-ENV around current root element
     */
    public function wrapSoapEnvelope() {
        $soapNs = 'SOAP-ENV';
        $soapNsUri = 'http://schemas.xmlsoap.org/soap/envelope/';
        $this->doc->createAttributeNS($soapNsUri, $soapNs);

        $envelope = new DOMElement("{$soapNs}:Envelope", null, $soapNsUri);
        $body = new DOMElement("{$soapNs}:Body", null, $soapNsUri);

        #take reference so we don't loose it
        $rootNode = $this->doc->documentElement;
        $this->doc->replaceChild($envelope, $rootNode);


        $envelope->appendChild($body);
        $body->appendChild($rootNode);
    }

    public function insertHeadInRequest() {

        $prefix = $this->doc->documentElement->prefix;

        $dateTime = date('d.m.Y') . 'T' . date('H:i:s');

        $header = new DOMElement("{$prefix}:Zaglavlje", '', $this->doc->documentElement->namespaceURI);
        $reqDateTime = new DOMElement("{$prefix}:DatumVrijeme", $dateTime, $this->doc->documentElement->namespaceURI);
        $reqUUID = new DOMElement("{$prefix}:IdPoruke", UUID::v4(), $this->doc->documentElement->namespaceURI);

        $refElem = $this->doc->documentElement->firstChild;

        $this->doc->documentElement->insertBefore($header, $refElem);

        $header->appendChild($reqUUID);
        $header->appendChild($reqDateTime);

    }

    public function sign() {

        $this->doc->documentElement->setAttribute('Id', 'signXmlId');

        $canonical = $this->doc->C14N(true, false);
        $signatureDigest = base64_encode(hash('sha1', $canonical, true));
        $this->addSignatureNode($signatureDigest);

        $signedInfoNode = $this->doc->getElementsByTagName('SignedInfo')->item(0);
        $sigNodeXMLString = $signedInfoNode->C14N(true);

        try {
            $signature = $this->cert->calculateSignature($sigNodeXMLString);
        } catch (Exception $e) {
            return false;
        }

        $signatureValue = base64_encode($signature);

        $sigNode = $this->doc->getElementsByTagName('Signature')->item(0);
        $sigNode->appendChild(new DOMElement('SignatureValue', $signatureValue));

        $this->addX509Node();
        return true;
    }

    private function addSignatureNode($digest) {
        $rootElem = $this->doc->documentElement;

        $sigNode = $rootElem->appendChild(new DOMElement('Signature'));
        $sigNode->setAttribute('xmlns', 'http://www.w3.org/2000/09/xmldsig#');

        $signedInfoNode = $sigNode->appendChild(new DOMElement('SignedInfo'));
        $signedInfoNode->setAttribute('xmlns', 'http://www.w3.org/2000/09/xmldsig#');

        $canonMethodNode = $signedInfoNode->appendChild(new DOMElement('CanonicalizationMethod'));
        $canonMethodNode->setAttribute('Algorithm', 'http://www.w3.org/2001/10/xml-exc-c14n#');

        $signatureMethodNode = $signedInfoNode->appendChild(new DOMElement('SignatureMethod'));
        $signatureMethodNode->setAttribute('Algorithm', 'http://www.w3.org/2000/09/xmldsig#rsa-sha1');

        $referenceNode = $signedInfoNode->appendChild(new DOMElement('Reference'));
        $referenceNode->setAttribute('URI', '#signXmlId');

        $transformsNode = $referenceNode->appendChild(new DOMElement('Transforms'));
        $tr1Node = $transformsNode->appendChild(new DOMElement('Transform'));
        $tr2Node = $transformsNode->appendChild(new DOMElement('Transform'));
        $tr1Node->setAttribute('Algorithm', 'http://www.w3.org/2000/09/xmldsig#enveloped-signature');
        $tr2Node->setAttribute('Algorithm', 'http://www.w3.org/2001/10/xml-exc-c14n#');

        $digestMethodNode = $referenceNode->appendChild(new DOMElement('DigestMethod'));
        $digestMethodNode->setAttribute('Algorithm', 'http://www.w3.org/2000/09/xmldsig#sha1');

        $referenceNode->appendChild(new DOMElement('DigestValue', $digest));
    }

    private function addX509Node() {
        $sigNode = $this->doc->getElementsByTagName('Signature')->item(0);

        $keyInfoNode = $sigNode->appendChild(new DOMElement('KeyInfo'));
        $x509DataNode = $keyInfoNode->appendChild(new DOMElement('X509Data'));

        $x509DataNode->appendChild(new DOMElement('X509Certificate', $this->cert->getX509Cert()));
        $x509IssuerSerialNode = $x509DataNode->appendChild(new DOMElement('X509IssuerSerial'));
        $x509IssuerSerialNode->appendChild(new DOMElement('X509IssuerName', $this->cert->getIssuerAsString()));
        $x509IssuerSerialNode->appendChild(new DOMElement('X509SerialNumber', $this->cert->getSerialNumber()));
    }

    public function saveXML() {
        return $this->doc->saveXML();
    }

    public function getType() {
        foreach ($this->allowedTypes as $type) {
            if ($this->doc->getElementsByTagName($type)->item(0)) {
                return $type;
            }
        }

        return false;
    }

}