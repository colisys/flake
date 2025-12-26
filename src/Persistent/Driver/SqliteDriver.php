<?php

namespace Flake\Persistent\Driver;

use Flake\Persistent\Event\DatabaseQueryEvent;
use Flake\Persistent\Exception\DatabaseException;
use Generator;
use Psr\EventDispatcher\EventDispatcherInterface;
use SQLite3;

use function Flake\dd;
use function Flake\make;

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

    public function query(string $sql, array $bindings = []): ?Generator
    {
        try {
            $this->connect();
            $stmt = $this->connection?->prepare($sql);
            foreach ($bindings as $key => $value) {
                $stmt->bindValue($key + 1, $value);
            }

            $this->lastSql = $stmt->getSQL(true);

            make(EventDispatcherInterface::class)?->dispatch(new DatabaseQueryEvent($this->lastSql));

            if ($result = $stmt->execute()) {
                $result->reset();

                while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                    yield $row;
                }
            }
        } catch (\Throwable $e) {
            dd(new DatabaseException($this->getError(), $this->getErrorNo(), $e));
        }
        return null;
    }

    public function fetchOne(string $sql, array $bindings = []): ?array
    {
        $result = $this->query($sql, $bindings);
        if ($result) {
            return $result->current();
        }
        return null;
    }

    public function fetchAll(string $sql, array $bindings = []): ?array
    {
        $result = $this->query($sql, $bindings);
        if ($result) {
            return iterator_to_array($result);
        }
        return null;
    }

    public function execute(string $sql, array $bindings = []): bool
    {
        try {
            $this->connect();
            $stmt = $this->connection?->prepare($sql);
            foreach ($bindings as $key => $value) {
                $stmt->bindValue($key + 1, $value);
            }
            $this->lastSql = $stmt->getSQL(true);

            make(EventDispatcherInterface::class)?->dispatch(new DatabaseQueryEvent($this->lastSql));

            return $stmt->execute() !== false;
        } catch (\Throwable $e) {
            dd(new DatabaseException($this->getError(), $this->getErrorNo(), $e));
        }
        return false;
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
