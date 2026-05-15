<?php
// Test 1 : TCP direct
echo "=== Test TCP ===\n";
$fp = fsockopen('tcp://smtp-relay.brevo.com', 587, $errno, $errstr, 10);
if ($fp) {
    echo "TCP OK - Réponse serveur : " . fgets($fp, 512);
    fclose($fp);
} else {
    echo "ERREUR TCP $errno : $errstr\n";
}

// Test 2 : via IP directement (contourne getaddrinfo)
echo "\n=== Test via IP ===\n";
$ip = gethostbyname('smtp-relay.brevo.com');
echo "IP résolue : $ip\n";
$fp2 = fsockopen("tcp://$ip", 587, $errno2, $errstr2, 10);
if ($fp2) {
    echo "IP OK - Réponse serveur : " . fgets($fp2, 512);
    fclose($fp2);
} else {
    echo "ERREUR IP $errno2 : $errstr2\n";
}

// Test 3 : SSL disponible ?
echo "\n=== SSL disponible : " . (extension_loaded('openssl') ? 'OUI' : 'NON') . " ===\n";
echo "Version OpenSSL : " . (defined('OPENSSL_VERSION_TEXT') ? OPENSSL_VERSION_TEXT : 'inconnue') . "\n";
