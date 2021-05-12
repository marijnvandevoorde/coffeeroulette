<?php

namespace Marijnworks\Zoomroulette\Zoomroulette;

use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;

class EncryptionToolkit
{
    private ?Key $key;

    public function __construct(?Key $key)
    {
        $this->key = $key;
    }

    public function encrypt(string $plainText): string
    {
        return Crypto::encrypt($plainText, $this->key);
    }

    public function decrpyt(string $cipherText): string
    {
        return Crypto::decrypt($cipherText, $this->key);
    }
}
