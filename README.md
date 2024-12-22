# RGB Laravel Basecode Generator

## DB Support

- PostgreSQL
- MySQL

## Requirement

- Laravel 10/11
- PHP 8.2

## Install

```bash
composer require --dev jhonoryza/rgb-laravelwebbasecode-gen
```

## Start

1. create and run your migration

2. generate the module

```bash
php artisan make:cms Module
```

## How this works

- this package will read your migrated table column name and data type

- auto fill column in model, factory and form request file

### Generated Files

- Model
- Factory
- Seeder
- DatabaseSeeder
- Form Request
- Controller
- Service
- Blade Index, Edit and Create
- Route
- Menu
- Permission

### Limitation

- For every column will always use text component in index, create and edit
  blade files.

### Security

If you've found a bug regarding security please mail
[jardik.oryza@gmail.com](mailto:jardik.oryza@gmail.com) instead of using the
issue tracker.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more
information.
