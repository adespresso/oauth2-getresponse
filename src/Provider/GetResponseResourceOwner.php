<?php

declare(strict_types=1);

namespace AdEspresso\OAuth2\Client\Provider;

use League\OAuth2\Client\Provider\ResourceOwnerInterface;

class GetResponseResourceOwner implements ResourceOwnerInterface
{
    /**
     * Raw response.
     *
     * @var array
     */
    protected $response;

    /**
     * @param array $response
     */
    public function __construct(array $response)
    {
        $this->response = $response;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->response['accountId'];
    }

    /**
     * @return string
     */
    public function getFirstName(): string
    {
        return $this->response['firstName'] ?? null;
    }

    /**
     * @return string
     */
    public function getLastName(): ?string
    {
        return $this->response['lastName'] ?? null;
    }

    public function toArray(): array
    {
        return $this->response;
    }
}
