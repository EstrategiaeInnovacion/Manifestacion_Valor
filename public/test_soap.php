<?php
echo "PHP Version: " . phpversion() . "<br>";
echo "SOAP Extension Loaded: " . (extension_loaded('soap') ? 'YES' : 'NO') . "<br>";
echo "OpenSSL Extension Loaded: " . (extension_loaded('openssl') ? 'YES' : 'NO') . "<br>";

if (extension_loaded('soap')) {
    echo "<br>SOAP Classes Available:<br>";
    echo "- SoapClient: " . (class_exists('SoapClient') ? 'YES' : 'NO') . "<br>";
    echo "- SoapServer: " . (class_exists('SoapServer') ? 'YES' : 'NO') . "<br>";
}
