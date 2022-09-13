# Base Repo Laravel

<a href="https://packagist.org/packages/cuytamvan/base-pattern-laravel"><img src="https://img.shields.io/packagist/dt/cuytamvan/base-pattern-laravel" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/cuytamvan/base-pattern-laravel"><img src="https://img.shields.io/packagist/l/cuytamvan/base-pattern-laravel" alt="Lisence"></a>

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
    $params = $request->query();
    $this->repository->setPayload($params);

    /**
     * automaticly detect request _limit, default for request _limit is 10
     * if _limit >= 1, it will be paginate
     * if _limit less than 1, it will show all data
     */

    $data = $this->repository->getData();

    return view('user.index', compact('data'));
}

/**
 * if you use $data for api, i suggest to use collection and resource
 * you can read documentation https://laravel.com/docs/9.x/eloquent-resources
 */

public function index(Request $request)
{
    try {
        $params = $request->query();
        $payload = $this->repository->setPayload($params);
        $data = $this->repository->getData();

        $res = $payload['withPagination'] ?
            new UserCollection($data) :
            UserResource::collection($data);

        return response()->json([
            'message' => 'success',
            'data' => $res,
        ]);
    } catch (Exception $e) {
        return response()->json([
            'message' => $e->getMessage(),
            'data' => null,
        ], 500);
    }
}

```

### Min
filter data with min value of field, available for field date and numeric

example url:

`{{base_url}}/module-name?_min=created_at:2022-02-02`

`{{base_url}}/module-name?_min=price:20000`

if want to filter more than 1 field:

example url:

`{{base_url}}/module-name?_min=created_at:2022-02-02|updated_at:2022-02-02`

`{{base_url}}/module-name?_min=price:20000|qty:20000`

### Max
filter data with max value of field, available for field date and numeric

example url:

`{{base_url}}/module-name?_max=created_at:2022-02-02`

`{{base_url}}/module-name?_max=price:20000`

if want to filter more than 1 field:

example url:

`{{base_url}}/module-name?_max=created_at:2022-02-02|updated_at:2022-02-02`

`{{base_url}}/module-name?_max=price:20000|qty:20000`

### Search like
filter data with search like

example url:

`{{base_url}}/module-name?_like=name:loremipsumdolor`

`{{base_url}}/module-name?_like=name,username,email:loremipsumdolor`

### Search perfield
filter data with search spesific columns, but you must define function on your model
```php
// get from fillable
public function columns() {
    $arr = $this->fillable;

    // additional columns
    $arr[] = 'created_at';
    $arr[] = 'updated_at';

    return $arr;
}

// or manualy
public function columns() {
    return [
        'name',
        'email',
        'created_at',
        'updated_at',
    ];
}
```

example url for search like:

`{{base_url}}/module-name?_search=name:lorem ipsum dolor sit amet|email:test`

example url for search exact:

`{{base_url}}/module-name?_search=name!:lorem ipsum dolor sit amet|email!:test`

### Search by relation
filter data with search spesific columns on relation, but you must define function on your model
```php
public function relations()
{
    return [
        'user' => (new User())->columns(),

        // or

        'user' => [
            'name',
            'email',
        ],
    ];
}

// relation
public function user()
{
    return $this->belongsTo(User::class);
}
```

example url for search like:

`{{base_url}}/module-name?_search_relation=user.name:cuytamvan`

example url for search exact:

`{{base_url}}/module-name?_search_relation=user.name!:cuytamvan`

### Order
order by field

example url:

`{{base_url}}/module-name?_order=name:asc`

if want to order more than 1 field:

`{{base_url}}/module-name?_order=name:asc|email:desc`

### Combine all searchable

`{{base_url}}/module-name?_order=name:asc|email:desc&search=name:lorem&min=created_at:2022-02-02`
