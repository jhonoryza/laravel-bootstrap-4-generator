<?php

namespace Jhonoryza\Bootstrap\Generator\Console\Commands\Concerns;

use Illuminate\Database\Schema\PostgresBuilder;

trait ValidationTrait
{
    private const validationPostgresRules = [
        // String Types
        'text'       => 'string',              // String panjang
        'varchar'    => 'string|max:255',       // String dengan batas panjang
        'char'       => 'string|size:1',        // Karakter tunggal
        'tinytext'   => 'string|max:255',       // Teks kecil
        'mediumtext' => 'string|max:16777215',  // Teks medium
        'longtext'   => 'string|max:16777215',  // Teks panjang

        // Numeric Types
        'tinyint'   => 'integer|between:-128,127',       // Angka 1 byte
        'smallint'  => 'integer|between:-32768,32767',   // Angka 2 byte
        'mediumint' => 'integer|between:-8388608,8388607', // Angka 3 byte
        'int'       => 'integer|between:-2147483648,2147483647', // Angka 4 byte
        'bigint'    => 'integer',               // Angka 8 byte (tidak ada batas dalam validasi integer Laravel)
        'decimal'   => 'numeric|between:-9999,9999',      // Angka desimal
        'float'     => 'numeric|between:-1000,1000',       // Angka pecahan
        'double'    => 'numeric|between:-1000000,1000000', // Angka presisi tinggi

        // Date and Time Types
        'date'      => 'date',                 // Format: YYYY-MM-DD
        'datetime'  => 'date_format:Y-m-d H:i:s', // Format: YYYY-MM-DD HH:MM:SS
        'timestamp' => 'date_format:Y-m-d H:i:s', // Format yang sama dengan datetime
        'time'      => 'date_format:H:i:s',    // Format: HH:MM:SS
        'year'      => 'integer|size:4',       // Tahun (YYYY)

        // JSON and BLOB Types
        'json'       => 'json',                 // Format JSON
        'jsonb'      => 'json',                 // Format JSON (sama dengan json)
        'tinyblob'   => 'nullable|mimes:jpeg,bmp,png',  // Binary data (hanya untuk file gambar)
        'blob'       => 'nullable|mimes:jpeg,bmp,png',  // Binary data
        'mediumblob' => 'nullable|mimes:jpeg,bmp,png',  // Binary data
        'longblob'   => 'nullable|mimes:jpeg,bmp,png',  // Binary data

        // Enum and Set Types
        'enum' => 'in:value1,value2,value3', // Salah satu dari nilai yang ada
        'set'  => 'array',                   // Kombinasi nilai (disesuaikan dengan kebutuhan)

        // Other Types
        'boolean' => 'boolean',               // Nilai boolean (true/false)
        'binary'  => 'nullable|mimes:jpeg,bmp,png',  // Data biner (bisa diterima sebagai gambar)
        'uuid'    => 'uuid',                  // UUID
        'inet'    => 'ip',                    // Alamat IP
    ];

    private const validationMysqlRules = [
        // String Types
        'char'       => 'string|size:1',           // Karakter tunggal
        'varchar'    => 'string|max:255',          // String dengan panjang maksimal
        'tinytext'   => 'string|max:255',          // Teks kecil
        'text'       => 'string|max:65535',        // Teks panjang
        'mediumtext' => 'string|max:16777215',     // Teks medium
        'longtext'   => 'string|max:16777215',     // Teks sangat panjang

        // Numeric Types
        'tinyint'   => 'integer|between:-128,127', // Angka 1 byte
        'smallint'  => 'integer|between:-32768,32767', // Angka 2 byte
        'mediumint' => 'integer|between:-8388608,8388607', // Angka 3 byte
        'int'       => 'integer|between:-2147483648,2147483647', // Angka 4 byte
        'bigint'    => 'integer',                 // Angka 8 byte
        'decimal'   => 'numeric|between:-9999,9999',  // Angka desimal
        'float'     => 'numeric|between:-1000,1000', // Angka pecahan
        'double'    => 'numeric|between:-1000000,1000000', // Angka presisi tinggi

        // Date and Time Types
        'date'      => 'date',                    // Format: YYYY-MM-DD
        'datetime'  => 'date_format:Y-m-d H:i:s',  // Format: YYYY-MM-DD HH:MM:SS
        'timestamp' => 'date_format:Y-m-d H:i:s',  // Format: YYYY-MM-DD HH:MM:SS
        'time'      => 'date_format:H:i:s',       // Format: HH:MM:SS
        'year'      => 'integer|size:4',          // Tahun (YYYY)

        // JSON and BLOB Types
        'json'       => 'json',                    // Format JSON
        'tinyblob'   => 'nullable|mimes:jpeg,bmp,png',  // Data biner kecil (misalnya gambar)
        'blob'       => 'nullable|mimes:jpeg,bmp,png',  // Data biner (misalnya gambar)
        'mediumblob' => 'nullable|mimes:jpeg,bmp,png',  // Data biner medium
        'longblob'   => 'nullable|mimes:jpeg,bmp,png',  // Data biner besar

        // Enum and Set Types
        'enum' => 'in:value1,value2,value3',  // Salah satu dari nilai yang ada
        'set'  => 'array',                    // Kombinasi nilai dalam set (disesuaikan dengan kebutuhan)

        // Other Types
        'boolean' => 'boolean',                  // Nilai boolean (true/false)
        'binary'  => 'nullable|mimes:jpeg,bmp,png', // Data biner (bisa diterima sebagai gambar)
        'uuid'    => 'uuid',                     // UUID
        'inet'    => 'ip',                       // Alamat IP (IPv4/IPv6)
    ];

    protected function getValidationRules(bool $isNullable, ...$keys): string
    {
        $cursor = self::validationMysqlRules;
        if (app('db')->getSchemaBuilder() instanceof PostgresBuilder) {
            $cursor = self::validationPostgresRules;
        }

        $isNullableRule = $isNullable ? 'nullable|' : 'required|';

        $temp = null;
        foreach ($keys as $key) {
            $temp = $cursor[$key] ?? null;
            if ($temp !== null) {
                break;
            }
        }

        return $isNullableRule . $temp;
    }
}
