<?php

namespace Flake\Persistent\Driver;

use Generator;
use SQLite3;

class SqliteDriver implements AbstractDriver
{
    private ?SQLite3 $connection;
    private string $lastSql = '';

    /**
     * @param array{"database":string} $options
     */
    public function __construct(private array $options = []) {}

    public function connect(): void
    {
        if (!$this->isConnected())
            $this->connection = new SQLite3($this->options['database']);
    }

    public function disconnect(): void
    {
        $this->connection?->close();
    }

    public function isConnected(): bool
    {
        return isset($this->connection);
    }

    public function getErrorNo(): int
    {
        return $this->connection?->lastErrorCode() ?? SQLite3::OK;
    }

    public function getError(): ?string
    {
        return $this->connection?->lastErrorMsg();
    }

    public function query(string $sql, array $bindings = []): Generator
    {
        $this->connect();
        $stmt = $this->connection?->prepare($sql);
        foreach ($bindings as $key => $value) {
            $stmt->bindValue($key + 1, $value);
        }

        $this->lastSql = $stmt->getSQL(true);

        if ($result = $stmt->execute()) {
            $result->reset();

            while ($row = $result->fetchArray()) {
                yield $row;
            }
        }
    }

    public function execute(string $sql, array $bindings = []): bool
    {
        $this->connect();
        $stmt = $this->connection?->prepare($sql);
        foreach ($bindings as $key => $value) {
            $stmt->bindValue($key + 1, $value);
        }
        $this->lastSql = $stmt->getSQL(true);
        return $stmt->execute() !== false;
    }

    public function getEffectedRows(): int
    {
        return $this->connection?->changes();
    }

    public function getLastInsertId(): int
    {
        return $this->connection?->lastInsertRowID() ?? 0;
    }

    public function getLastSql(): string
    {
        return $this->lastSql;
    }
}
