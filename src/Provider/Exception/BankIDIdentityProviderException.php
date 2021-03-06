<?php

namespace TheRezor\OAuth2\Client\Provider\Exception;

use Psr\Http\Message\ResponseInterface;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;

class BankIDIdentityProviderException extends IdentityProviderException
{
    /**
     * Creates client exception from response.
     *
     * @param  ResponseInterface  $response
     * @param  string  $data  Parsed response data
     *
     * @return IdentityProviderException
     */
    public static function clientException(ResponseInterface $response, $data)
    {
        return static::createInstance($response, $data);
    }

    protected static function createInstance(ResponseInterface $response, $data)
    {
        $message = $response->getReasonPhrase();
        $code = $response->getStatusCode();
        $body = $response->getBody();

        if (isset($data['error_description'])) {
            $message = $data['error_description'];
        }
        if (isset($data['code'])) {
            $code = $data['code'];
        }

        return new static((string) $message, (int) $code, (string) $body);
    }
}
