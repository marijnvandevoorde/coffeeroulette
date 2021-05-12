<?php

namespace Marijnworks\Zoomroulette\Zoomroulette;

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class Spin
{
    private int $id;

    private UuidInterface $uuid;

    private int $openSpots;

    private string $joinLink;

    public function __construct(string $joinLink, int $openSpots)
    {
        $this->joinLink = $joinLink;
        $this->openSpots = $openSpots;
        $this->uuid = Uuid::uuid4();
    }

    /**
     * @param array{id:int, joinlink:string, openspots:int, uuid:string} $record
     */
    public static function withSqlRecord(array $record): Spin
    {
        $spin = new self($record['joinlink'], $record['openspots']);
        $spin->id = $record['id'];
        $spin->uuid = Uuid::fromString($record['uuid']);

        return $spin;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUuid(): UuidInterface
    {
        return $this->uuid;
    }

    public function getOpenSpots(): int
    {
        return $this->openSpots;
    }

    public function getJoinLink(): string
    {
        return $this->joinLink;
    }

    /**
     * @return Spin Cloned version with id set to $id
     */
    public function withId(int $id): Spin
    {
        $spin = new self($this->joinLink, $this->openSpots);
        $spin->id = $id;
        $spin->uuid = $this->uuid;

        return $spin;
    }
}
