<?php

namespace Flake\Persistent\Builder;

class SqliteBuilder implements AbstractBuilder
{
    protected string $table = '';
    protected array $wheres = [];
    protected array $columns = ['*'];
    protected array $joins = [];
    protected array $orders = [];
    protected array $groups = [];
    protected array $havings = [];
    protected array $bindings = [];
    protected array $sets = [];
    protected int $limit = 0;
    protected int $offset = 0;
    protected bool $distinct = false;
    protected array $unions = [];
    protected string $setType = 'select';

    public function table(string $tableName): static
    {
        $this->table = $tableName;
        return $this;
    }

    public function columns(array $columns): static
    {
        $this->columns = $columns;
        return $this;
    }

    public function where(string $column, $value, $operator = '=', $boolean = 'AND'): static
    {
        $this->wheres[] = [
            'operator' => $operator,
            'column' => $column,
            'boolean' => $boolean,
            'value' => $value,
        ];
        return $this;
    }

    public function whereOr(string $column, $value, $operator = '='): static
    {
        return $this->where($column, $value, $operator, 'OR');
    }

    public function whereBetween(string $column, array $values, $boolean = 'AND'): static
    {
        return $this->where($column, $values, 'BETWEEN', $boolean);
    }

    public function whereIn(string $column, array $values, string $boolean = 'AND'): static
    {
        return $this->where($column, $values, 'IN', $boolean);
    }

    public function whereNotIn(string $column, array $values, string $boolean = 'AND'): static
    {
        return $this->where($column, $values, 'NOT IN', $boolean);
    }

    public function whereNull(string $column, string $boolean = 'AND'): static
    {
        return $this->where($column, null, 'IS', $boolean);
    }

    public function whereNotNull(string $column, string $boolean = 'AND'): static
    {
        return $this->where($column, null, 'NOT', $boolean);
    }

    public function whereLike(string $column, string $value, string $boolean = 'AND'): static
    {
        return $this->where($column, $value, 'LIKE', $boolean);
    }

    public function whereNotLike(string $column, string $value, string $boolean = 'AND'): static
    {
        return $this->where($column, $value, 'NOT LIKE', $boolean);
    }

    public function whereRaw(string $raw): static
    {
        $this->wheres[] = [
            'type' => 'raw',
            'raw' => $raw,
            'boolean' => 'AND'
        ];
        return $this;
    }

    public function select(array|string $column = '*'): static
    {
        if (is_string($column)) {
            $this->columns = [$column];
        } else {
            $this->columns = $column;
        }
        return $this;
    }

    public function insert(array $data = []): static
    {
        $this->setType = 'insert';
        $this->sets = $data;
        return $this;
    }

    public function update(array $data = []): static
    {
        $this->setType = 'update';
        $this->sets = $data;
        return $this;
    }

    public function delete(): static
    {
        $this->setType = 'delete';
        return $this;
    }

    public function set(string $column, $value): static
    {
        $this->sets[$column] = $value;
        return $this;
    }

    public function limit(int $limit): static
    {
        $this->limit = $limit;
        return $this;
    }

    public function offset(int $offset): static
    {
        $this->offset = $offset;
        return $this;
    }

    public function orderBy(string $column, string $direction = 'ASC'): static
    {
        $this->orders[] = [
            'column' => $column,
            'direction' => strtoupper($direction)
        ];
        return $this;
    }

    public function orderByDesc(string $column): static
    {
        return $this->orderBy($column, 'DESC');
    }

    public function group(string $column): static
    {
        $this->groups[] = $column;
        return $this;
    }

    public function having(string $column, $value): static
    {
        $this->havings[] = [
            'column' => $column,
            'value' => $value,
            'operator' => '='
        ];
        return $this;
    }

    public function join(string $table, string $column1, string $column2): static
    {
        $this->joins[] = [
            'type' => 'INNER',
            'table' => $table,
            'column1' => $column1,
            'column2' => $column2
        ];
        return $this;
    }

    public function leftJoin(string $table, string $column1, string $column2): static
    {
        $this->joins[] = [
            'type' => 'LEFT',
            'table' => $table,
            'column1' => $column1,
            'column2' => $column2
        ];
        return $this;
    }

    public function rightJoin(string $table, string $column1, string $column2): static
    {
        $this->joins[] = [
            'type' => 'RIGHT',
            'table' => $table,
            'column1' => $column1,
            'column2' => $column2
        ];
        return $this;
    }

    public function innerJoin(string $table, string $column1, string $column2): static
    {
        $this->joins[] = [
            'type' => 'INNER',
            'table' => $table,
            'column1' => $column1,
            'column2' => $column2
        ];
        return $this;
    }

    public function fullJoin(string $table, string $column1, string $column2): static
    {
        $this->joins[] = [
            'type' => 'FULL',
            'table' => $table,
            'column1' => $column1,
            'column2' => $column2
        ];
        return $this;
    }

    public function union(string $table): static
    {
        $this->unions[] = $table;
        return $this;
    }

    public function distinct(): static
    {
        $this->distinct = true;
        return $this;
    }

    public function build(): array
    {
        $query = '';
        $bindings = [];

        if (!empty($this->sets) && isset($this->setType) && $this->setType === 'insert') {
            // INSERT query
            $query = "INSERT INTO {$this->table}";
            $columns = implode(', ', array_keys($this->sets));
            $placeholders = implode(', ', array_fill(0, count($this->sets), '?'));
            $query .= " ({$columns}) VALUES ({$placeholders})";
            $bindings = array_values($this->sets);
        } elseif (!empty($this->sets) && isset($this->setType) && $this->setType === 'update') {
            // UPDATE query
            $query = "UPDATE {$this->table} SET ";
            $setParts = [];
            foreach ($this->sets as $column => $value) {
                $setParts[] = "{$column} = ?";
                $bindings[] = $value;
            }
            $query .= implode(', ', $setParts);

            if (!empty($this->wheres)) {
                $whereClause = $this->buildWhereClause($bindings);
                $query .= " WHERE " . $whereClause['clause'];
                $bindings = array_merge($bindings, $whereClause['bindings']);
            }
        } elseif (isset($this->setType) && $this->setType === 'delete') {
            // DELETE query
            $query = "DELETE FROM {$this->table}";

            if (!empty($this->wheres)) {
                $whereClause = $this->buildWhereClause($bindings);
                $query .= " WHERE " . $whereClause['clause'];
                $bindings = array_merge($bindings, $whereClause['bindings']);
            }
        } else {
            // SELECT query
            $columns = $this->distinct ? 'DISTINCT ' . implode(', ', $this->columns) : implode(', ', $this->columns);
            $query = "SELECT {$columns} FROM {$this->table}";

            // Joins
            foreach ($this->joins as $join) {
                $query .= " {$join['type']} JOIN {$join['table']} ON {$join['column1']} = {$join['column2']}";
            }

            // Where conditions
            if (!empty($this->wheres)) {
                $whereClause = $this->buildWhereClause($bindings);
                $query .= " WHERE " . $whereClause['clause'];
                $bindings = array_merge($bindings, $whereClause['bindings']);
            }

            // Group by
            if (!empty($this->groups)) {
                $query .= " GROUP BY " . implode(', ', $this->groups);
            }

            // Having
            if (!empty($this->havings)) {
                $havingParts = [];
                foreach ($this->havings as $having) {
                    $havingParts[] = "{$having['column']} {$having['operator']} ?";
                    $bindings[] = $having['value'];
                }
                $query .= " HAVING " . implode(' AND ', $havingParts);
            }

            // Order by
            if (!empty($this->orders)) {
                $orderParts = [];
                foreach ($this->orders as $order) {
                    $orderParts[] = "{$order['column']} {$order['direction']}";
                }
                $query .= " ORDER BY " . implode(', ', $orderParts);
            }

            // Limit and offset
            if ($this->limit > 0) {
                $query .= " LIMIT {$this->limit}";
                if ($this->offset > 0) {
                    $query .= " OFFSET {$this->offset}";
                }
            }
        }

        $this->reset();
        return [$query, $bindings];
    }

    private function buildWhereClause(array &$bindings): array
    {
        $whereParts = [];
        $whereBindings = [];

        foreach ($this->wheres as $index => $where) {
            if (isset($where['type']) && $where['type'] === 'raw') {
                $whereParts[] = ($index === 0 ? '' : $where['boolean'] . ' ') . $where['raw'];
            } else {
                $operator = $where['operator'];
                $boolean = $index === 0 ? '' : $where['boolean'] . ' ';

                if ($operator === 'BETWEEN') {
                    $whereParts[] = "{$boolean}{$where['column']} BETWEEN ? AND ?";
                    $whereBindings[] = $where['value'][0];
                    $whereBindings[] = $where['value'][1];
                } elseif ($operator === 'IN' || $operator === 'NOT IN') {
                    $placeholders = str_repeat('?,', count($where['value']) - 1) . '?';
                    $whereParts[] = "{$boolean}{$where['column']} {$operator} ({$placeholders})";
                    $whereBindings = array_merge($whereBindings, $where['value']);
                } elseif ($operator === 'IS' || $operator === 'NOT') {
                    $whereParts[] = "{$boolean}{$where['column']} {$operator} NULL";
                } else {
                    $whereParts[] = "{$boolean}{$where['column']} {$operator} ?";
                    $whereBindings[] = $where['value'];
                }
            }
        }

        return [
            'clause' => implode(' ', $whereParts),
            'bindings' => $whereBindings
        ];
    }

    public function reset(): static
    {
        $this->table = '';
        $this->wheres = [];
        $this->columns = ['*'];
        $this->joins = [];
        $this->orders = [];
        $this->groups = [];
        $this->havings = [];
        $this->bindings = [];
        $this->sets = [];
        $this->limit = 0;
        $this->offset = 0;
        $this->distinct = false;
        $this->unions = [];
        unset($this->setType);

        return $this;
    }
}
