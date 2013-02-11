PHP Fiskalizacija (Fiskalizator_PHP)
====================================

Usage: example.php

Open source pure PHP implementation of Croatian Fiscalization protocol.

Requirements:  
0.  PHP 5.4, if you want to use some older version, you will have to make some small changes. PHP 5.3 does not suppert someFunction()[0] syntax.
1.  Enable OpenSSL and CURL extensions in php.ini (extension=php_curl.dll and extension=php_openssl.dll)  
2.  Open example.php in your favorite text editor and change path and password for your certificate file.  
3.  Open "racun.xml" and change OIB field to match OIB used in certificate.
OIB has exactly 11 numeric characters.  
  
  
Croatian Tax Administration recommends running application for two days in DEMO mode
to verify that everything works correctly.  
***!After!*** TWO days of runtime without problems have passed, run $fis->setProductionMode().

Features:
 * Automatic retry on network errors with custom specifiable timeout
 * Auto add ZKI (protection code) if it not defined in XML (no need to calculate it yourself)
 * Auto generate UUID and setup message header (UUID and datetime field)
 * Convenience methods on main class (it is actually only a module facade),  
you are free to rewrite Fiskaloizator.php as you see fit.
 * Lots of error checking, if something can go wrong and it is not taken into account, pls open new Issue on github
