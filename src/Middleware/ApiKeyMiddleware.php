<?php

namespace Cuytamvan\BasePattern\Middleware;

use Carbon\Carbon;
use Cuytamvan\BasePattern\Services\ApiKeyService;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Closure, Exception;

class ApiKeyMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (config('cuypattern.api_key')) {
            $key = $request->header(config('cuypattern.api_key_name'), '');
            $data = $this->cachingKey($key);

            if (!$data['success']) return response()->json([
                'status' => 401,
                'message' => $data['message'],
            ], 401);
        }
        return $next($request);
    }

    protected function cachingKey($key): array
    {
        $name = 'api-key__' . $key;
        $res = [
            'success' => false,
            'message' => '',
            'domains' => [],
        ];
        $domain = $_SERVER['HTTP_HOST'];

        try {
            if (Cache::has($name)) {
                $cache = Cache::get($name);

                $res['success'] = true;
                $res['message'] = 'success';
                $res['domains'] = $cache['domains'];

                if (!ApiKeyService::checkHostname($cache['domains'], $domain)) throw new Exception('You can\'t access this API with your domain.');

                return $res;
            } else {
                $data = new ApiKeyService();
                $data->setKey($key);

                $errors = $data->getError();
                if ($errors->count()) throw new Exception($errors->first());

                $res['success'] = true;
                $res['message'] = 'success';
                $res['domains'] = $data->getHostname();
                Cache::put($name, $res, Carbon::now()->addMinutes(15));

                if (!ApiKeyService::checkHostname($res['domains'], $domain)) throw new Exception('You can\'t access this API with your domain.');

                return $res;
            }
        } catch (Exception $e) {
            $res['success'] = false;
            $res['message'] = $e->getMessage();

            return $res;
        }
    }
}
