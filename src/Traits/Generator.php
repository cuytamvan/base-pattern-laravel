<?php

namespace Cuytamvan\BasePattern\Traits;

use Illuminate\Support\Facades\File;

trait Generator {
    protected function getStub($stub) {
        $data = file_get_contents(__DIR__.'/../../resources/stubs/'.$stub.'.stub');
        return $data;
    }

    protected function handleDirectory() {

    }

    public function generate($folder, $name, $template) {
        $dir = app_path($folder);
        $checkToArray = explode('/', $dir);
        $check = '';
        foreach($checkToArray as $r) {
            $check .= $r.'/';
            if(!file_exists($check)) mkdir($check);
        }

        file_put_contents($dir.$name, $template);
        return true;
    }
}
