<?php

namespace Cuytamvan\BasePattern\Model;

use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    protected $fillable = [
        'log_name',
        'description',
        'ref_model',
        'ref_id',
        'causer_model',
        'causer_id',
        'properties',
    ];

    public function columns()
    {
        $arr = $this->fillable;
        $arr[] = 'id';
        $arr[] = 'created_at';
        $arr[] = 'updated_at';
        return $arr;
    }

    public function causer()
    {
        return $this->morphTo(__FUNCTION__, 'causer_model');
    }

    public function ref()
    {
        return $this->morphTo(__FUNCTION__, 'ref_model');
    }
}
