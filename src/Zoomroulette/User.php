<?php

namespace Teamleader\Zoomroulette\Zoomroulette;

use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Token\AccessTokenInterface;

class User
{
    private int $id;

    private string $ssolUserId;

    private string $ssolPlatform;

    private AccessTokenInterface $accessToken;

    private ?string $zoomUserid = null;

    private ?AccessTokenInterface $zoomAccessToken = null;

    public function __construct(string $ssolPlatform, string $ssolUserId, AccessTokenInterface $accessToken)
    {
        $this->ssolUserId = $ssolUserId;
        $this->ssolPlatform = $ssolPlatform;
        $this->accessToken = $accessToken;
    }

    public static function withSqlRecord(array $record): self
    {
        $accessToken = json_decode($record['sso_credentials'], true);
        $user = new self($record['sso_platform'], $record['sso_userid'], new AccessToken($accessToken));
        $user->id = $record['id'];
        $user->zoomUserid = $record['zoom_userid'];
        $zoomToken = json_decode($record['zoom_credentials'], true);
        if ($zoomToken) {
            $user->zoomAccessToken = new AccessToken($zoomToken);
        }

        return $user;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getSsoUserId(): string
    {
        return $this->ssolUserId;
    }

    public function getSsoPlatform(): string
    {
        return $this->ssolPlatform;
    }

    public function getSsoAccessToken(): AccessTokenInterface
    {
        return $this->accessToken;
    }

    public function setSsoAccessToken(AccessTokenInterface $accessToken): void
    {
        $this->accessToken = $accessToken;
    }

    public function getZoomUserid(): ?string
    {
        return $this->zoomUserid;
    }

    public function setZoomUserid(string $zoomUserid): void
    {
        $this->zoomUserid = $zoomUserid;
    }

    public function getZoomAccessToken(): ?AccessTokenInterface
    {
        return $this->zoomAccessToken;
    }

    public function setZoomAccessToken(AccessTokenInterface $zoomAccessToken): void
    {
        $this->zoomAccessToken = $zoomAccessToken;
    }
}
