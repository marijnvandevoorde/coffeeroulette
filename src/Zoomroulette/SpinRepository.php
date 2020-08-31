<?php


namespace Teamleader\Zoomroulette\Zoomroulette;


use Doctrine\DBAL\Connection;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class SpinRepository
{

    const TABLE_NAME = 'spins';

    /**
     * @var Connection
     */
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function add(Spin $spin) : Spin {
        $this->connection->insert(
            self::TABLE_NAME,
            [
                'joinlink' => $spin->getJoinLink(),
                'openspots' => $spin->getOpenSpots(),
                'uuid' => $spin->getUuid()
            ]
        );
        return $spin->withId($this->connection->lastInsertId());

    }

    public function claimSpotByUuid(UuidInterface $uuid) {
        $this->connection->beginTransaction();
        try {
            $rowsUpdated = $this->connection->executeUpdate(
                'UPDATE spins
                        SET openspots = openspots -1
                        WHERE uuid = :uuid
                        AND openspots > 0',
                [
                    'TABLENAME' => self::TABLE_NAME,
                    'uuid' => $uuid->toString(),
                    'openspots' => '> 0',
                ]

            );

            if ($rowsUpdated < 1) {
                throw new SpinNotFoundException("No spin by id " . $uuid->toString());
            }
            // do stuff
            $spin = $this->findByUuid($uuid);
            $this->connection->commit();
            return $spin;
        } catch (\Exception $e) {
            $this->connection->rollBack();
            throw new SpinNotFoundException("No spin by id " . $uuid->toString());
        }

    }

    public function findById(int $id) : Spin {
        $query = $this->connection->createQueryBuilder();
        $result = $query->select('*')
            ->from(self::TABLE_NAME)
            ->where('id = :id')
            ->setParameter('id', $id)
            ->execute();

        $row = $result->fetch();
        if (!$row) {
            throw new SpinNotFoundException("No spin by id " . $uuid->toString());
        }

        return Spin::withSqlRecord($row);
    }

    public function findByUuid(UuidInterface $uuid) : Spin {
        $query = $this->connection->createQueryBuilder();
        $result = $query->select('*')
            ->from(self::TABLE_NAME)
            ->where('uuid = :uuid')
            ->setParameter('uuid', $uuid->toString())
            ->execute();

        $row = $result->fetch();
        if (!$row) {
            throw new SpinNotFoundException("No spin by id " . $uuid->toString());
        }

        return Spin::withSqlRecord($row);
    }

}