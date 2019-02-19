<?php

namespace ThirtyThree\SocialiteProviders\Dingtalk;

use RuntimeException;
use ThirtyThree\Dingtalk\AccessToken;
use SocialiteProviders\Manager\OAuth2\User;
use Laravel\Socialite\Two\ProviderInterface;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;

class Provider extends AbstractProvider implements ProviderInterface
{
    /**
     * Unique Provider Identifier.
     */
    const IDENTIFIER = 'DINGTALK';

    /**
     * {@inheritdoc}.
     */
    protected $scopes = ['snsapi_login'];

    /**
     * {@inheritdoc}.
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase('https://oapi.dingtalk.com/connect/qrconnect', $state);
    }

    /**
     * {@inheritdoc}.
     */
    protected function getCodeFields($state = null)
    {
        return [
            'appid' => $this->clientId,
            'response_type' => 'code',
            'scope' => $this->formatScopes($this->scopes, $this->scopeSeparator),
            'state' => $state,
            'redirect_uri' => $this->redirectUrl,
        ];
    }

    /**
     * {@inheritdoc}.
     */
    protected function getTokenUrl()
    {
        return 'https://oapi.dingtalk.com/sns/get_persistent_code?access_token='.AccessToken::snsToken();
    }

    /**
     * {@inheritdoc}.
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get('https://oapi.dingtalk.com/sns/getuserinfo', [
            'query' => [
                'sns_token' => $this->getSnsToken(),
            ],
        ]);

        $json = json_decode($response->getBody(), true);

        if (array_get($json, 'errcode') !== 0) {
            throw new RuntimeException('Get dingtalk user_info error:'.array_get($json, 'errmsg', 'unknown'));
        }

        return $json['user_info'];
    }

    protected function getSnsToken()
    {
        $response = $this->getHttpClient()->post('https://oapi.dingtalk.com/sns/get_sns_token?access_token='.AccessToken::snsToken(), [
            'json' => [
                'openid' => $this->credentialsResponseBody['openid'],
                'persistent_code' => $this->credentialsResponseBody['persistent_code'],
            ],
        ]);

        $json = json_decode($response->getBody(), true);

        if (array_get($json, 'errcode') !== 0) {
            throw new RuntimeException('Get dingtalk sns_token error:'.array_get($json, 'errmsg', 'unknown'));
        }

        return $json['sns_token'];
    }

    /**
     * {@inheritdoc}.
     */
    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user)
            ->map([
                'id' => $user['dingId'],
                'nickname' => $user['nick'],
            ]);
    }

    /**
     * {@inheritdoc}.
     */
    protected function getTokenFields($code)
    {
        return [
            'appid' => $this->clientId,
            'secret' => $this->clientSecret,
            'tmp_auth_code' => $code,
            'grant_type' => 'authorization_code',
        ];
    }

    /**
     * {@inheritdoc}.
     */
    public function getAccessTokenResponse($code)
    {
        $response = $this->getHttpClient()->post($this->getTokenUrl(), [
            'json' => $this->getTokenFields($code),
        ]);

        $json = json_decode($response->getBody(), true);

        if (array_get($json, 'errcode') !== 0) {
            throw new RuntimeException('Get dingtalk access_token error:'.array_get($json, 'errmsg', 'unknown'));
        }
        $info = array_except($json, ['errcode', 'errmsg']);
        $info['access_token'] = $info['persistent_code'];

        $this->credentialsResponseBody = $info;

        return $this->credentialsResponseBody;
    }

    public static function additionalConfigKeys()
    {
        return [];
    }
}
