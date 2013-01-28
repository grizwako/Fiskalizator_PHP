<?php

require_once('Certificate.php');
require_once('FiskalRequestXML.php');
require_once('FiskalResponseXML.php');
require_once('CIS_Service.php');

class _Fiskalizator {

    /** @var FiskalRequestXML */
    private $request;

    /** @var FiskalResponseXML */
    private $response;

    /** @var Certificate */
    private $cert;

    /** @var CIS_Service */
    private $cis;


    /**
     * @param $privateCertPfx
     * @param $privateCertPass
     * @param bool $productionMode
     * @throws Exception if there are problems with certificate loading
     */
    public function __construct($privateCertPfx, $privateCertPass,$productionMode = FALSE){
        $this->cert = new Certificate();
        $this->cert->loadFile($privateCertPfx,$privateCertPass);
        $this->cis = new CIS_Service();
        if ($productionMode) {
            $this->setProductionMode();
        }
    }

    /**
     * @return string Pure XML representation as string
     */
    public function getRawRequest() {
        return $this->request->saveXML();
    }

    /**
     * @return string Pure XML representation as string
     */
    public function getRawResponse() {
        return $this->response->saveXML();
    }

    public function getRequest() {
        return $this->request;
    }

    public function getResponse() {
        return $this->response;
    }

    public function getZKI() {
        return $this->request->getZKI();
    }

    public function getJIR() {
        return $this->response->getJIR();
    }

    public function setProductionMode() {
        $this->cis->setProductionMode();
    }


    /**
     * @param DOMDocument $xmldoc
     * @param int $attempts Maximum number of attempts in case of connection problems
     * @param int|float $timeout timeout for each attempted request in seconds.
     * Max precision is to milisecond (3 decimal places) like 4.217
     * @return bool true on success
     * @throws Exception On network problems, problems with signing, cis returning error information
     * Use ->getMessage() on caught exception
     */
    public function doRequest(DOMDocument $xmldoc, $attempts = 3, $timeout = 5) {

        $this->request = new FiskalRequestXML($xmldoc, $this->cert);
        if ($this->request->getType() == 'RacunZahtjev'){
            $this->request->setupZKI();
        }
        $this->request->insertHeadInRequest();
        $this->request->sign();
        $this->request->wrapSoapEnvelope();
        $xml = $this->getRawRequest();

        try {
            $responseXml = $this->cis->doRequest($xml,$attempts,$timeout);
        } catch (Exception $e) {
            throw $e;
        }
        $this->request = new FiskalResponseXML($responseXml);
        if ($e = $this->request->getErrorMessage()) {
            throw new Exception($e);
        }

        return true;
    }

    public function getRequestType(){
        return $this->request->getType();
    }

    public function getResponseType(){
        return $this->getResponseType();
    }


}