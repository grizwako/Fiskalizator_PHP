<?php

class Fiskalizator {
	
	public $CisUrl = 'https://cistest.apis-it.hr:8449/FiskalizacijaServiceTest';
	public $certPath = '';
	public $certPass = '';
	public $timeout = 5;

	private $errors = array();

	public function getJIR($xml) {

		$obj = simplexml_load_string($xml);
		if ($response = $obj->xpath("//*[local-name() = 'Jir']") ) {
			return (string)$response[0];
		}

		return false;
	}

	public function getErrors() {
		return $this->errors;
	}

	private function wrapSoapEnvelope($xml) {
		$orig_header = '<?xml version="1.0" encoding="UTF-8"?>';

		$soapHeader = '<?xml version="1.0" encoding="UTF-8"?>
			<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" xmlns:tns="http://www.apis-it.hr/fin/2012/types/f73"><SOAP-ENV:Body>';

		$soapFooter = '</SOAP-ENV:Body></SOAP-ENV:Envelope>';

		if (strpos($xml, $orig_header) === false){
			$this->errors[] = 'CODE5: Invalid XML declaration. Expecting <?xml version="1.0" encoding="UTF-8"?>';
			return false;
		}

		$xml = str_replace($orig_header, $soapHeader, $xml);

		$xml .= $soapFooter;

		return $xml;
	}



	public function doRequest(DOMDocument $doc){

		if (!$this->loadCertificateData()) return false;

		$this->appendHeaderToRequest($doc);

		if (!$signed_xml = $this->signXML($doc) ) return false;
		
		if (!$soapMessage = Fiskalizator::wrapSoapEnvelope($signed_xml)) return false;

		$conn = curl_init();

		$settings = array(
			CURLOPT_URL				=> $this->CisUrl ,
			CURLOPT_CONNECTTIMEOUT	=> $this->timeout,
			CURLOPT_TIMEOUT 		=> $this->timeout,
			CURLOPT_RETURNTRANSFER	=> true,
			CURLOPT_SSL_VERIFYPEER 	=> false,
			CURLOPT_POST 			=> true,
			CURLOPT_POSTFIELDS 		=> $soapMessage
		);

		curl_setopt_array($conn, $settings);

		if ($response = curl_exec($conn) ) {

			if ($this->checkResponseForErrors($response) === false) {
				return $response;
			}
			
			return $response;

		} else {

			$this->errors[] = 'CODECURL: '.curl_error($conn);
			return false;
		}
	}


	private function signXML(DOMDocument $doc){

		$rootElem = $doc->documentElement;
		$rootElem->setAttribute('Id', 'signXmlId');
		
		#dodaje/postavlja zastitni kod ako se radi o RacunZahtjev
		$this->addProtectionCodeForInvoice($doc);

		#calc digest here, so we dont have to specify what are we using to calculate digest
		$canonical = $doc->C14N(true, false);
		$signatureDigest = base64_encode(hash('sha1', $canonical, true));

		$this->addSignatureNode($doc,$signatureDigest);

		$signedInfoNode = $doc->getElementsByTagName('SignedInfo')->item(0);
		$sigNodeXMLString = $signedInfoNode->C14N(true);

		if (! openssl_sign ($sigNodeXMLString, $signature, $this->certPrivateKey, OPENSSL_ALGO_SHA1)) {
			$this->errors[] = 'CODE6: Failed to sign XML.';
		    return false;
		}

		$signatureValue = base64_encode($signature);

		$sigNode = $doc->getElementsByTagName('Signature')->item(0);
		$sigNode->appendChild(new DOMElement('SignatureValue', $signatureValue));

		$this->addX509Node($doc);

		return $doc->saveXML();

	}


	private function addX509Node($doc) {

		$sigNode = $doc->getElementsByTagName('Signature')->item(0);

		$keyInfoNode = $sigNode->appendChild(new DOMElement('KeyInfo'));
		$x509DataNode = $keyInfoNode->appendChild(new DOMElement('X509Data'));

		$x509DataNode->appendChild(new DOMElement('X509Certificate', $this->certPureText));
		$x509IssuerSerialNode = $x509DataNode->appendChild(new DOMElement('X509IssuerSerial'));
		$x509IssuerSerialNode->appendChild(new DOMElement('X509IssuerName',$this->certIssuerName));
		$x509IssuerSerialNode->appendChild(new DOMElement('X509SerialNumber',$this->certSerialNumber));
	}


	private function loadCertificateData() {
		if (!is_file($this->certPath)){
			$this->errors[] = 'CODE3: There is no certificate file!';
			return;
		}

		$certText = file_get_contents($this->certPath);
		openssl_pkcs12_read($certText, $cert, $this->certPass);

		if (!$cert){
			$this->errors[] = 'CODE4: Could not parse certificate! (Wrong password, or invalid certificate file)';
			return false;
		}
		
		$this->certPrivateKey = $cert['pkey'];

		$certData = openssl_x509_parse($cert['cert']);

		$pureCertText = $cert['cert'];
		$beginCertText = '-----BEGIN CERTIFICATE-----';
		$endCertText = '-----END CERTIFICATE-----';
		$pureCertText = str_replace($beginCertText, '', $pureCertText);

		$this->certPureText = str_replace($endCertText, '', $pureCertText);

		$issuerVals = array();
		foreach($certData['issuer'] as $key => $val){
			$issuerVals[] = $key.'='.$val;
		}

		$this->certIssuerName = implode(',',$issuerVals);
		$this->certSerialNumber = $certData['serialNumber'];

		return true;
	}


	private function addSignatureNode(DOMDocument $doc,$signatureDigest){
		$rootElem = $doc->documentElement;

		$sigNode = $rootElem->appendChild(new DOMElement('Signature'));
		$sigNode->setAttribute('xmlns','http://www.w3.org/2000/09/xmldsig#');

		$signedInfoNode = $sigNode->appendChild(new DOMElement('SignedInfo'));
		$signedInfoNode->setAttribute('xmlns','http://www.w3.org/2000/09/xmldsig#');

		$canonMethodNode = $signedInfoNode->appendChild(new DOMElement('CanonicalizationMethod'));
		$canonMethodNode->setAttribute('Algorithm','http://www.w3.org/2001/10/xml-exc-c14n#');

		$signatureMethodNode = $signedInfoNode->appendChild(new DOMElement('SignatureMethod'));
		$signatureMethodNode->setAttribute('Algorithm','http://www.w3.org/2000/09/xmldsig#rsa-sha1');

		$referenceNode = $signedInfoNode->appendChild(new DOMElement('Reference'));
		$referenceNode->setAttribute('URI','#signXmlId');

		$transformsNode = $referenceNode->appendChild(new DOMElement('Transforms'));
		$tr1Node = $transformsNode->appendChild(new DOMElement('Transform'));
		$tr2Node = $transformsNode->appendChild(new DOMElement('Transform'));
		$tr1Node->setAttribute('Algorithm','http://www.w3.org/2000/09/xmldsig#enveloped-signature');
		$tr2Node->setAttribute('Algorithm', 'http://www.w3.org/2001/10/xml-exc-c14n#');

		$digestMethodNode = $referenceNode->appendChild(new DOMElement('DigestMethod'));
		$digestMethodNode->setAttribute('Algorithm','http://www.w3.org/2000/09/xmldsig#sha1');

		$digestValueNode = $referenceNode->appendChild(new DOMElement('DigestValue',$signatureDigest));
	}


	private function checkResponseForErrors($response) {
		
		$obj = simplexml_load_string($response);
		
		if ($e = $obj->xpath('//faultstring')) {
			$this->errors[] = 'CODE0: '.(string)$e[0];
			return;
		}
		
		if ( !$obj->xpath("//*[local-name() = 'Signature']") ) {
			$this->errors[] = 'CODE1: Signature not found in response';
			return;
		}

		if ($e = $obj->xpath("//*[local-name() = 'PorukaGreske']") ) {
			$this->errors[] = 'CODE2: '.(string)$e[0];
		}
	}


	private function addProtectionCodeForInvoice($doc){
		$prefix =  $doc->documentElement->prefix;

		//We need ProtectionCode only for Invoice request.
		if ($doc->documentElement->tagName !== "{$prefix}:RacunZahtjev"){
			return false;
		}

		//If ProtectionCode exists, do not add it
		if ($doc->getElementsByTagName('ZastKod')->item(0)){
			return false;
		}

		$zki = $this->calculateZKI($doc);

		$invoice = $doc->getElementsByTagName('Racun')->item(0);

		$NakDost = $doc->getElementsByTagName('NakDost')->item(0);

		$ProtectionCodeNode = new DOMElement("{$prefix}:ZastKod", $zki, $doc->documentElement->namespaceURI);
		$invoice->insertBefore($ProtectionCodeNode, $NakDost);
	}


	private function calculateZKI($doc){
		
		$nodes = array('Oib','DatVrijeme','BrOznRac','OznPosPr','OznNapUr','IznosUkupno');
		$temp = '';
		
		foreach ($nodes as $node){
			$res = $doc->getElementsByTagName($node)->item(0);
			$temp .= $res->nodeValue;
		}

		if (! openssl_sign ($temp, $out, $this->certPrivateKey, OPENSSL_ALGO_SHA1)) {
			$this->errors[] = 'CODE7: Failed to sign "ZastitniKod".';
		    return false;
		}

		return md5($out);
	}

	private function appendHeaderToRequest($doc) {

		$prefix =  $doc->documentElement->prefix;

		$dateTime = date('d.m.Y').'T'.date('H:i:s');

		$header = new DOMElement("{$prefix}:Zaglavlje", '', $doc->documentElement->namespaceURI);
		$req_dateTime = new DOMElement("{$prefix}:DatumVrijeme", $dateTime, $doc->documentElement->namespaceURI);
		$req_UUID = new DOMElement("{$prefix}:IdPoruke", $this->generateUUID(), $doc->documentElement->namespaceURI);

		$refElem = $doc->documentElement->firstChild;

		$doc->documentElement->insertBefore($header, $refElem);

		$header->appendChild($req_UUID);
		$header->appendChild($req_dateTime);

	}

	private function generateUUID() {

		return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),
        mt_rand( 0, 0x0fff ) | 0x4000,
        mt_rand( 0, 0x3fff ) | 0x8000,
        mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ) );
	}


}