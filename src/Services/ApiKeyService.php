<?php

namespace Cuytamvan\BasePattern\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

use Cuytamvan\BasePattern\Model\AppApiKey;

use Carbon\Carbon;
use Exception;

class ApiKeyService {
    protected $data = null;
    protected $hostname = '[]';
    protected $error = [];
    protected $model;

    public function __construct() {
        $this->model = new AppApiKey();
    }

    public function setKey($key): self {
        try {
            $data = $this->model->where([
                'secret' => $key,
                'status' => 1,
            ])->first();

            if (!$data) throw new Exception('Keys not found.');
            $this->data = $data;
            $this->hostname = $data->hostname;

        } catch (Exception $e) {
            $this->error[] = $e->getMessage();
        }

        return $this;
    }

    public static function checkExpired($time): bool {
        $expired = new Carbon($time);

        return !$expired->isFuture();
    }

    public function isExpired(): bool {
        try {
            if (!$this->data) throw new Exception('Data not filled in.');
            return self::checkExpired($this->data->expired_at);

        } catch (Exception $e) {
            $this->error[] = $e->getMessage();
            return true;
        }
    }

    public function getError() {
        return collect($this->error);
    }

    public function getAll() {
        $data = $this->data;
        return (object) ($data ? [
            'name' => $data->name,
            'secret' => $data->secret,
            'expired_at' => $data->expired_at,
            'status' => $data->status,
            'hostnames' => $this->getHostname(),
        ] : []);
    }

    public function getHostname(): array {
        if ($this->data) return json_decode($this->hostname, true);
        else return [];
    }

    public function addHostname($name): bool {
        if ($this->data) {
            $hosts = $this->getHostname();
            $hosts[] = $name;

            $newHosts = json_encode($hosts);
            $check = $this->data->update([ 'hostname' => $newHosts ]);
            if ($check) $this->hostname = $newHosts;

            return $check;
        } else return false;
    }

    public function removeHostname($name) {
        if ($this->data) {
            $hosts = $this->getHostname();
            foreach($hosts as $i => $v)
                if ($v === $name) unset($hosts[$i]);

            $newHosts = json_encode($hosts);
            $check = $this->data->update([ 'hostname' => $newHosts ]);
            if ($check) $this->hostname = $newHosts;

            return $check;
        } else return false;
    }

    public function isHostname(Request $request) {
        $hosts = $this->getHostname();
        $host = $request->getHttpHost();

        return self::checkHostname($hosts, $host);
    }

    public static function checkHostname($hostnames = [], $hostname): bool {
        return in_array($hostname, $hostnames);
    }

    public static function generate($name): AppApiKey {
        $format = 'Y-m-d H:i:s';
        $now = Carbon::now()->format($format);
        $expired = Carbon::now()->addDays(60)->format($format);
        $secret = sha1("{$name}__{$now}-{$expired}");

        return AppApiKey::create([
            'name' => $name,
            'secret' => $secret,
            'expired_at' => $expired,
            'status' => 1,
        ]);
    }
}
