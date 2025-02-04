
## Sample config/package.php

```php

<?php declare(strict_types=1);

return [
    'locations' => ['/domains'],

    'status' => [
        'doplac.cms' => true,
    ],
];


```

## Sample domains/Cms/index.php

```php

<?php declare(strict_types=1);

return [
    'id' => 'doplac.cms',
    'autoload' => [
        'CMS\\' => 'app/',
        "CMS\\Factories\\" => "database/factories/",
        "CMS\\Seeders\\" => "database/seeders/",
    ],
    'providers' => [
        \CMS\Providers\RouteServiceProvider::class
    ],
    'schedules' => [
        'app/Console/Schedule.php',
    ],
    "config" => [
        'cms.services' => 'services.php'
    ],
    'commands' => 'app/Console/Commands',
];


```