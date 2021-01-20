<?php

namespace TheRezor\OAuth2\Client\Provider;

use TheRezor\OAuth2\Client\Cipher\EUSign;
use TheRezor\OAuth2\Client\Cipher\CipherInterface;
use TheRezor\OAuth2\Client\Provider\Exception\BankIDIdentityProviderException;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Tool\BearerAuthorizationTrait;
use Psr\Http\Message\ResponseInterface;
use UnexpectedValueException;

class BankID extends AbstractProvider
{
    use BearerAuthorizationTrait;

    public function __construct(array $options = [], array $collaborators = [])
    {
        parent::__construct($options, $collaborators);

        if (empty($collaborators['cipher'])) {
            $collaborators['cipher'] = new EUSign($this->getKey(), $this->getPassword());
        }
        $this->setCipher($collaborators['cipher']);
    }

    /**
     * @var string Key used in a token response to identify the resource owner.
     */
    const ACCESS_TOKEN_RESOURCE_OWNER_ID = 'memberId';

    /**
     * Default host
     *
     * @var string
     */
    protected $host = 'https://id.bank.gov.ua';

    /**
     * @var CipherInterface
     */
    protected $cipher;

    protected $fields = [
        'firstName',
        'middleName',
        'lastName',
        'phone',
        'inn',
        'birthDay',
        'sex',
    ];

    protected $addresses = [
        [
            'type'   => 'factual',
            'fields' => [
                'country',
                'state',
                'area',
                'city',
                'street',
                'houseNo',
                'flatNo',
            ],
        ],
    ];

    protected $documents = [
        [
            'type'   => 'passport',
            'fields' => [
                'series',
                'number',
                'issue',
                'dateIssue',
                'dateExpiration',
                'issueCountryIso2',
            ],
        ],
    ];

    protected $scans = [
        [
            'type'   => 'passport',
            'fields' => [
                'scanFile',
                'dateCreate',
                'extension',
            ],
        ],
    ];

    protected $cert = '';

    protected $key = '';

    protected $password = '';

    /**
     * @return CipherInterface
     */
    public function getCipher(): CipherInterface
    {
        return $this->cipher;
    }

    /**
     * @param  CipherInterface  $cipher
     * @return self
     */
    public function setCipher(CipherInterface $cipher)
    {
        $this->cipher = $cipher;

        return $this;
    }

    /**
     * Base64 of certificate
     *
     * @return string
     */
    public function getCert(): string
    {
        return $this->cert;
    }

    /**
     * Private key binary
     *
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * Private key password
     *
     * @return string
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * @return array
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    /**
     * @return array
     */
    public function getAddresses(): array
    {
        return $this->addresses;
    }

    /**
     * @return array
     */
    public function getDocuments(): array
    {
        return $this->documents;
    }

    /**
     * @return array
     */
    public function getScans(): array
    {
        return $this->scans;
    }

    /**
     * Gets host.
     *
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * Get authorization url to begin OAuth flow
     *
     * @return string
     */
    public function getBaseAuthorizationUrl()
    {
        return $this->host.'/v1/bank/oauth2/authorize';
    }

    /**
     * Get access token url to retrieve token
     *
     * @param  array  $params
     *
     * @return string
     */
    public function getBaseAccessTokenUrl(array $params)
    {
        return $this->host.'/v1/bank/oauth2/token';
    }

    /**
     * Get provider url to fetch user details
     *
     * @param  AccessToken  $token
     *
     * @return string
     */
    public function getResourceOwnerDetailsUrl(AccessToken $token)
    {
        return $this->host.'/v1/bank/resource/client';
    }

    /**
     * Requests resource owner details.
     *
     * @param  AccessToken  $token
     * @return mixed
     */
    protected function fetchResourceOwnerDetails(AccessToken $token)
    {
        $url = $this->getResourceOwnerDetailsUrl($token);

        $request = $this->getAuthenticatedRequest(
            self::METHOD_POST,
            $url,
            $token,
            [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'body'    => json_encode(
                    [
                        'cert'      => base64_encode($this->getCert()),
                        'type'      => 'physical',
                        'fields'    => $this->getFields(),
                        'addresses' => $this->getAddresses(),
                        'documents' => $this->getDocuments(),
                        'scans'     => $this->getScans(),
                    ]
                ),
            ]
        );

        $response = $this->getParsedResponse($request);

        if (false === is_array($response)) {
            throw new UnexpectedValueException(
                'Invalid response received from Authorization Server. Expected JSON.'
            );
        }

        return $response;
    }

    /**
     * Get the default scopes used by this provider.
     *
     * This should not be a complete list of all scopes, but the minimum
     * required for the provider user interface!
     *
     * @return array
     */
    protected function getDefaultScopes()
    {
        return [];
    }

    /**
     * Check a provider response for errors.
     *
     * @param  ResponseInterface  $response
     * @param  string  $data  Parsed response data
     * @return void
     * @throws IdentityProviderException
     */
    protected function checkResponse(ResponseInterface $response, $data)
    {
        // Standard error response format
        if (!empty($data['error'])) {
            throw BankIDIdentityProviderException::clientException($response, $data);
        }
    }

    /**
     * Generate a user object from a successful user details request.
     *
     * @param  array  $response
     * @param  AccessToken  $token
     * @return ResourceOwnerInterface
     */
    protected function createResourceOwner(array $response, AccessToken $token)
    {
        $decoded = [
            'memberId' => $response['memberId'],
            'sidBi'    => $response['sidBi'],
            'data'     => json_decode(
                $this->getCipher()->decode(
                    $response['customerCrypto'],
                    $response['cert']
                ),
                true
            ),
        ];

        return new BankIDResourceOwner($decoded);
    }
}
