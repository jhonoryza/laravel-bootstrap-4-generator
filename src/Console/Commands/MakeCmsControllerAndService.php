<?php

namespace Jhonoryza\Bootstrap\Generator\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Jhonoryza\Bootstrap\Generator\Console\Commands\Concerns\ColumnTrait;
use Jhonoryza\Bootstrap\Generator\Console\Commands\Concerns\FactoryTrait;
use Jhonoryza\Bootstrap\Generator\Console\Commands\Concerns\HelperTrait;
use Jhonoryza\Bootstrap\Generator\Console\Commands\Concerns\ReplaceKeywordsTrait;
use Jhonoryza\Bootstrap\Generator\Console\Commands\Concerns\ValidationTrait;

use function Laravel\Prompts\text;

class MakeCmsControllerAndService extends Command
{
    use ColumnTrait, FactoryTrait, HelperTrait, ReplaceKeywordsTrait, ValidationTrait;

    protected function getTableName(): string
    {
        return Str::snake(Str::plural($this->module));
    }

    protected $signature = 'make:cms {module? : module name}';

    protected $description = 'Generate a CMS files with predefined structure';

    protected ?string $module = null;

    public function handle(): void
    {
        $this->module = $this->argument('module');
        if ($this->module == null) {
            $this->module = text(
                label: 'module name',
                placeholder: 'Category',
                required: true
            );
        }
        $this->module = Str::camel(Str::singular($this->module));

        // $this->generateMigration();
        try {
            $this->generateModel();
            $this->generateFactory();
            $this->generateSeeder();
            $this->updateDatabaseSeeder();
            $this->generateService();
            $this->generateRequest();
            $this->generateController();
            $this->generateIndexBlade();
            $this->generateCreateBlade();
            $this->generateEditBlade();
            $this->updateRoute();
            $this->updateMenu();
            $this->updatePermission();
        } catch (Exception $exception) {
            $this->error($exception->getMessage());
        }
    }

    //    private function generateMigration(): void
    //    {
    //        $tableName = $this->getSnakeDummies();
    //
    //        $migrationPath = database_path("migrations/" . date('Y_m_d_His') . "_create_{$tableName}_table.php");
    //        $migrationStub = $this->getStubPath('cms-migration.stub');
    //        $this->generateFile($migrationStub, $migrationPath, [
    //            '{{ tableName }}' => $tableName,
    //        ]);
    //        $this->info("Migration for table {$tableName} created successfully.");
    //    }

    /**
     * @throws Exception
     */
    private function generateModel(): void
    {
        $modelName = $this->getStudlyDummy();
        $namespace = "App\Models";

        $stubPath = $this->getStubPath('cms-model.stub');
        $filePath = app_path("Models/$modelName.php");

        $replacements = [
            '{{ namespace }}' => $namespace,
            '{{ modelName }}' => $modelName,
            '{{ fillable }}'  => $this->getFillableColumnList()
                ->map(fn ($item) => "'" . $item['name'] . "'")
                ->implode(",\n\t\t"),
        ];

        $this->generateFile($stubPath, $filePath, $replacements);

        $this->info("Model $modelName created successfully!");
    }

    /**
     * @throws Exception
     */
    private function generateFactory(): void
    {
        $modelName   = $this->getStudlyDummy();
        $factoryName = "{$modelName}Factory";

        $factoryPath = database_path("factories/$factoryName.php");
        $factoryStub = $this->getStubPath('cms-factory.stub');

        $replacements = [
            '{{ modelName }}'       => $modelName,
            '{{ factoryName }}'     => $factoryName,
            '{{ fillableFactory }}' => $this->getFillableColumnList()
                ->map(fn ($item) => "'{$item['name']}' => " . $this->getFakeData($item['type'], $item['type_name']))
                ->implode(",\n\t\t\t"),
        ];

        $this->generateFile($factoryStub, $factoryPath, $replacements);

        $this->info("Factory $factoryName created successfully.");
    }

    /**
     * @throws Exception
     */
    private function generateSeeder(): void
    {
        $modelName  = $this->getStudlyDummy();
        $seederName = "{$modelName}Seeder";

        $seederPath = database_path("seeders/$seederName.php");
        $seederStub = $this->getStubPath('cms-seeder.stub');

        $replacements = [
            '{{ modelName }}'  => $modelName,
            '{{ seederName }}' => $seederName,
        ];

        $this->generateFile($seederStub, $seederPath, $replacements);

        $this->info("Seeder $seederName created successfully.");
    }

    /**
     * @throws Exception
     */
    private function updateDatabaseSeeder(): void
    {
        $modelName  = $this->getStudlyDummy();
        $seederName = "{$modelName}Seeder";

        $dbSeederStub = $this->getStubPath('cms-db-seeder.stub');
        $dbSeederPath = database_path('seeders/DatabaseSeeder.php');
        $replacements = [
            '{{ className }}' => $seederName,
        ];

        $template    = $this->replaceContent($dbSeederStub, $replacements);
        $fileContent = File::get($dbSeederPath);

        $isExists = preg_match($this->generatePattern($template), $fileContent);
        if (! $isExists) {
            $fileContent = preg_replace('/\$this->call\(AdminsTableSeeder::class\);/', "\$this->call(AdminsTableSeeder::class);\n\t\t$template", $fileContent, 1);
            File::put($dbSeederPath, $fileContent);
        }

        $this->info('DatabaseSeeder updated successfully.');
    }

    /**
     * @throws Exception
     */
    private function generateRequest(): void
    {
        $modelName = $this->getStudlyDummy();
        $className = $modelName . 'StoreRequest';
        $namespace = "App\Http\Requests\CMS\\$modelName";

        $stubPath = $this->getStubPath('cms-request.stub');
        $filePath = app_path("Http/Requests/CMS/$modelName/$className.php");

        $replacements = [
            '{{ namespace }}'     => $namespace,
            '{{ className }}'     => $className,
            '{{ fillableRules }}' => $this->getFillableColumnList()
                ->map(fn ($item) => "'{$item['name']}' => '{$this->getValidationRules($item['nullable'], $item['type'], $item['type_name'])}'")
                ->implode(",\n\t\t\t"),
        ];

        $this->generateFile($stubPath, $filePath, $replacements);

        $this->info("Request $className created successfully!");
    }

    /**
     * @throws Exception
     */
    private function generateService(): void
    {
        $modelName        = $this->getStudlyDummy();
        $className        = $modelName . 'Service';
        $storeRequestName = $modelName . 'StoreRequest';

        $namespace        = "App\Http\Services\CMS";
        $modelNamespace   = "App\Models\\$modelName";
        $requestNamespace = "App\Http\Requests\CMS\\$modelName\\$storeRequestName";

        $stubPath = $this->getStubPath('cms-service.stub');
        $filePath = app_path("Http/Services/CMS/$className.php");

        $replacements = [
            '{{ namespace }}'        => $namespace,
            '{{ modelNamespace }}'   => $modelNamespace,
            '{{ requestNamespace }}' => $requestNamespace,
            '{{ className }}'        => $className,
            '{{ modelName }}'        => $modelName,
            '{{ storeRequestName }}' => $storeRequestName,
            '{{ camelDummy }}'       => $this->getCamelDummy(),
        ];

        $this->generateFile($stubPath, $filePath, $replacements);

        $this->info("Service $className created successfully!");
    }

    /**
     * @throws Exception
     */
    private function generateController(): void
    {
        $modelName        = $this->getStudlyDummy();
        $className        = $modelName . 'Controller';
        $serviceName      = $modelName . 'Service';
        $storeRequestName = $modelName . 'StoreRequest';

        $namespace        = "App\Http\Controllers\CMS";
        $modelNamespace   = "App\Models\\$modelName";
        $serviceNamespace = "App\Http\Services\CMS\\$serviceName";
        $requestNamespace = "App\Http\Requests\CMS\\$modelName\\$storeRequestName";

        $stubPath = $this->getStubPath('cms-controller.stub');
        $filePath = app_path("Http/Controllers/CMS/$className.php");

        $replacements = [
            '{{ namespace }}'        => $namespace,
            '{{ modelNamespace }}'   => $modelNamespace,
            '{{ requestNamespace }}' => $requestNamespace,
            '{{ serviceNamespace }}' => $serviceNamespace,
            '{{ className }}'        => $className,
            '{{ modelName }}'        => $modelName,
            '{{ serviceName }}'      => $serviceName,
            '{{ storeRequestName }}' => $storeRequestName,
            '{{ camelServiceName }}' => Str::camel($serviceName),
            '{{ kebabDummy }}'       => $this->getKebabDummy(),
            '{{ camelDummies }}'     => $this->getCamelDummies(),
            '{{ camelDummy }}'       => $this->getCamelDummy(),
        ];

        $this->generateFile($stubPath, $filePath, $replacements);

        $this->info("Controller $className created successfully!");
    }

    /**
     * @throws Exception
     */
    private function generateIndexBlade(): void
    {
        $className = $this->getStudlyDummy();
        $fileName  = $this->getKebabDummy() . '/index.blade.php';
        $namespace = 'views/cms/pages';

        $stubPath = $this->getStubPath('cms-index.blade.stub');
        $filePath = resource_path("$namespace/$fileName");

        $colHeadTemplate = '<th class="js-sortable" data-sort-by="%s">%s</th>';
        $colBodyTemplate = '<td>{{ $%s->%s }}</td>';

        $replacements = [
            '{{ className }}'    => $className,
            '{{ kebabDummy }}'   => $this->getKebabDummy(),
            '{{ camelDummy }}'   => $this->getCamelDummy(),
            '{{ camelDummies }}' => $this->getCamelDummies(),
            '{{ columnHead }}'   => $this->getFillableColumnList()
                ->map(fn ($item) => sprintf($colHeadTemplate, $item['name'], Str::title($item['name'])))
                ->implode("\n\t\t\t\t"),
            '{{ columnBody }}' => $this->getFillableColumnList()
                ->map(fn ($item) => sprintf($colBodyTemplate, $this->getCamelDummy(), $item['name']))
                ->implode("\n\t\t\t\t"),
        ];

        $this->generateFile($stubPath, $filePath, $replacements);

        $this->info("Blade file $fileName created successfully!");
    }

    /**
     * @throws Exception
     */
    private function generateCreateBlade(): void
    {
        $className = $this->getStudlyDummy();
        $fileName  = $this->getKebabDummy() . '/create.blade.php';
        $namespace = 'views/cms/pages';

        $stubPath = $this->getStubPath('cms-create.blade.stub');
        $filePath = resource_path("$namespace/$fileName");

        $template = '<x-cms::input.text name="%s" label="%s" :required="true" />';

        $replacements = [
            '{{ className }}'    => $className,
            '{{ kebabDummy }}'   => $this->getKebabDummy(),
            '{{ columnFields }}' => $this->getFillableColumnList()
                ->map(fn ($item) => sprintf($template, $item['name'], Str::title($item['name'])))
                ->implode("\n\t\t\t\t"),
        ];

        $this->generateFile($stubPath, $filePath, $replacements);

        $this->info("Blade file $fileName created successfully!");
    }

    /**
     * @throws Exception
     */
    private function generateEditBlade(): void
    {
        $className = $this->getStudlyDummy();
        $fileName  = $this->getKebabDummy() . '/edit.blade.php';
        $namespace = 'views/cms/pages';

        $stubPath = $this->getStubPath('cms-edit.blade.stub');
        $filePath = resource_path("$namespace/$fileName");

        $template = '<x-cms::input.text name="%s" value="{{ $%s->%s }}" label="%s" :required="true" />';

        $replacements = [
            '{{ className }}'    => $className,
            '{{ kebabDummy }}'   => $this->getKebabDummy(),
            '{{ camelDummy }}'   => $this->getCamelDummy(),
            '{{ columnFields }}' => $this->getFillableColumnList()
                ->map(fn ($item) => sprintf(
                    $template,
                    $item['name'],
                    $this->getCamelDummy(),
                    $item['name'],
                    Str::title($item['name'])
                ))
                ->implode("\n\t\t\t\t"),
        ];

        $this->generateFile($stubPath, $filePath, $replacements);

        $this->info("Blade file $fileName created successfully!");
    }

    /**
     * @throws Exception
     */
    private function updateRoute(): void
    {
        $className = $this->getStudlyDummy();

        $stubPath = $this->getStubPath('cms-route.stub');
        $filePath = base_path('routes/cms.php');

        $replacements = [
            '{{ className }}'  => $className,
            '{{ kebabDummy }}' => $this->getKebabDummy(),
        ];

        $this->appendFile($stubPath, $filePath, $replacements);

        $useStatement = "use App\Http\Controllers\CMS\\$className" . 'Controller;';
        $fileContent  = File::get($filePath);
        $isExists     = preg_match($this->generatePattern($useStatement), $fileContent);
        if (! $isExists) {
            $fileContent = preg_replace('/<\?php/', "<?php\n\n" . $useStatement, $fileContent, 1);
            File::put($filePath, $fileContent);
        }

        $this->info("Route for $this->module generated successfully in routes/cms.php!");
    }

    /**
     * @throws Exception
     */
    private function updateMenu(): void
    {
        $className = $this->getStudlyDummy();

        $stubPath = $this->getStubPath('cms-menu.blade.stub');
        $filePath = resource_path('views/cms/layouts/sidebars/menu.blade.php');

        $replacements = [
            '{{ className }}'  => $className,
            '{{ kebabDummy }}' => $this->getKebabDummy(),
        ];

        $this->appendFile($stubPath, $filePath, $replacements);

        $this->info("Menu for module $this->module added successfully!");
    }

    /**
     * @throws Exception
     */
    private function updatePermission(): void
    {
        $className = $this->getStudlyDummy();

        $stubPath = $this->getStubPath('cms-permission.stub');
        $filePath = base_path('database/seeders/RolesAndPermissionsTableSeeder.php');

        $replacements = [
            '{{ className }}' => $className,
        ];

        $template    = $this->replaceContent($stubPath, $replacements);
        $fileContent = File::get($filePath);

        $alreadyExists = preg_match($this->generatePattern($template), $fileContent);
        if (! $alreadyExists) {
            $fileContent = preg_replace('/Register Permissions/', "Register Permissions\n\t\t$template", $fileContent, 1);
            File::put($filePath, $fileContent);

            Artisan::call('db:seed --class RolesAndPermissionsTableSeeder');
            Artisan::call('db:seed --class AdminsTableSeeder');
        }

        $this->info("Permission for module $this->module added successfully!");
    }
}
