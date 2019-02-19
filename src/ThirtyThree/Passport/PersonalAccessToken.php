<?php

namespace ThirtyThree\Passport;

use Laravel\Passport\Passport;
use League\OAuth2\Server\CryptKey;
use Laravel\Passport\Bridge\Client;
use Laravel\Passport\Bridge\AccessToken;
use Laravel\Passport\PersonalAccessTokenFactory;

class PersonalAccessToken extends PersonalAccessTokenFactory
{
    public function generateAccessToken($token)
    {
        $accessToken = new AccessToken($token->user_id, $token->scopes);
        $accessToken->setIdentifier($token->id);
        $clientInfo = $token->client()->first();
        $client = new Client($clientInfo->id, $clientInfo->name, $clientInfo->redirect);
        $accessToken->setClient($client);
        $accessToken->setExpiryDateTime(new \DateTime($token->expires_at));

        return $accessToken->convertToJWT($this->makeCryptKey('oauth-private.key'));
    }

    protected function makeCryptKey($key)
    {
        return new CryptKey(
            'file://'.Passport::keyPath($key),
            null,
            false
        );
    }
}
