<?php

namespace Jhonoryza\Rgb\BasecodeGen\Console\Commands\Concerns;

use Illuminate\Database\Schema\PostgresBuilder;

trait FactoryTrait
{
    private const pgsqlFakeData = [
        'text'          => 'fake()->text(200)',
        'varchar'       => 'fake()->text(50)',
        'char'          => 'fake()->randomLetter()',
        'boolean'       => 'fake()->boolean()',
        'integer'       => 'fake()->numberBetween(-2147483648, 2147483647)',
        'bigint'        => 'fake()->randomNumber(19, true)',
        'smallint'      => 'fake()->numberBetween(-32768, 32767)',
        'real'          => 'fake()->randomFloat(2, -1000, 1000)',
        'double'        => 'fake()->randomFloat(6, -1000000, 1000000)',
        'numeric'       => 'fake()->randomFloat(10, -1000, 1000)',
        'decimal'       => 'fake()->randomFloat(10, -1000, 1000)',
        'uuid'          => 'fake()->uuid()',
        'date'          => 'fake()->date(\'Y-m-d\')',
        'timestamp'     => 'fake()->dateTime()->format(\'Y-m-d H:i:s\')',
        'timestamptz'   => 'fake()->dateTime()->format(\'Y-m-d H:i:sO\')',
        'time'          => 'fake()->time(\'H:i:s\')',
        'interval'      => 'fake()->numberBetween(1, 100) . \' days\'',
        'json'          => 'json_encode([\'key\' => fake()->word()])',
        'jsonb'         => 'json_encode([\'key\' => fake()->word()])',
        'bytea'         => 'fake()->sha256()',
        'inet'          => 'fake()->ipv4()',
        'cidr'          => 'fake()->ipv4() . \'/24\'',
        'macaddr'       => 'fake()->macAddress()',
        'point'         => '\'(\' . fake()->randomFloat(2, -180, 180) . \',\' . fake()->randomFloat(2, -90, 90) . \')\'',
        'line'          => '\'[\' . fake()->randomFloat(2) . \',\' . fake()->randomFloat(2) . \']\'',
        'polygon'       => 'json_encode([[fake()->randomFloat(2), fake()->randomFloat(2)]])',
        'enum'          => 'fake()->randomElement([\'value1\', \'value2\'])',
    ];

    private const mysqlFakeData = [
        // String Types
        'char'            => 'fake()->randomLetter()', // Single character
        'varchar'         => 'fake()->text(50)',       // Text with max length (e.g., 50)
        'tinytext'        => 'fake()->text(255)',      // Up to 255 characters
        'text'            => 'fake()->text(65535)',    // Up to 65,535 characters
        'mediumtext'      => 'fake()->text(16777215)', // Up to 16,777,215 characters
        'longtext'        => 'fake()->paragraphs(10, true)', // Large text

        // Numeric Types
        'tinyint'         => 'fake()->numberBetween(-128, 127)', // 1 byte
        'smallint'        => 'fake()->numberBetween(-32768, 32767)', // 2 bytes
        'mediumint'       => 'fake()->numberBetween(-8388608, 8388607)', // 3 bytes
        'int'             => 'fake()->numberBetween(-2147483648, 2147483647)', // 4 bytes
        'bigint'          => 'fake()->randomNumber(19, true)', // 8 bytes
        'decimal'         => 'fake()->randomFloat(2, -9999, 9999)', // Adjustable precision
        'float'           => 'fake()->randomFloat(2, -1000, 1000)', // Approximate number
        'double'          => 'fake()->randomFloat(6, -1000000, 1000000)', // Higher precision

        // Date and Time Types
        'date'            => 'fake()->date(\'Y-m-d\')', // Format: YYYY-MM-DD
        'datetime'        => 'fake()->dateTime()->format(\'Y-m-d H:i:s\')', // Format: YYYY-MM-DD HH:MM:SS
        'timestamp'       => 'fake()->dateTime()->format(\'Y-m-d H:i:s\')', // Same as datetime
        'time'            => 'fake()->time(\'H:i:s\')', // Format: HH:MM:SS
        'year'            => 'fake()->year()', // Year format: YYYY

        // JSON and BLOB Types
        'json'            => 'json_encode([\'key\' => fake()->word()])', // JSON object
        'tinyblob'        => 'fake()->sha256()', // Small binary data
        'blob'            => 'fake()->sha256()', // Binary data
        'mediumblob'      => 'fake()->sha256()', // Larger binary data
        'longblob'        => 'fake()->sha256()', // Very large binary data

        // Enum and Set Types
        'enum'            => 'fake()->randomElement([\'value1\', \'value2\', \'value3\'])', // One of predefined values
        'set'             => 'implode(\',\', fake()->randomElements([\'value1\', \'value2\', \'value3\'], 2))', // Combination of values

        // Other Types
        'boolean'         => 'fake()->boolean()', // True or false
        'binary'          => 'fake()->sha256()', // Binary data
        'uuid'            => 'fake()->uuid()', // Universally unique identifier
        'inet'            => 'fake()->ipv4()', // IP address
    ];

    protected function getFakeData(string $key): string
    {
        if (app('db')->getSchemaBuilder() instanceof PostgresBuilder) {
            return self::pgsqlFakeData[$key];
        }
        return self::mysqlFakeData[$key];
    }
}
