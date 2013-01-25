<?php

class CIS_Service
{

    private $url = 'https://cistest.apis-it.hr:8449/FiskalizacijaServiceTest';
    private $ca_cert = 'certificates/demo/ssl_cis.crt';

    /**
     * @var int|float
     */
    public $timeout = 5;


    /**
     * @param string $xml SOAP request going to service
     * @param int $attempts Maximum number of attempts in case of connection problems
     * @param int|float $timeout timeout for each attempted request in seconds.
     * Max precision is to milisecond (3 decimal places) like 4.217
     * @return string $xml or false on failure
     */
    public function doRequest($xml, $attempts = 3, $timeout = 10) {

        if (!$attempts) $attempts = 1;

        $outXml = false;

        while ($attempts > 0) {
            try {
                $outXml = $this->call($xml, $timeout);
                break;
            } catch (Exception $e) {

            }
        }

        return $outXml;
    }

    private function call($xml, $timeout) {
        $conn = curl_init();

        $settings = array(
            CURLOPT_URL => $this->url,
            CURLOPT_CONNECTTIMEOUT_MS => $timeout * 1000,
            CURLOPT_TIMEOUT_MS => $timeout * 1000,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $xml,

            // secure this!
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_CAINFO => dirname(__FILE__) . '/' . $this->ca_cert,
        );

        curl_setopt_array($conn, $settings);

        $res = curl_exec($conn);

        return $res;
    }

    public function setProductionMode() {
        $this->url = 'https://cis.porezna-uprava.hr:8449/FiskalizacijaService';
        $this->ca_cert = 'certificates/production/ssl_cis.crt';
    }

}