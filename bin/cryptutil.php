<?php


require_once(__DIR__ . '/../src/bootstrap.php');


$secret = \Defuse\Crypto\Key::createNewRandomKey();
echo "You can use \"" . $secret->saveToAsciiSafeString() . "\" as key. Add it to the CRYPTO_SECRET environment variable.\n";


exit;




