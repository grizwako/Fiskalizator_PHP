<?php

class FiskalResponseXML
{

    private static $allowedTypes;

    /**
     * @param string $xml
     */
    public function __construct($xml) {
        $this->doc = new DOMDocument();
        $this->doc->loadXML($xml);

        FiskalResponseXML::$allowedTypes = array(
            'RacunOdgovor', 'PoslovniProstorOdgovor'
        );
    }

    public function getJIR() {
        if ($jir = $this->doc->getElementsByTagName('Jir')->item(0)) {
            return $jir->nodeValue;
        }
        return false;
    }

    public function getType() {
        foreach (FiskalResponseXML::$allowedTypes as $type) {
            if ($this->doc->getElementsByTagName($type)->item(0)) {
                return $type;
            }
        }

        return false;
    }

    /**
     * @return bool|string Textual description of error, or FALSE if there are no errors
     */
    public function getErrorMessage() {

        if ($e1 = $this->doc->getElementsByTagName('faultstring')->item(0)) {
            return 'CODE0: ' . $e1->nodeValue;
        }
        if (!$this->doc->getElementsByTagName('Signature')) {
            return 'CODE1: Signature not found in response';
        }

        if ($e2 = $this->doc->getElementsByTagName('PorukaGreske')->item(0)) {
            return 'CODE2: ' . $e2->nodeValue;
        }


        return false;

    }
}