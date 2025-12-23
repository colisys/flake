<?php

namespace Flake\Persistent\Driver;

use Generator;

interface AbstractDriver
{
    public function __construct(array $options = []);
    public function connect(): void;
    public function disconnect(): void;
    public function isConnected(): bool;
    public function getErrorNo(): int;
    public function getError(): ?string;
    public function query(string $sql, array $bindings = []): ?Generator;
    public function execute(string $sql, array $bindings = []): bool;
    public function getEffectedRows(): int;
    public function getLastInsertId(): int;
    public function getLastSql(): string;
}
