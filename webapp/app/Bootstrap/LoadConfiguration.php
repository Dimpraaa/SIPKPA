<?php

namespace App\Bootstrap;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Bootstrap\LoadConfiguration as FrameworkLoadConfiguration;
use ReflectionClass;
use Symfony\Component\Finder\Finder;

class LoadConfiguration extends FrameworkLoadConfiguration
{
    /**
     * The application supplies its complete database configuration using the
     * PHP 8.5-compatible PDO constants, so the legacy framework copy is unused.
     *
     * @return array<string, array<string, mixed>>
     */
    protected function getBaseConfiguration(): array
    {
        $configuration = [];
        $frameworkDirectory = dirname((new ReflectionClass(Application::class))->getFileName(), 4);

        foreach (Finder::create()->files()->name('*.php')->notName('database.php')->in($frameworkDirectory.'/config') as $file) {
            $configuration[basename($file->getRealPath(), '.php')] = require $file->getRealPath();
        }

        return $configuration;
    }
}
