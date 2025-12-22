<?php

namespace Flake\Persistent\Builder;

interface AbstractBuilder
{
    /** 
     * Set the table name
     */
    public function table(string $tableName): static;
    /**
     * Set the columns to select
     */
    public function columns(array $columns): static;

    /**
     * Set the where clause
     * 
     * @param "AND"|"OR" $boolean
     */
    public function where(string $column, $value, string $operator = '=', string $boolean = 'AND'): static;
    /**
     * Set the where clause with OR
     * 
     * @param "AND"|"OR" $boolean
     */
    public function whereOr(string $column, $value, string $operator = '='): static;
    /**
     * Set the where clause with BETWEEN
     * 
     * @param "AND"|"OR" $boolean
     */
    public function whereBetween(string $column, array $values, string $boolean = 'AND'): static;
    /**
     * Set the where clause with IN
     * 
     * @param "AND"|"OR" $boolean
     */
    public function whereIn(string $column, array $values, string $boolean = 'AND'): static;
    /**
     * Set the where clause with NOT IN
     * 
     * @param "AND"|"OR" $boolean
     */
    public function whereNotIn(string $column, array $values, string $boolean = 'AND'): static;
    /**
     * Set the where clause with IS NULL
     * 
     * @param "AND"|"OR" $boolean
     */
    public function whereNull(string $column, string $boolean = 'AND'): static;
    /**
     * Set the where clause with IS NOT NULL
     * 
     * @param "AND"|"OR" $boolean
     */
    public function whereNotNull(string $column, string $boolean = 'AND'): static;
    /**
     * Set the where clause with LIKE
     * 
     * @param "AND"|"OR" $boolean
     */
    public function whereLike(string $column, string $value, string $boolean = 'AND'): static;
    /**
     * Set the where clause with NOT LIKE
     * 
     * @param "AND"|"OR" $boolean
     */
    public function whereNotLike(string $column, string $value, string $boolean = 'AND'): static;
    /**
     * Set the where clause with REGEXP
     */
    public function whereRaw(string $raw): static;

    /**
     * Prepare for select
     */
    public function select(array|string $column = '*'): static;
    /**
     * Prepare for insert
     */
    public function insert(array $data = []): static;
    /**
     * Prepare for update
     */
    public function update(array $data = []): static;
    /**
     * Prepare for delete
     */
    public function delete(): static;

    /**
     * Set the data to insert or update
     */
    public function set(string $column, $value): static;
    /**
     * Set the limit
     */
    public function limit(int $limit): static;
    /**
     * Set the offset
     */
    public function offset(int $offset): static;
    /**
     * Set the order by
     * 
     * @param "ASC"|"DESC" $direction
     */
    public function orderBy(string $column, string $direction = 'ASC'): static;
    /**
     * Set the order by desc
     */
    public function orderByDesc(string $column): static;
    /**
     * Set the group by
     */
    public function group(string $column): static;
    /**
     * Set the having clause
     */
    public function having(string $column, $value): static;

    /**
     * Set the join clause
     */
    public function join(string $table, string $column1, string $column2): static;
    /**
     * Set the left join clause
     */
    public function leftJoin(string $table, string $column1, string $column2): static;
    /**
     * Set the right join clause
     */
    public function rightJoin(string $table, string $column1, string $column2): static;
    /**
     * Set the inner join clause
     */
    public function innerJoin(string $table, string $column1, string $column2): static;
    /**
     * Set the full join clause
     */
    public function fullJoin(string $table, string $column1, string $column2): static;
    /**
     * Set the union clause
     */
    public function union(string $table): static;
    /**
     * Set the distinct clause
     */
    public function distinct(): static;

    /**
     * Build the query
     *
     * @return array{0:string,1:array}
     */
    public function build(): array;
    /**
     * Reset the query
     */
    public function reset(): static;
}
