<?php

namespace ZupiterDoplac\Domain;

use ZupiterDoplac\Domain\Supports\PackageManager;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;
use Illuminate\Console\Scheduling\Schedule;

class DomainDrivenServiceProvider extends ServiceProvider
{

    private array $activePackages = [];

    public function boot()
    {

        $schedules = [];
        $commands = [];

        foreach ($this->activePackages as $activePackage){

            if (isset($activePackage['providers'])) {

                $providers = (array) $activePackage['providers'];

                foreach ($providers as $provider) {
                    $this->app->register($provider);
                }
            }

            if (isset($activePackage['schedules'])) {

                $schedulePaths = (array) $activePackage['schedules'];

                foreach ($schedulePaths as $schedulePath) {
                    $schedules[] = $activePackage['path'].DIRECTORY_SEPARATOR.$schedulePath;
                }
            }

            if ( !app()->configurationIsCached() && isset($activePackage['config'])) {

                $configs = (array) $activePackage['config'];

                foreach ($configs as $key => $config) {
                    $configPath =  $activePackage['path'].DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.$config;

                    Config::set("$key", require $configPath);
                }
            }

            if ( app()->runningInConsole() && isset($activePackage['commands']) && $activePackage['commands']) {

                $configs =  $activePackage['config'];
                $appNamespace = array_search("app/", $activePackage['autoload'], true);

                foreach (File::allFiles($activePackage['path'].DIRECTORY_SEPARATOR.$activePackage['commands']) as $file) {
                    $fileName = explode('.', $file->getFilename())[0];
                    $commands[] = '\\'.$appNamespace.'Console\\Commands\\'.$fileName;
                }
            }
        }

        if ($this->app->runningInConsole()) {

            $this->app->booted(function () use ($schedules) {

                $schedule = $this->app->make(Schedule::class);

                foreach ($schedules as $schedulePath) {
                    include  $schedulePath;
                }
            });

            $this->commands([
                \ZupiterDoplac\Domain\Commands\DomainClear::class,
                \ZupiterDoplac\Domain\Commands\DomainMigration::class,
                ...$commands
            ]);
        }

    }

    public function register()
    {
        // Register the package managers
        $this->app->singleton(PackageManager::class, fn () => new PackageManager());

        /** @var PackageManager $manager */
        $manager = $this->app->make(PackageManager::class);

        $roots = config('packages.locations') ?? [];

        $paths = array_map(fn ($root) => base_path($root).'/*/index.php', $roots);

        $manager->register($paths);

        $packageStatus = config('packages.status') ?? [];


        $activePackages = array_filter($manager->packages, function ($key) use ($packageStatus) {
            return isset($packageStatus[$key]) && $packageStatus[$key] === true;
        }, ARRAY_FILTER_USE_KEY);


        $this->activePackages = $activePackages;


        $manager->load( $this->activePackages );
    }

}