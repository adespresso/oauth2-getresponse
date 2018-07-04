<?php

declare(strict_types=1);

namespace AdEspresso\OAuth2\Client\Tests;

use AdEspresso\OAuth2\Client\Provider\GetResponse;
use GuzzleHttp\ClientInterface;
use League\OAuth2\Client\Token\AccessToken;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

class GetResponseTest extends TestCase
{
    /**
     * @var GetResponse
     */
    protected $provider;

    protected function setUp()
    {
        $this->provider = new GetResponse([
            'clientId' => 'mock_client_id',
            'clientSecret' => 'mock_secret',
            'redirectUri' => 'mock_redirect_uri',
        ]);
    }

    public function testAuthorizationUrl()
    {
        $url = $this->provider->getAuthorizationUrl();
        $uri = parse_url($url);
        parse_str($uri['query'], $query);

        $this->assertArrayHasKey('client_id', $query);
        $this->assertArrayHasKey('redirect_uri', $query);
        $this->assertArrayHasKey('state', $query);
        $this->assertArrayHasKey('scope', $query);
        $this->assertArrayHasKey('response_type', $query);
        $this->assertNotNull($this->provider->getState());
    }

    public function testGetAuthorizationUrl()
    {
        $url = $this->provider->getAuthorizationUrl();
        $uri = parse_url($url);

        $this->assertEquals('app.getresponse.com', $uri['host']);
        $this->assertEquals('/oauth2_authorize.html', $uri['path']);
    }

    public function testGetBaseAccessTokenUrl()
    {
        $params = [];

        $url = $this->provider->getBaseAccessTokenUrl($params);
        $uri = parse_url($url);

        $this->assertEquals('api.getresponse.com', $uri['host']);
        $this->assertEquals('/v3/token', $uri['path']);
    }

    public function testGetResourceOwnerDetailsUrl()
    {
        $token = 'mock_token';
        $tokenMock = $this->getMockBuilder(AccessToken::class)
            ->disableOriginalConstructor()
            ->getMock();

        $tokenMock->method('getToken')->willReturn($token);

        $url = $this->provider->getResourceOwnerDetailsUrl($tokenMock);
        $uri = parse_url($url);

        $this->assertEquals('api.getresponse.com', $uri['host']);
        $this->assertEquals('/v3/accounts', $uri['path']);
    }

    public function testGetAccessToken()
    {
        $accessToken = $this->getJsonFile('access_token_response.json');
        $responseMock = $this->getMockBuilder(ResponseInterface::class)->getMock();
        $responseMock->method('getBody')->willReturn($accessToken);
        $responseMock->method('getHeader')->willReturn(['content-type' => 'json']);
        $responseMock->method('getStatusCode')->willReturn(200);

        $clientMock = $this->getMockBuilder(ClientInterface::class)->getMock();
        $clientMock->expects($this->once())->method('send')->willReturn($responseMock);
        $this->provider->setHttpClient($clientMock);

        $token = $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);

        $this->assertEquals('mock_access_token', $token->getToken());
        $this->assertEquals(time() + 21600, $token->getExpires());
        $this->assertEquals('mock_refresh_token', $token->getRefreshToken());
        $this->assertNull($token->getResourceOwnerId());
    }

    public function testUserData()
    {
        $accessToken = $this->getJsonFile('access_token_response.json');
        $accessTokenInfoJson = $this->getJsonFile('access_token_info.json');
        $accessTokenInfo = json_decode($accessTokenInfoJson, true);

        $postResponseMock = $this->getMockBuilder(ResponseInterface::class)->getMock();
        $postResponseMock->method('getBody')->willReturn($accessToken);
        $postResponseMock->method('getHeader')->willReturn(['content-type' => 'json']);
        $postResponseMock->method('getStatusCode')->willReturn(200);

        $accessTokenInfoResponseMock = $this->getMockBuilder(ResponseInterface::class)->getMock();
        $accessTokenInfoResponseMock->method('getBody')->willReturn($accessTokenInfoJson);
        $accessTokenInfoResponseMock->method('getHeader')->willReturn(['content-type' => 'json']);
        $accessTokenInfoResponseMock->method('getStatusCode')->willReturn(200);

        $clientMock = $this->getMockBuilder(ClientInterface::class)->getMock();
        $clientMock->expects($this->exactly(2))
            ->method('send')
            ->willReturnOnConsecutiveCalls($postResponseMock, $accessTokenInfoResponseMock);

        $this->provider->setHttpClient($clientMock);

        $token = $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);
        $user = $this->provider->getResourceOwner($token);

        $this->assertEquals($accessTokenInfo, $user->toArray());
        $this->assertEquals($accessTokenInfo['firstName'], $user->getFirstName());
        $this->assertEquals($accessTokenInfo['lastName'], $user->getLastName());
    }

    /**
     * @expectedException \League\OAuth2\Client\Provider\Exception\IdentityProviderException
     */
    public function testExceptionThrownWhenErrorReceived()
    {
        $status = rand(401, 599);
        $postResponseMock = $this->getMockBuilder(ResponseInterface::class)->getMock();
        $postResponseMock->method('getBody')->willReturn('{"error": "error_code","error_description": "A human readable error message"}');
        $postResponseMock->method('getHeader')->willReturn(['content-type' => 'json']);
        $postResponseMock->method('getStatusCode')->willReturn($status);

        $clientMock = $this->getMockBuilder(ClientInterface::class)->getMock();
        $clientMock->expects($this->once())->method('send')->willReturn($postResponseMock);

        $this->provider->setHttpClient($clientMock);

        $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);
    }

    protected function getJsonFile($file, $encode = false)
    {
        $json = file_get_contents(__DIR__.'/../'.$file);
        $data = json_decode($json, true);

        if ($encode && JSON_ERROR_NONE == json_last_error()) {
            return $data;
        }

        return $json;
    }
}
