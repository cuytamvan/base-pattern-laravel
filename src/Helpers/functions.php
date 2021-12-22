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
