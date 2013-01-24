<?php

class CIS_Service {

    private $url = 'https://cistest.apis-it.hr:8449/FiskalizacijaServiceTest';
    private $ca_cert = 'certificates/demo/ssl_cis.crt';
    private $timeout = 5;


    /**
     * @param string $xml
     * @return string $xml or false on failure
     */
    public function call($xml){
        $conn = curl_init();

        $settings = array(
            CURLOPT_URL		=> $this->url ,
            CURLOPT_CONNECTTIMEOUT_MS	=> $this->timeout * 1000,
            CURLOPT_TIMEOUT_MS 	=> $this->timeout * 1000,
            CURLOPT_RETURNTRANSFER	=> true,
            CURLOPT_POST 		=> true,
            CURLOPT_POSTFIELDS 	=> $xml,

            // secure this!
            CURLOPT_SSL_VERIFYHOST  => 2,
            CURLOPT_SSL_VERIFYPEER 	=> true,
            CURLOPT_CAINFO 		=> dirname(__FILE__) .'/'.$this->ca_cert,
        );

        curl_setopt_array($conn, $settings);

        $res = curl_exec($conn);
        return $res;
    }

}