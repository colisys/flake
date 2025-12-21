<?php

namespace Flake\Persistent\Builder;

interface AbstractBuilder
{
    public function table(string $tableName): self;
    public function columns(array $columns): self;

    public function where(string $column, $value, string $operator = '=', string $boolean = 'AND'): self;
    public function whereOr(string $column, $value, string $operator = '='): self;
    public function whereBetween(string $column, array $values, string $boolean = 'AND'): self;
    public function whereIn(string $column, array $values, string $boolean = 'AND'): self;
    public function whereNotIn(string $column, array $values, string $boolean = 'AND'): self;
    public function whereNull(string $column, string $boolean = 'AND'): self;
    public function whereNotNull(string $column, string $boolean = 'AND'): self;
    public function whereLike(string $column, string $value, string $boolean = 'AND'): self;
    public function whereNotLike(string $column, string $value, string $boolean = 'AND'): self;
    public function whereRaw(string $raw): self;

    public function select(array|string $column = '*'): self;
    public function insert(array $data = []): self;
    public function update(array $data = []): self;
    public function delete(): self;

    public function set(string $column, $value): self;
    public function limit(int $limit): self;
    public function offset(int $offset): self;
    public function orderBy(string $column, string $direction = 'ASC'): self;
    public function group(string $column): self;
    public function having(string $column, $value): self;

    public function join(string $table, string $column1, string $column2): self;
    public function leftJoin(string $table, string $column1, string $column2): self;
    public function rightJoin(string $table, string $column1, string $column2): self;
    public function innerJoin(string $table, string $column1, string $column2): self;
    public function fullJoin(string $table, string $column1, string $column2): self;
    public function union(string $table): self;
    public function distinct(): self;

    public function build(): array;
    public function reset(): self;
}
