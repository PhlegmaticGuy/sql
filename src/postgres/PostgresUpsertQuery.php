<?php

namespace mindplay\sql\postgres;

use mindplay\sql\framework\MapperProvider;
use mindplay\sql\model\components\Returning;
use mindplay\sql\model\components\ReturnVars;
use mindplay\sql\model\Driver;
use mindplay\sql\model\schema\Column;
use mindplay\sql\model\schema\Table;
use mindplay\sql\model\TypeProvider;

class PostgresUpsertQuery extends PostgresInsertQuery implements MapperProvider
{
    use Returning;

    /**
     * @var string
     */
    protected $conflict_expr = null;

    /**
     * @var Column[]
     */
    protected $update_columns = null;

    /**
     * @param Driver       $driver
     * @param TypeProvider $types
     * @param Table        $table
     */
    public function __construct(Driver $driver, TypeProvider $types, Table $table)
    {
        parent::__construct($driver, $types, $table);

        $this->return_vars = new ReturnVars($table, $driver, $types);
    }

    /**
     * @inheritdoc
     */
    public function getSQL()
    {
        $returning = $this->return_vars->hasReturnVars()
            ? "\nRETURNING " . $this->return_vars->buildReturnVars()
            : "";

        $on_conflict = "\nON CONFLICT {$this->conflict_expr}";

        $excluded_column_names = array_map(function(Column $column) {
            $quoted_name = $this->driver->quoteName($column->getName());
            return "{$quoted_name} = EXCLUDED.{$quoted_name}";
        }, $this->update_columns);

        $on_conflict .= "\nDO UPDATE SET " . implode(", ", $excluded_column_names);

        return parent::getSQL() . $on_conflict . $returning;
    }

    public function onConflict($cols)
    {
        $cols = is_array($cols) ? $cols : [$cols];
    }

    /**
     * @param string $expr
     *
     * @return $this
     */
    public function onConflictExpr($expr)
    {
        $this->conflict_expr = $expr;

        return $this;
    }

    /**
     * @param Column[] $columns
     *
     * @return $this
     */
    public function update(array $columns)
    {
        $this->update_columns = $columns;

        return $this;
    }
}
