# Base Repo Laravel

<a href="https://packagist.org/packages/cuytamvan/base-pattern-laravel"><img src="https://img.shields.io/packagist/dt/cuytamvan/base-pattern-laravel" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/cuytamvan/base-pattern-laravel"><img src="https://img.shields.io/packagist/l/cuytamvan/base-pattern-laravel" alt="Lisence"></a>

## About
lorem ipsum dolor sit amet

## Instalation 💻

### Installation in Lumen

you can install the package via composer

`composer require cuytamvan/base-pattern-laravel`


you should copy config

`cp ./vendor/cuytamvan/base-pattern-laravel/config/cuytamvan.php ./config/cuytamvan.php`


change your bootstrap/app.php

```php
$app->configure('cuypattern');
$app->alias('cache', \Illuminate\Cache\CacheManager::class);  // if you don't have this already
$app->provider(Cuytamvan\BasePattern\BasePatternServiceProvider::class);
```

run the migration to create table for this package:

`php artisan migrate`

## Basic Usage
create repository file

`php artisan make:repository ModuleName`

it will generate 2 file:
  - ModuleNameRepository
  - ModuleNameRepositoryEloquent

to use repository, you should create RepositoryServiceProvider.php

```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider {
    protected $repositories = [
        'ModuleName',
    ];

    public function register() {
        foreach($this->repositories as $r) {
            $this->app->bind("App\\Repositories\\{$r}Repository", "App\\Repositories\\{$r}RepositoryEloquent");
        }
    }
}
```

and controller:

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Repositories\ModuleNameRepository;

use Exception;

class ModuleNameController extends Controller {
    protected $repository;
    protected $perm = 'ModuleName';

    public function __construct(ModuleNameRepository $repository) {
        $this->repository = $repository;
    }
}
```
