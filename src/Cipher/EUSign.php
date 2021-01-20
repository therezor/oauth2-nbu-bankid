<?php

namespace TheRezor\OAuth2\Client\Cipher;

use TheRezor\OAuth2\Client\Provider\Exception\EUSignException;
use stdClass;

class EUSign implements CipherInterface
{
    public const ENCODING_UTF8 = 65001;

    protected $key;

    protected $password;

    public function __construct(string $key, string $password)
    {
        $this->key = $key;
        $this->password = $password;
    }

    public function decode(string $data, string $cert): string
    {
        $context = null;
        $keyContext = null;
        $errorCode = null;
        $envelop = new stdClass();
        $signInfo = new stdClass();

        euspe_setcharset(self::ENCODING_UTF8);
        $this->verifyResult(euspe_init($errorCode), $errorCode);
        $this->verifyResult(euspe_ctxcreate($context, $errorCode), $errorCode);
        $this->verifyResult(
            euspe_ctxreadprivatekeybinary($context, $this->key, $this->password, $keyContext, $errorCode),
            $errorCode
        );
        $this->verifyResult(
            euspe_ctxdevelopdata(
                $keyContext,
                base64_decode($data),
                base64_decode($cert),
                $envelop->data,
                $envelop->signTime,
                $envelop->useTSP,
                $envelop->issuer,
                $envelop->issuerCN,
                $envelop->serial,
                $envelop->subject,
                $envelop->subjCN,
                $envelop->subjOrg,
                $envelop->subjOrgUnit,
                $envelop->subjTitle,
                $envelop->subjState,
                $envelop->subjLocality,
                $envelop->subjFullName,
                $envelop->subjAddress,
                $envelop->subjPhone,
                $envelop->subjEMail,
                $envelop->subjDNS,
                $envelop->subjEDRPOUCode,
                $envelop->subjDRFOCode,
                $errorCode
            ),
            $errorCode
        );
        $this->verifyResult(
            euspe_signverify(
                $envelop->data,
                $signInfo->signTime,
                $signInfo->useTSP,
                $signInfo->issuer,
                $signInfo->issuerCN,
                $signInfo->serial,
                $signInfo->subject,
                $signInfo->subjCN,
                $signInfo->subjOrg,
                $signInfo->subjOrgUnit,
                $signInfo->subjTitle,
                $signInfo->subjState,
                $signInfo->subjLocality,
                $signInfo->subjFullName,
                $signInfo->subjAddress,
                $signInfo->subjPhone,
                $signInfo->subjEMail,
                $signInfo->subjDNS,
                $signInfo->subjEDRPOUCode,
                $signInfo->subjDRFOCode,
                $signInfo->data,
                $errorCode
            ),
            $errorCode
        );

        euspe_ctxfreeprivatekey($keyContext);
        euspe_ctxfree($context);
        euspe_finalize();

        return $signInfo->data;
    }

    protected function verifyResult($result, $errorCode)
    {
        switch ($result) {
            case 1:
                throw new EUSignException('Decode error: '.$errorCode, $errorCode);

            case 2:
                throw new EUSignException('Decode error: Wrong params');

            case 3:
                throw new EUSignException('Decode error: Cipher initialization error');
        }

        return true;
    }
}