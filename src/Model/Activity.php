<?php

namespace Cuytamvan\BasePattern\Model;

use Illuminate\Database\Eloquent\Model;

class Activity extends Model {
    protected $fillable = [
        'log_name',
        'description',
        'ref_model',
        'ref_id',
        'causer_model',
        'causer_id',
        'properties',
    ];
}
