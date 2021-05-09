<?php

namespace Marijnworks\Zoomroulette\Zoomroulette;

use Doctrine\DBAL\Connection;

class UserRepository
{
    const TABLE_NAME = 'users';

    private Connection $connection;

    private EncryptionToolkit $encryptionToolkit;

    public function __construct(Connection $connection, EncryptionToolkit $encryptionToolkit)
    {
        $this->connection = $connection;
        $this->encryptionToolkit = $encryptionToolkit;
    }

    public function add(User $user): User
    {
        $this->connection->insert(
            self::TABLE_NAME,
            [
                'sso_platform' => $user->getSsoPlatform(),
                'sso_userid' => $user->getSsoUserId(),
                'sso_credentials' => $this->encryptionToolkit->encrypt(json_encode($user->getSsoAccessToken())),
                'zoom_userid' => $user->getZoomUserid(),
                'zoom_credentials' => $this->encryptionToolkit->encrypt(json_encode($user->getZoomAccessToken())),
            ]
        );
        $user->setId((int) $this->connection->lastInsertId());

        return $user;
    }

    public function update(User $user): void
    {
        $this->connection->update(
            self::TABLE_NAME,
            [
                'sso_platform' => $user->getSsoPlatform(),
                'sso_userid' => $user->getSsoUserId(),
                'sso_credentials' => $this->encryptionToolkit->encrypt(json_encode($user->getSsoAccessToken())),
                'zoom_userid' => $user->getZoomUserId(),
                'zoom_credentials' => $this->encryptionToolkit->encrypt(json_encode($user->getZoomAccessToken())),
            ],
            [
                'id' => $user->getId(),
            ]
        );
    }

    public function findById(int $id): User
    {
        $query = $this->connection->createQueryBuilder();
        $result = $query->select('*')
            ->from(self::TABLE_NAME)
            ->where('id = :id')
            ->setParameter('id', $id)
            ->execute();

        $row = $result->fetch();
        if (!$row) {
            throw new UserNotFoundException('No user by this id');
        }
        $row['zoom_credentials'] = $this->encryptionToolkit->decrpyt($row['zoom_credentials']);
        $row['sso_credentials'] = $this->encryptionToolkit->decrpyt($row['sso_credentials']);

        return User::withSqlRecord($row);
    }

    public function findBySsoId(string $ssoPlatform, string $ssoUserId): User
    {
        $query = $this->connection->createQueryBuilder();
        $result = $query->select('*')
            ->from(self::TABLE_NAME)
            ->where('sso_platform = :sso_platform')
            ->where('sso_userid = :sso_userid')
            ->setParameter('sso_platform', $ssoPlatform)
            ->setParameter('sso_userid', $ssoUserId)
            ->execute();

        $row = $result->fetch();
        if (!$row) {
            throw new UserNotFoundException('No user by this id');
        }

        $row['zoom_credentials'] = $this->encryptionToolkit->decrpyt($row['zoom_credentials']);
        $row['sso_credentials'] = $this->encryptionToolkit->decrpyt($row['sso_credentials']);

        return User::withSqlRecord($row);
    }
}
