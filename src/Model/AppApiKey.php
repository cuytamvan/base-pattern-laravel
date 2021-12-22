<?php

namespace Cuytamvan\BasePattern\Model;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Exception;

class AppApiKey extends Model {
    protected $fillable = [
        'name',
        'secret',
        'status',
        'hostname',
        'expired_at',
    ];

    public static function check($keys): bool {
        try {
            $check = self::where('secret', $keys)
                ->where('status', 1)
                ->where('expired_at', '>', Carbon::now())
                ->first();

            if ($check) return true;
            else return false;

        } catch (Exception $e) {
            throw $e;
        }
    }
}
