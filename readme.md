# Base Repo Laravel

<a href="https://packagist.org/packages/cuytamvan/base-pattern-laravel"><img src="https://img.shields.io/packagist/dt/cuytamvan/base-pattern-laravel" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/cuytamvan/base-pattern-laravel"><img src="https://img.shields.io/packagist/l/cuytamvan/base-pattern-laravel" alt="Lisence"></a>

## About
lorem ipsum dolor sit amet

## Instalation 💻

### Setup package in Lumen

you can install the package via composer

`composer require cuytamvan/base-pattern-laravel`


you should copy config

`cp ./vendor/cuytamvan/base-pattern-laravel/config/cuypattern.php ./config/cuypattern.php`

change your bootstrap/app.php

```php
$app->configure('cuypattern');
$app->alias('cache', \Illuminate\Cache\CacheManager::class);  // if you don't have this already
$app->provider(Cuytamvan\BasePattern\BasePatternServiceProvider::class);
```

### Setup package in laravel

add your config/app.php

```php
[
    ...
    'providers' => [
        ...
        Cuytamvan\BasePattern\BasePatternServiceProvider::class,
    ],
]
```

publish your fucking provider with command  : `php artisan vendor:publish`

and choose `Provider: Cuytamvan\BasePattern\BasePatternServiceProvider`

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

    public function __construct(ModuleNameRepository $repository) {
        $this->repository = $repository;
    }
}
```

## Searchable
On controller setup your index

```php
public function index(Request $request) {
    $limit = $request->limit ?? 10; // for limit data
    $params = $request->query();
    $this->repository->setPayload($params);

    return $this->repository->paginate($limit)->appends($params);
}
```

### Min
filter data with min value of field, available for field date and numeric

example url:

`{{base_url}}/module-name?min=created_at:2022-02-02`

`{{base_url}}/module-name?min=price:20000`

if want to filter more than 1 field:

example url:

`{{base_url}}/module-name?min=created_at:2022-02-02|updated_at:2022-02-02`

`{{base_url}}/module-name?min=price:20000|qty:20000`

### Max
filter data with max value of field, available for field date and numeric

example url:

`{{base_url}}/module-name?max=created_at:2022-02-02`

`{{base_url}}/module-name?max=price:20000`

if want to filter more than 1 field:

example url:

`{{base_url}}/module-name?max=created_at:2022-02-02|updated_at:2022-02-02`

`{{base_url}}/module-name?max=price:20000|qty:20000`

### Search like
filter data with search like

example url:

`{{base_url}}/module-name?search_like=name:loremipsumdolor`

`{{base_url}}/module-name?search_like=name,username,email:loremipsumdolor`

### Search perfield
filter data with search spesific columns

example url:

`{{base_url}}/module-name?search=name:lorem ipsum dolor sit amet|email:test`

### Order
order by field

example url:

`{{base_url}}/module-name?order=name:asc`

if want to order more than 1 field:

`{{base_url}}/module-name?order=name:asc|email:desc`
