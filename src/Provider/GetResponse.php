<?php

declare(strict_types=1);

namespace AdEspresso\OAuth2\Client\Provider;

use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use Psr\Http\Message\ResponseInterface;

class GetResponse extends AbstractProvider
{
    protected $baseApiUrl = 'https://api.getresponse.com/v3';

    /**
     * Get authorization url to begin OAuth flow.
     *
     * @return string
     */
    public function getBaseAuthorizationUrl()
    {
        return 'https://app.getresponse.com/oauth2_authorize.html';
    }

    /**
     * Get access token url to retrieve token.
     *
     * @param array $params
     *
     * @return string
     */
    public function getBaseAccessTokenUrl(array $params): string
    {
        return $this->baseApiUrl.'/token';
    }

    public function getResourceOwnerDetailsUrl(AccessToken $token): string
    {
        return $this->baseApiUrl.'/accounts';
    }

    /**
     * Get the default scopes used by this provider.
     *
     * This provider doesn't specify default scopes. This is here to satisfy
     * the provider interface.
     *
     * @return array
     */
    protected function getDefaultScopes(): array
    {
        return [];
    }

    /**
     * @param ResponseInterface $response
     * @param array|string      $data
     *
     * @throws IdentityProviderException
     */
    protected function checkResponse(ResponseInterface $response, $data)
    {
        $statusCode = $response->getStatusCode();
        if ($statusCode >= 400) {
            throw new IdentityProviderException(
                $data['message'] ?? $response->getReasonPhrase(),
                $statusCode,
                $response
            );
        }
    }

    /**
     * Generate a minimal user object from a successful user details request.
     *
     * @param array       $response
     * @param AccessToken $token
     *
     * @return GetResponseResourceOwner
     */
    protected function createResourceOwner(array $response, AccessToken $token): GetResponseResourceOwner
    {
        return new GetResponseResourceOwner($response);
    }

    protected function getAuthorizationHeaders($token = null): array
    {
        if ($token instanceof AccessToken) {
            return array_merge(parent::getAuthorizationHeaders($token), [
                'Authorization' => 'Bearer '.$token->getToken(),
            ]);
        }

        return array_merge(parent::getAuthorizationHeaders($token), [
            'Authorization' => 'Basic '.base64_encode($this->clientId.':'.$this->clientSecret),
        ]);
    }

    protected function getAccessTokenBody(array $params): string
    {
        return parent::getAccessTokenBody(
            array_filter($params, function (string $key) {
                return !in_array($key, ['client_id', 'client_secret'], true);
            }, ARRAY_FILTER_USE_KEY)
        );
    }

    protected function getDefaultHeaders(): array
    {
        return [
            'Authorization' => 'Basic '.base64_encode($this->clientId.':'.$this->clientSecret),
        ];
    }
}
