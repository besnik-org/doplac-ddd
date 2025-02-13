<?php

declare(strict_types=1);

namespace ZupiterDoplac\Domain\Supports;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class DomainSupport
{
    private static DomainSupport|null $instance = null;
    public bool $withoutCache = false;
    private array $domains = [];
    private array $seeders = [];
    private array $factories = [];

    public function __construct()
    {
        $this->cacheData();
    }

    private function arrayToString($array, $indent = 0) {
        $output = '';

        foreach ($array as $key => $value) {
            $output .= str_repeat(' ', $indent * 4) . "'" . $key . "' => ";

            if (is_array($value)) {
                $output .= "[\n" . $this->arrayToString($value, $indent + 1) . str_repeat(' ', $indent * 4) . "],\n";
            } else {
                $output .= "'" . addslashes($value) . "',\n";
            }
        }

        return $output;
    }


    public static function clearData()
    {
        $storagePath = storage_path('app/domain-data.php');

        if (File::exists($storagePath)) {
            File::delete($storagePath);
        }
    }


    private function cacheData()
    {
        $storagePath = storage_path('app/domain-data.php');

        if (!file_exists($storagePath)) {

            File::ensureDirectoryExists(storage_path('app'));

            $content = $this->data();

            $returnStatement = $this->arrayToString($content, 1);

            $contentFinal = "<?php\n\n";
            $contentFinal .= "return [\n" . $returnStatement . "];\n";

            $this->seeders = $content['seeders'];
            $this->factories = $content['factories'];
            $this->domains = $content['domains'];

            if (file_put_contents($storagePath, $contentFinal) !== false) {
            }

            return;
        }

        $content =  include $storagePath;
        $this->seeders = $content['seeders'];
        $this->factories = $content['factories'];
        $this->domains = $content['domains'];
    }

    protected function data(): array
    {
        /** @var array{ factories: array, domains: array, seeders: array } $cachedData */

        $composerData = json_decode(file_get_contents(base_path('composer.json')), true);
        $autoload = $composerData['autoload']['psr-4'] ?? [];

        ksort($autoload);
        $seeders = [];
        $domains = [
          "app" => [
                'title' => 'app',
                'namespace' => 'App\\',
                'real_path' => 'app'
          ]
        ];
        $factories = [];

        $allDomainFiles = glob(base_path('domains').'/*/index.php', GLOB_NOSORT);

        foreach ($allDomainFiles as $file) {
            $config = include $file;
            $split = explode('/', $file);

            $title = $split[count($split)-2];
            
            $domains[$title] = [
                'title' => $title,
                'namespace' => array_keys($config['autoload'])[0],
                'real_path' => 'domains'.DIRECTORY_SEPARATOR.$title.DIRECTORY_SEPARATOR.'app',
            ];

//            dd($domains);
        }


        return [
            'factories' => $factories,
            'domains' => $domains,
            'seeders' => $seeders,
        ];
    }

    public static function init($withoutCache = false): DomainSupport
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function getOnlyDomains(): array
    {
        return array_map(function ($item) {
            return $item['title'];
        }, $this->domains);
    }

    public function getDomainDetails($domain): array
    {
        return $this->domains[$domain];
    }

    public function getDomains(): array
    {
        return $this->domains;
    }

    public function getSeeders(): array
    {
        return $this->seeders;
    }

    public function getFactories(): array
    {
        return $this->factories;
    }
}
