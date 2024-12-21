<?php

namespace Jhonoryza\Rgb\BasecodeGen\Console\Commands\Concerns;

use ErrorException;
use Illuminate\Database\Schema\Grammars\MySqlGrammar;
use Illuminate\Database\Schema\Grammars\PostgresGrammar;
use Illuminate\Database\Schema\MySqlBuilder;
use Illuminate\Database\Schema\PostgresBuilder;
use Illuminate\Support\Collection;

trait ColumnTrait
{
    protected function getColumnList(): Collection
    {
        $table = $this->getTableName();

        /**@var \Illuminate\Database\DatabaseManager */
        $database = app('db');

        $schemaBuilder = $database->getSchemaBuilder();

        if (!($schemaBuilder instanceof MySqlBuilder || $schemaBuilder instanceof PostgresBuilder)) {
            throw new ErrorException('Unsupported database / schema builder.');
        }

        /**@var \Illuminate\Database\Schema\Grammars\PostgresGrammar */
        $grammar = $schemaBuilder instanceof PostgresBuilder ?
            new PostgresGrammar() : new MySqlGrammar();

        $sqlStatement = $grammar->compileColumns('public', $table);

        $results = $schemaBuilder->getConnection()->select($sqlStatement);

        $columnList =  $schemaBuilder->getConnection()->getPostProcessor()->processColumns($results);

        // $this->info(collect($columnList));
        return collect($columnList);
    }

    protected function getFillableColumnList(): Collection
    {
        $columns = [];
        $columnList = $this->getColumnList()->toArray();

        foreach ($columnList as $key => $column) {
            if ($this->isFillable($column['name'] ?? null)) {
                $columns[] = $columnList[$key] ?? null;
            }
        }

        // $this->info(collect($columns));
        return collect($columns);
    }

    protected static $notFillableColumns = [
        'id',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected function isFillable(string | null $columnName): bool
    {
        return !(
            in_array($columnName, self::$notFillableColumns, true) ||
            ($columnName === null)
        );
    }
}
