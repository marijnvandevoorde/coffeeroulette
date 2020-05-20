<?php

namespace Teamleader\Zoomroulette\Zoom;

use League\OAuth2\Client\Token\AccessToken;

class ZoomOauthStorage
{
    /**
     * @var string
     */
    private $path;

    public function __construct(string $path)
    {
        $this->path = realpath($path) . '/';
    }

    private static function sanitizeFilename(string $filename): string
    {
        return str_replace(['/', DIRECTORY_SEPARATOR, '.'], '', $filename);
    }

    public function save(string $resourceOwnerId, AccessToken $authenticationData): void
    {
        try {
            // Some added security
            $file = fopen($this->path . self::sanitizeFilename($resourceOwnerId) . '.json', 'w');
            fwrite($file, json_encode($authenticationData));
        } finally {
            fclose($file);
        }
    }

    /**
     * @throws UserNotFoundException
     */
    public function getTokenById(string $resourceOwnerId): AccessToken
    {
        $file = $this->path . self::sanitizeFilename($resourceOwnerId) . '.json';
        if (!file_exists($file)) {
            throw new UserNotFoundException();
        }
        $data = json_decode(file_get_contents($file), true);

        return new AccessToken($data);
    }
}
