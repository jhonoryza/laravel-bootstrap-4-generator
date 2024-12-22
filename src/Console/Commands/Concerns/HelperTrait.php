<?php

namespace Jhonoryza\Rgb\BasecodeGen\Console\Commands\Concerns;

use Exception;
use Illuminate\Support\Facades\File;

trait HelperTrait
{
    /**
     * Helper to generate files from stubs.
     */
    protected function generateFile(string $stubPath, string $destinationPath, array $replacements): void
    {
        if (!File::exists($stubPath)) {
            $this->error("Stub file not found: {$stubPath}");
            throw new Exception("Stub file not found: {$stubPath}");
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
            throw new Exception("Stub file not found: {$stubPath}");
        }

        $template = File::get($stubPath);
        foreach ($replacements as $key => $value) {
            $template = str_replace($key, $value, $template);
        }

        File::ensureDirectoryExists(dirname($destinationPath));
        $isExists = preg_match($this->generatePattern($template), File::get($destinationPath));
        if (!$isExists) {
            File::append($destinationPath, PHP_EOL . $template);
        }
    }

    /**
     * Helper to append files from stubs.
     */
    protected function replaceContent(string $stubPath,  array $replacements): string
    {
        if (!File::exists($stubPath)) {
            $this->error("Stub file not found: {$stubPath}");
            throw new Exception("Stub file not found: {$stubPath}");
        }

        $template = File::get($stubPath);
        foreach ($replacements as $key => $value) {
            $template = str_replace($key, $value, $template);
        }

        return $template;
    }

    /**
     * Helper to get stub file path.
     */
    protected function getStubPath(string $filename): string
    {
        $customPath = app()->basePath('stubs/rgb_basecode_gen/' . $filename);
        return file_exists($customPath)
            ? $customPath
            : __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'Stubs' . DIRECTORY_SEPARATOR . $filename;
    }

    /**
     * Helper generate regex patern
     */
    private function generatePattern($code)
    {
        $specialChars = ['\\', '/', '.', '*', '+', '?', '|', '(', ')', '[', ']', '{', '}', '^', '$', '-'];

        $escapedCode = str_replace(
            $specialChars,
            array_map(fn($char) => "\\" . $char, $specialChars),
            $code
        );

        return "/" . $escapedCode . "/";
    }
}
