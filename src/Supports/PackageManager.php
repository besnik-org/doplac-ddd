<?php

namespace ZupiterDoplac\Domain\Supports;

use Throwable;

class PackageManager
{

    public array $packages = [];

    public function register(array $paths): void
    {
        foreach ($paths as $type => $path) {

            $files = glob($path, GLOB_NOSORT);

            foreach ($files as $file) {

                try {

                    $config = include $file;

                    $config['path'] = dirname($file);

                    $this->packages[$config['id']] = $config;

                } catch (Throwable $e) {

                    continue;
                }

            }
        }
    }

    public function load(array $packages): void
    {
        foreach ($packages as $package) {

            if (isset($package['autoload'])) {

                foreach ($package['autoload'] as $namespace => $path) {

                    $path = rtrim($package['path'], '/') . '/' . $path;
                    $path = rtrim($path, '/');

                    $this->autoload($namespace, $path);
                }
            }

        }
    }

    private function autoload(string $namespace, string $path): void
    {
        spl_autoload_register(function ($class) use ($namespace, $path) {
            $len = strlen($namespace);
            if (strncmp($namespace, $class, $len) !== 0) {
                return;
            }

            $class = substr($class, $len);
            $file = $path . DIRECTORY_SEPARATOR . str_replace('\\', '/', $class) . '.php';

            if (file_exists($file)) {
                require $file;
            }
        });
    }


}
