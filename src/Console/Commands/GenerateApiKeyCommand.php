<?php

namespace Cuytamvan\BasePattern\Console\Commands;

use Illuminate\Console\Command;

use Cuytamvan\BasePattern\Services\ApiKeyService;
use Exception;

class GenerateApiKeyCommand extends Command {
    protected $signature = 'gen:apikey {name}';
    protected $description = 'For generate API Keys';

    public function __construct() {
        parent::__construct();
    }

    public function handle() {
        try {
            $name = $this->argument('name');
            if (config('cuypattern.api_key')) {
                $data = ApiKeyService::generate($name);

                if ($data) $this->info('Success generated.');
                else $this->error('Something wrong.');
            } else {
                $this->warn('Checking API keys is disable.');
            }
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
        return 0;
    }
}
