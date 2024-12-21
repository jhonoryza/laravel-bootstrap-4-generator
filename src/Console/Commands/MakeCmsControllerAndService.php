<?php

namespace Jhonoryza\Rgb\BasecodeGen\Console\Commands;

use Jhonoryza\Rgb\BasecodeGen\Console\Commands\Concerns\ColumnTrait;
use Jhonoryza\Rgb\BasecodeGen\Console\Commands\Concerns\FactoryTrait;
use Jhonoryza\Rgb\BasecodeGen\Console\Commands\Concerns\ReplaceKeywordsTrait;
use Jhonoryza\Rgb\BasecodeGen\Console\Commands\Concerns\ValidationTrait;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

use function Laravel\Prompts\text;

class MakeCmsControllerAndService extends Command
{
    use ReplaceKeywordsTrait, ColumnTrait, FactoryTrait, ValidationTrait;

    protected function getTableName(): string
    {
        return Str::snake(Str::plural($this->module));
    }

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:cms {module? : module name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a CMS files with predefined structure';

    protected string | null $module = null;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->module = $this->argument('module');
        if ($this->module == null) {
            $this->module = text(
                label: 'module name',
                required: true,
                placeholder: 'Category'
            );
        }
        $this->module = Str::camel(Str::singular($this->module));

        //$this->generateMigration();
        $this->generateModel();
        $this->generateFactory();
        $this->generateSeeder();
        $this->generateService();
        $this->generateRequest();
        $this->generateController();
        $this->generateIndexBlade();
        $this->generateCreateBlade();
        $this->generateEditBlade();
        $this->generateRoute();
        $this->generateMenu();
        $this->generatePermission();
    }

    /**
     * Helper to generate files from stubs.
     */
    protected function generateFile(string $stubPath, string $destinationPath, array $replacements): void
    {
        if (!File::exists($stubPath)) {
            $this->error("Stub file not found: {$stubPath}");
            return;
        }

        $template = File::get($stubPath);
        foreach ($replacements as $key => $value) {
            $template = str_replace($key, $value, $template);
        }

        File::ensureDirectoryExists(dirname($destinationPath));
        File::put($destinationPath, $template);
    }

    /**
     * Helper to append files from stubs.
     */
    protected function appendFile(string $stubPath, string $destinationPath, array $replacements): void
    {
        if (!File::exists($stubPath)) {
            $this->error("Stub file not found: {$stubPath}");
            return;
        }

        $template = File::get($stubPath);
        foreach ($replacements as $key => $value) {
            $template = str_replace($key, $value, $template);
        }

        File::ensureDirectoryExists(dirname($destinationPath));
        File::append($destinationPath, PHP_EOL . $template);
    }

    /**
     * Helper to append files from stubs.
     */
    protected function replaceContent(string $stubPath,  array $replacements): string
    {
        if (!File::exists($stubPath)) {
            $this->error("Stub file not found: {$stubPath}");
            return "";
        }

        $template = File::get($stubPath);
        foreach ($replacements as $key => $value) {
            $template = str_replace($key, $value, $template);
        }

        return $template;
    }

    private function generateMigration(): void
    {
        $tableName = $this->getSnakeDummies();

        $migrationPath = database_path("migrations/" . date('Y_m_d_His') . "_create_{$tableName}_table.php");
        $migrationStub = resource_path('stubs/cms-migration.stub');
        $this->generateFile($migrationStub, $migrationPath, [
            '{{ tableName }}' => $tableName,
        ]);
        $this->info("Migration for table {$tableName} created successfully.");
    }

    private function generateModel(): void
    {
        $modelName = $this->getStudlyDummy();
        $namespace = "App\Models";

        $stubPath = resource_path('stubs/cms-model.stub');
        $filePath = app_path("Models/{$modelName}.php");

        $replacements = [
            '{{ namespace }}' => $namespace,
            '{{ modelName }}' => $modelName,
            '{{ fillable }}' => $this->getFillableColumnList()
                ->map(fn($item) => "'" . $item['name'] . "'")
                ->implode(",\n\t\t")
        ];

        $this->generateFile($stubPath, $filePath, $replacements);

        $this->info("Model {$modelName} created successfully!");
    }

    private function generateFactory(): void
    {
        $modelName = $this->getStudlyDummy();
        $factoryName = "{$modelName}Factory";

        $factoryPath = database_path("factories/{$factoryName}.php");
        $factoryStub = resource_path('stubs/cms-factory.stub');

        $replacements = [
            '{{ modelName }}' => $modelName,
            '{{ factoryName }}' => $factoryName,
            '{{ fillableFactory }}' => $this->getFillableColumnList()
                ->map(fn($item) => "'{$item['name']}' => " . $this->getFakeData($item['type']))
                ->implode(",\n\t\t\t")
        ];

        $this->generateFile($factoryStub, $factoryPath, $replacements);

        $this->info("Factory {$factoryName} created successfully.");
    }

    private function generateSeeder(): void
    {
        $modelName = $this->getStudlyDummy();
        $seederName = "{$modelName}Seeder";

        $seederPath = database_path("seeders/{$seederName}.php");
        $seederStub = resource_path('stubs/cms-seeder.stub');

        $replacements = [
            '{{ modelName }}' => $modelName,
            '{{ seederName }}' => $seederName,
        ];

        $this->generateFile($seederStub, $seederPath, $replacements);

        $this->info("Seeder {$seederName} created successfully.");
    }

    private function generateRequest(): void
    {
        $modelName = $this->getStudlyDummy();
        $className = $modelName . 'StoreRequest';
        $namespace = "App\Http\Requests\CMS\{$modelName}";

        $stubPath = resource_path('stubs/cms-request.stub');
        $filePath = app_path("Http/Requests/CMS/{$modelName}/{$className}.php");

        $replacements = [
            '{{ namespace }}' => $namespace,
            '{{ className }}' => $className,
            '{{ fillableRules }}' => $this->getFillableColumnList()
                ->map(fn($item) => "'{$item['name']}' => '{$this->getValidationRules($item['type'],$item['nullable'])}'")
                ->implode(",\n\t\t\t")
        ];

        $this->generateFile($stubPath, $filePath, $replacements);

        $this->info("Request {$className} created successfully!");
    }

    private function generateService(): void
    {
        $modelName = $this->getStudlyDummy();
        $className = $modelName . 'Service';
        $storeRequestName = $modelName . 'StoreRequest';

        $namespace = "App\Http\Services\CMS";
        $modelNamespace = "App\Models\\$modelName";
        $requestNamespace = "App\Http\Requests\CMS\\$modelName\\$storeRequestName";

        $stubPath = resource_path('stubs/cms-service.stub');
        $filePath = app_path("Http/Services/CMS/{$className}.php");

        $replacements = [
            '{{ namespace }}' => $namespace,
            '{{ modelNamespace }}' => $modelNamespace,
            '{{ requestNamespace }}' => $requestNamespace,
            '{{ className }}' => $className,
            '{{ modelName }}' => $modelName,
            '{{ storeRequestName }}' => $storeRequestName,
            '{{ camelDummy }}' => $this->getCamelDummy(),
        ];

        $this->generateFile($stubPath, $filePath, $replacements);

        $this->info("Service {$className} created successfully!");
    }

    private function generateController(): void
    {
        $modelName = $this->getStudlyDummy();
        $className = $modelName . 'Controller';
        $serviceName = $modelName . 'Service';
        $storeRequestName = $modelName . 'StoreRequest';

        $namespace = "App\Http\Controllers\CMS";
        $modelNamespace = "App\Models\\$modelName";
        $serviceNamespace = "App\Http\Services\CMS\\$serviceName";
        $requestNamespace = "App\Http\Requests\CMS\\$modelName\\$storeRequestName";

        $stubPath = resource_path('stubs/cms-controller.stub');
        $filePath = app_path("Http/Controllers/CMS/{$className}.php");

        $replacements = [
            '{{ namespace }}' => $namespace,
            '{{ modelNamespace }}' => $modelNamespace,
            '{{ requestNamespace }}' => $requestNamespace,
            '{{ serviceNamespace }}' => $serviceNamespace,
            '{{ className }}' => $className,
            '{{ modelName }}' => $modelName,
            '{{ serviceName }}' => $serviceName,
            '{{ storeRequestName }}' => $storeRequestName,
            '{{ camelServiceName }}' => Str::camel($serviceName),
            '{{ kebabDummy }}' => $this->getKebabDummy(),
            '{{ camelDummies }}' => $this->getCamelDummies(),
            '{{ camelDummy }}' => $this->getCamelDummy()
        ];

        $this->generateFile($stubPath, $filePath, $replacements);

        $this->info("Controller {$className} created successfully!");
    }

    private function generateIndexBlade(): void
    {
        $className = $this->getStudlyDummy();
        $fileName = $this->getKebabDummy() . '/index.blade.php';
        $namespace = "views/cms/pages";

        $stubPath = resource_path('stubs/cms-index.blade.stub');
        $filePath = resource_path("{$namespace}/{$fileName}");

        $replacements = [
            '{{ className }}' => $className,
            '{{ kebabDummy }}' => $this->getKebabDummy(),
            '{{ camelDummy }}' => $this->getCamelDummy(),
            '{{ camelDummies }}' => $this->getCamelDummies()
        ];

        $this->generateFile($stubPath, $filePath, $replacements);

        $this->info("Blade file {$fileName} created successfully!");
    }

    private function generateCreateBlade(): void
    {
        $className = $this->getStudlyDummy();
        $fileName = $this->getKebabDummy() . '/create.blade.php';
        $namespace = "views/cms/pages";

        $stubPath = resource_path('stubs/cms-create.blade.stub');
        $filePath = resource_path("{$namespace}/{$fileName}");

        $replacements = [
            '{{ className }}' => $className,
            '{{ kebabDummy }}' => $this->getKebabDummy(),
        ];

        $this->generateFile($stubPath, $filePath, $replacements);

        $this->info("Blade file {$fileName} created successfully!");
    }

    private function generateEditBlade(): void
    {
        $className = $this->getStudlyDummy();
        $fileName = $this->getKebabDummy() . '/edit.blade.php';
        $namespace = "views/cms/pages";

        $stubPath = resource_path('stubs/cms-edit.blade.stub');
        $filePath = resource_path("{$namespace}/{$fileName}");

        $replacements = [
            '{{ className }}' => $className,
            '{{ kebabDummy }}' => $this->getKebabDummy(),
            '{{ camelDummy }}' => $this->getCamelDummy()
        ];

        $this->generateFile($stubPath, $filePath, $replacements);

        $this->info("Blade file {$fileName} created successfully!");
    }

    private function generateRoute(): void
    {
        $className = $this->getStudlyDummy();

        $stubPath = resource_path('stubs/cms-route.stub');
        $filePath = base_path('routes/cms.php');

        $replacements = [
            '{{ className }}' => $className,
            '{{ kebabDummy }}' => $this->getKebabDummy(),
        ];

        $this->appendFile($stubPath, $filePath, $replacements);

        $useStatement = "use App\Http\Controllers\CMS\{$className}Controller;";
        $fileContent = File::get($filePath);
        $fileContent = preg_replace('/<\?php/', "<?php\n\n" . $useStatement, $fileContent, 1);
        File::put($filePath, $fileContent);

        $this->info("Route for {$this->module} generated successfully in routes/cms.php!");
    }

    private function generateMenu(): void
    {
        $className = $this->getStudlyDummy();

        $stubPath = resource_path('stubs/cms-menu.blade.stub');
        $filePath = resource_path("views/cms/layouts/sidebars/menu.blade.php");

        $replacements = [
            '{{ className }}' => $className,
            '{{ kebabDummy }}' => $this->getKebabDummy(),
        ];

        $this->appendFile($stubPath, $filePath, $replacements);

        $this->info("Menu for module {$this->module} added successfully!");
    }

    private function generatePermission(): void
    {
        $className = $this->getStudlyDummy();

        $stubPath = resource_path('stubs/cms-permission.stub');
        $filePath = base_path("database/seeders/RolesAndPermissionsTableSeeder.php");

        $replacements = [
            '{{ className }}' => $className,
        ];
        $fileContent = File::get($stubPath);
        $template = $this->replaceContent($stubPath, $replacements);

        $fileContent = File::get($filePath);
        $fileContent = preg_replace('/Register Permissions/', "Register Permissions\n\t\t{$template}", $fileContent, 1);
        File::put($filePath, $fileContent);

        Artisan::call("db:seed --class RolesAndPermissionsTableSeeder");
        Artisan::call("db:seed --class AdminsTableSeeder");

        $this->info("Permission for module {$this->module} added successfully!");
    }
}
