<?php

namespace TheRezor\OAuth2\Client\Cipher;

interface CipherInterface
{
    public function decode(string $data, string $cert): string;
}