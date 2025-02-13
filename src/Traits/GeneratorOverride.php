<?php

namespace ZupiterDoplac\Domain\Traits;

use ZupiterDoplac\Domain\Supports\DomainSupport;

use Illuminate\Support\Str;

use function Laravel\Prompts\select;

trait GeneratorOverride
{

    protected array $domain = [];

    /**
     * Get the root namespace for the class.
     *
     * @return string
     */
    protected function rootNamespace()
    {
        if(! empty($this->domain)) {
            return $this->domain['namespace'];
        }

        $comm = DomainSupport::init(true);

        $domain = select(
            label: 'Please select domain!',
            options: $comm->getOnlyDomains(),
            default: 'App'
        );

        $this->domain = $comm->getDomainDetails($domain);

        return $this->domain['namespace'];
    }

    /**
     * Get the destination class path.
     *
     * @param  string  $name
     * @return string
     */
    protected function getPath($name)
    {
        $name = Str::replaceFirst($this->rootNamespace(), '', $name);

//        dd(base_path($this->domain['real_path']), $name);

        return base_path($this->domain['real_path'].DIRECTORY_SEPARATOR.str_replace('\\', '/', $name).'.php');
    }

}