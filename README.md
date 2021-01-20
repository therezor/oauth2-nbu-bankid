# NBU BankID Provider for OAuth 2.0 Client
This package provides National Bank of Ukraine OAuth 2.0 support for the PHP League's [OAuth 2.0 Client](https://github.com/thephpleague/oauth2-client).

## Installation

1. Download and install eusphpe php extension \
    Download link: http://iit.com.ua/download/EUSPHPE-20200910.zip \
    For installation instructions read documentation inside installation archive (Documentation/EUSignPHPDescription.doc)

2. Add certificates to folder determined in step 1 \
    https://iit.com.ua/download/productfiles/CACertificates.p7b  \
    https://iit.com.ua/download/productfiles/CACertificates.Test.p7b

3. Add php library to your project using composer:
```
composer require therezor/oauth2-nbu-bankid
```
Have fun....

## Usage

Usage is the same as The League's OAuth client, using `\TheRezor\OAuth2\Client\Provider\BankID` as the provider.

### Authorization Code Flow

```php
$provider = new TheRezor\OAuth2\Client\Provider\BankID([
                        'clientId'     => '{your-client-id}',
                        'clientSecret' => '{your-client-secret}',
                        'redirectUri'  => 'https://example.com/callback-url',
                        'host'         => 'https://id.bank.gov.ua', // Optional, defaults to https://id.bank.gov.ua, for testing use https://testid.bank.gov.ua
                        'cert'         => file_get_contents('your_path/cert.cer'),
                        'key'          => file_get_contents('your_path/Key-6.dat'),
                        'password'     => '{your-private-key-password}',
                    ]);

if (!isset($_GET['code'])) {

    // If we don't have an authorization code then get one
    $authUrl = $provider->getAuthorizationUrl();
    $_SESSION['oauth2state'] = $provider->getState();
    header('Location: '.$authUrl);
    exit;

// Check given state against previously stored one to mitigate CSRF attack
} elseif (empty($_GET['state']) || ($_GET['state'] !== $_SESSION['oauth2state'])) {

    unset($_SESSION['oauth2state']);
    exit('Invalid state');

} else {

    // Try to get an access token (using the authorization code grant)
    $token = $provider->getAccessToken('authorization_code', [
        'code' => $_GET['code']
    ]);

    // Optional: Now you have a token you can look up a users profile data
    try {

        // We got an access token, let's now get the user's details
        $user = $provider->getResourceOwner($token);  
 
        // Use these details to create a new profile
        echo("Hello {$user->getFirstName()} {$user->getLastName()}");
    } catch (Exception $e) {

        // Failed to get user details
        exit('Oh dear...');
    }

    // Use this to interact with an API on the users behalf
    echo $token->getToken();
}
```
## Testing

Not available for now. TODO: add tests.

## License

The MIT License (MIT). Please see [License File](https://github.com/therezor/oauth2-nbu-bankid/blob/master/LICENSE) for more information.