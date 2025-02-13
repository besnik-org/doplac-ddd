<?php declare(strict_types=1);

namespace Illuminate\Database\Console\Migrations;

use ZupiterDoplac\Domain\Supports\DomainSupport;
use Illuminate\Console\Command;

use Illuminate\Support\Str;

use function Laravel\Prompts\select;

class BaseCommand extends Command
{
    protected array $domain = [];

    /**
     * Get all of the migration paths.
     *
     * @return array
     */
    protected function getMigrationPaths()
    {
        // Here, we will check to see if a path option has been defined. If it has we will
        // use the path relative to the root of the installation folder so our database
        // migrations may be run for any customized path from within the application.
        if ($this->input->hasOption('path') && $this->option('path')) {
            return collect($this->option('path'))->map(function ($path) {
                return ! $this->usingRealPath()
                                ? $this->laravel->basePath().'/'.$path
                                : $path;
            })->all();
        }

        return array_merge(
            $this->migrator->paths(),
            [$this->getMigrationPath()]
        );
    }

    /**
     * Determine if the given path(s) are pre-resolved "real" paths.
     *
     * @return bool
     */
    protected function usingRealPath()
    {
        return $this->input->hasOption('realpath') && $this->option('realpath');
    }

    private function getDomain(): array
    {
        if(! empty($this->domain)) {
            return $this->domain;
        }

        $comm = DomainSupport::init(true);

        $domain = select(
            label: 'Please select domain for migration file!',
            options: $comm->getOnlyDomains(),
            default: 'root'
        );

        return  $this->domain = $comm->getDomainDetails($domain);
    }

    /**
     * Get the path to the migration directory.
     *
     * @return string
     */
    protected function getMigrationPath()
    {
        if(Str::contains($this->signature, 'migrate {--database')) {
            return $this->laravel->databasePath().DIRECTORY_SEPARATOR.'migrations';
        }

        $this->getDomain();
      
        return base_path($this->domain['real_path'].DIRECTORY_SEPARATOR.'../database'.DIRECTORY_SEPARATOR.'migrations');
    }
}
