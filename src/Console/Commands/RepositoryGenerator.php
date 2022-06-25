<?php

namespace Cuytamvan\BasePattern\Console\Commands;

use Illuminate\Console\Command;

use Cuytamvan\BasePattern\Traits\Generator;

class RepositoryGenerator extends Command
{
    protected $signature = 'make:repository {name}';
    protected $description = 'Fucking make repository bitch';

    use Generator;

    public function __construct()
    {
        parent::__construct();
    }

    protected function repository($name)
    {
        $template = str_replace(
            ['{{name}}'],
            [$name],
            $this->getStub('Repository')
        );
        $this->generate("Repositories/", "{$name}Repository.php", $template);
    }

    protected function repositoryEloquent($name)
    {
        $template = str_replace(
            ['{{name}}'],
            [$name],
            $this->getStub('RepositoryEloquent')
        );
        $this->generate("Repositories/", "{$name}RepositoryEloquent.php", $template);
    }

    public function handle()
    {
        $name = $this->argument('name');

        $this->repository($name);
        $this->repositoryEloquent($name);

        $this->info('Repository created successfully');

        return 0;
    }
}
