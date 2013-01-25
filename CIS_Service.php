<?php

class CIS_Service
{

    private $url = 'https://cistest.apis-it.hr:8449/FiskalizacijaServiceTest';
    private $caCert = 'certificates/demo/ssl_cis.crt';


    /**
     * @param string $xml SOAP request going to service
     * @param int $attempts Maximum number of attempts in case of connection problems
     * @param int|float $timeout timeout for each attempted request in seconds.
     * Max precision is to milisecond (3 decimal places) like 4.217
     * @return string $xml or false on failure
     * @throws Exception in case of connection problems
     */
    public function doRequest($xml, $attempts = 3, $timeout = 0.07) {

        if (!$attempts or !is_int($attempts)) {
            $attempts = 1;
        }

        $exceptionsText = false;
        while ($attempts > 0) {
            $attempts--;
            try {
                $outXml = $this->call($xml, $timeout);
                return $outXml;
            } catch (Exception $e) {
                $exceptionsText .= $e->getMessage() . ' || ';
            }
        }
        throw new Exception($exceptionsText);
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
            CURLOPT_CAINFO => dirname(__FILE__) . '/' . $this->caCert,
        );

        curl_setopt_array($conn, $settings);

        if ($rawResponse = curl_exec($conn)) {
            return $rawResponse;
        } else {
            throw new Exception('CODECURL: ' . curl_error($conn));
        }
    }

    public function setProductionMode() {
        $this->url = 'https://cis.porezna-uprava.hr:8449/FiskalizacijaService';
        $this->caCert = 'certificates/production/ssl_cis.crt';
    }

}