<?php

namespace Jhonoryza\Rgb\BasecodeGen\Console\Commands\Concerns;

use ErrorException;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Schema\Grammars\MySqlGrammar;
use Illuminate\Database\Schema\Grammars\PostgresGrammar;
use Illuminate\Database\Schema\MySqlBuilder;
use Illuminate\Database\Schema\PostgresBuilder;
use Illuminate\Support\Collection;

trait ColumnTrait
{
    /**
     * @throws ErrorException
     */
    protected function getColumnList(): Collection
    {
        $table = $this->getTableName();

        /**@var DatabaseManager $database */
        $database = app('db');

        $schemaBuilder = $database->getSchemaBuilder();

        if (!($schemaBuilder instanceof MySqlBuilder || $schemaBuilder instanceof PostgresBuilder)) {
            throw new ErrorException('Unsupported database / schema builder.');
        }

        /**@var PostgresGrammar $grammar */
        $grammar = $schemaBuilder instanceof PostgresBuilder ?
            new PostgresGrammar() : new MySqlGrammar();

        $sqlStatement = $grammar->compileColumns('public', $table);

        $results = $schemaBuilder->getConnection()->select($sqlStatement);

        $columnList =  $schemaBuilder->getConnection()->getPostProcessor()->processColumns($results);

        // $this->info(collect($columnList));
        return collect($columnList);
    }

    /**
     * @throws ErrorException
     */
    protected function getFillableColumnList(): Collection
    {
        $columns = [];
        $columnList = $this->getColumnList()->toArray();

        foreach ($columnList as $column) {
            if ($this->isFillable($column['name'] ?? null)) {
                $columns[] = $column;
            }
        }

        // $this->info(collect($columns));
        return collect($columns);
    }

    protected static array $notFillableColumns = [
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
