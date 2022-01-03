<?php

use Carbon\Carbon;
use Cuytamvan\BasePattern\Services\ActivityService;
use Illuminate\Database\Schema\Blueprint;

function activity($logName): ActivityService {
    return new ActivityService($logName);
}

function addFieldActionBy(Blueprint $table, $softDelete = false) {
    $table->unsignedBigInteger('created_by')->nullable();
    $table->unsignedBigInteger('updated_by')->nullable();
    if ($softDelete) $table->unsignedBigInteger('deleted_by')->nullable();
}

if (!function_exists('app_path')) {
    function app_path($path = '') {
        return app('path') . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }
}

if (!function_exists('config_path')) {
    function config_path($path = '') {
        return app()->basePath() . '/config' . ($path ? '/' . $path : $path);
    }
}

if (!function_exists('time_readable')) {
    function time_readable($time, $withTime = false) {
        return (new Carbon($time))->isoFormat('MMMM Do YYYY'.($withTime ? ', HH:mm:ss' : ''));
    }
}

if (!function_exists('extract_params')) {
    function extract_params($params) {
        $fix_params = [];
        $parent = explode('|', $params);
        if (count($parent) != 0) {
            foreach ($parent as $value) {
                if ($value != '') {
                    $child = explode(':', $value);
                    if (count($child) != 0 &&
                        isset($child[0]) && $child[0] != '' &&
                        isset($child[1]) && $child[1] != '' &&
                        $child[0] != '' && $child[1] != ''
                    ) {
                        $val = $child[1];
                        if (isset($child[2])) $val = $val.':'.$child[2];
                        if (isset($child[3])) $val = $val.':'.$child[3];
                        $fix_params[] = ['key' => $child[0], 'value' => $val];
                    }
                }
            }
        }
        return $fix_params;
    }
}

if (!function_exists('extract_params_like')) {
    function extract_params_like($str) {
        if ($str && $str !== '') {
            $ex = explode(':', $str);
            $columns = collect(explode(',', $ex[0]));
            $value = $ex[1];
            foreach($ex as $i => $r) {
                if ($i > 1) $value .= ':'.$r;
            }
    
            return (object) [
                'columns' => $columns,
                'value' => $value,
            ];
        }
        return null;
    }
}

if (!function_exists('extract_params_like')) {
    function extract_key_relation($key) {
        $res = null;
        $ex = explode('.', $key);
        if ( count($ex) > 1 && count($ex) < 3 && strlen($ex[0]) > 0 && strlen($ex[1]) > 0) {
            $importantCheck = explode('!', $ex[1]);
            $column = $importantCheck[0];

            $operatoCheck = explode('@', $column);
            $column = $operatoCheck[0];

            $res = [
                'relation' => $ex[0],
                'column' => $column,
            ];
        }
        return $res;
    }
}
