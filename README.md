Fiskalizator_PHP
================

Pure PHP implementation of Croatian Fiscalization protocol.

Requirements:  
1.  Enable OpenSSL and CURL extensions in php.ini (extension=php_curl.dll and extension=php_openssl.dll)  
2.  Open example.php in your favorite text editor and change path and password for your certificate file.  
3.  Open "racun.xml" and change OIB field to match OIB used in certificate.
OIB has exactly 11 numeric characters.  
  
  
Croatian Tax Administration recommends running application for two days in DEMO mode
to verify that everything works correctly.  
***!After!*** two days of runtime without problems have passed, run $fis->setProductionMode().


