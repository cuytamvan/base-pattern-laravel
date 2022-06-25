<?php

namespace Cuytamvan\BasePattern\Repository;

use Illuminate\Database\Eloquent\Collection;

use Exception;
use Illuminate\Database\Eloquent\Builder;

abstract class CoreRepository
{
    protected $guard = null;
    protected $withActivities = true;
    protected $orderBy = null;
    protected $payload = [];

    public function user()
    {
        return auth($this->guard)->user();
    }

    public function uid()
    {
        return auth($this->guard)->id();
    }

    public function setGuard(string $guard)
    {
        $this->guard = $guard;
    }

    public function store($id = null, $input = [])
    {
        try {
            $type = 'create';
            if (!$id && in_array('created_by', $this->model->columns())) $input['created_by'] = $this->uid();
            if ($id && in_array('updated_by', $this->model->columns())) $input['updated_by'] = $this->uid();

            if ($id) {
                $type = 'update';
                $data = $this->findById($id);
                if (!$data) throw new Exception('Not found');
                $data->update($input);
            } else $data = $this->model->create($input);

            if ($this->user() && $this->withActivities) {
                activity($this->moduleName ?? '')
                    ->setProperties(['color' => '#3B82F6', 'type' => $type])
                    ->setCauser($this->user())
                    ->addSubject($data)
                    ->save();
            }

            return $data;
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function count($where = null)
    {
        $query = $this->model->query();
        if ($where) $query = $query->where($where);
        return $query->count();
    }

    public function sum($field, $where = null)
    {
        $query = $this->model->query();
        if ($where) $query = $query->where($where);
        return $query->sum($field);
    }

    public function all($where = null, $with = []): Collection
    {
        $data = $this->model->query()->with($with);
        if ($where) $data->where($where);
        if ($this->orderBy && is_array($this->orderBy)) {
            foreach ($this->orderBy as $field => $type) {
                $data = $data->orderBy($field, $type);
            }
        }
        $data = $this->searchable($data);
        return $data->get();
    }

    public function setOrderBy($orderBy)
    {
        $this->orderBy = $orderBy;
    }

    public function validateColumns(string $name, array $customColumns = null)
    {
        $columns = $customColumns ?? $this->model->columns();
        $column = str_replace('!', '', $name);

        return in_array($column, $columns) ? $column : null;
    }

    public function setPayload($payload = [])
    {
        $arr = $payload;
        $arr['withPagination'] = true;

        if (isset($payload['_limit'])) {
            $limit = (int) $payload['_limit'];
            if ($limit < 1) $arr['withPagination'] = false;
        }

        $this->payload = $payload;

        return $arr;
    }

    public function searchable(Builder $data): Builder
    {
        $payload = $this->payload;
        $columns = $this->model->columns();

        $validate = fn ($str) => is_numeric($str) || is_date($str);

        $search = isset($payload['_search']) ? extract_params($payload['_search']) : [];
        $order = isset($payload['_order']) ? extract_params($payload['_order']) : [];
        $searchLike = isset($payload['_like']) ? extract_params_like($payload['_like']) : null;

        $requestMin = isset($payload['_min']) ? extract_params($payload['_min']) : [];
        $requestMax = isset($payload['_max']) ? extract_params($payload['_max']) : [];

        $requestRelation = isset($payload['_search_relation']) ? extract_key_relation($payload['_search_relation']) : [];

        $mapMinMax = fn ($arr) => [
            'column' => $arr['key'],
            'value' => is_date($arr['value']) ? date('Y-m-d', strtotime($arr['value'])) : $arr['value'],
        ];

        $min = array_map(function ($r) use ($mapMinMax) {
            return $mapMinMax($r);
        }, array_filter($requestMin, function ($r) use ($validate) {
            return $validate($r['value']);
        }));

        $max = array_map(function ($r) use ($mapMinMax) {
            return $mapMinMax($r);
        }, array_filter($requestMax, function ($r) use ($validate) {
            return $validate($r['value']);
        }));

        $data->where(function (Builder $data) use ($columns, $search, $min, $max, $searchLike) {
            if (count($search)) {
                foreach ($search as $params) {
                    if ($column = $this->validateColumns($params['key'])) {
                        $value = $params['value'];
                        $importantCheck = explode('!', $params['key']);

                        if ($value == 'not_null') $data->whereNotNull($column);
                        else if ($value == 'is_null') $data->whereNull($column);
                        else if (isset($importantCheck[1])) $data->where($column, $value);
                        else $data->whereRaw($column . ' like ?', ['%' . $value . '%']);
                    }
                }
            }

            if ($searchLike) {
                $data->where(function ($q) use ($columns, $searchLike) {
                    $index = 0;
                    foreach ($searchLike->columns as $r) {
                        if (in_array($r, $columns)) {
                            if ($index === 0) $q->where($r, 'LIKE', "%{$searchLike->value}%");
                            else $q->orWhere($r, 'LIKE', "%{$searchLike->value}%");
                            $index++;
                        }
                    }
                });
            }

            if (count($min)) {
                $data->where(function ($q) use ($columns, $min) {
                    foreach ($min as $r) {
                        if (in_array($r['column'], $columns)) $q->where($r['column'], '>=', $r['value']);
                    }
                });
            }

            if (count($max)) {
                $data->where(function ($q) use ($columns, $max) {
                    foreach ($max as $r) {
                        if (in_array($r['column'], $columns)) $q->where($r['column'], '<=', $r['value']);
                    }
                });
            }
        });

        $relationList = method_exists($this->model, 'relations') ? $this->model->relations() : [];

        foreach ($requestRelation as $r) {
            $check = $relationList[$r['relation']] ?? null;

            $column = $this->validateColumns($r['column']['key'], $check);

            if ($check && $column) {
                $data->whereHas($r['relation'], function ($q) use ($column, $r) {
                    $value = $r['column']['value'];
                    $importantCheck = explode('!', $r['column']['key']);

                    if ($value == 'not_null') $q->whereNotNull($column);
                    else if ($value == 'is_null') $q->whereNull($column);
                    else if (isset($importantCheck[1])) $q->where($column, $value);
                    else $q->whereRaw(
                        $column . ' like ?',
                        ['%' . $value . '%']
                    );
                });
            }
        }

        if (count($order)) {
            $available = ['asc', 'desc'];
            foreach ($order as $r) {
                $column = $r['key'];
                if (in_array($column, $columns)) {
                    $value = isset($r['value']) && in_array($r['value'], $available) ? $r['value'] : 'asc';
                    $data->orderBy($column, $value);
                }
            }
        }

        return $data;
    }

    public function paginate($limit = 10, $where = null, $with = [], $whereHas = null)
    {
        $data = $this->model->query()->with($with);
        if ($where) $data = $data->where($where);
        if (isset($whereHas) && is_array($whereHas) && count($whereHas)) {
            foreach ($whereHas as $relateable => $func) $data = $data->whereHas($relateable, $func);
        }

        if ($this->orderBy && is_array($this->orderBy)) {
            foreach ($this->orderBy as $field => $type) {
                $data = $data->orderBy($field, $type);
            }
        }

        $data = $this->searchable($data);
        return $data->paginate($limit);
    }

    public function getData($limit = 10, $where = null, $with = [], $whereHas = null)
    {
        $limit = (int) ($this->payload['_limit'] ?? $limit);
        $query = $this->model->query()->with($with);

        if ($where) $query->where($where);
        if (isset($whereHas) && is_array($whereHas) && count($whereHas)) {
            foreach ($whereHas as $relateable => $func) $query->whereHas($relateable, $func);
        }

        $data = $this->searchable($query);

        if ($limit < 1) return $data->get();

        return $data->paginate($limit, ['*'], '_page')->appends($this->payload);
    }

    public function findById($id, $where = null)
    {
        $data = $this->model->query();
        if ($where) $data->where($where);
        return $data->find($id);
    }

    public function delete(...$id)
    {
        if ($this->user() && $this->withActivities) {
            $activity = activity($this->moduleName ?? '')
                ->setProperties(['color' => '#EF4444', 'type' => 'delete'])
                ->setCauser($this->user());
        }

        if (is_array($id)) {
            $query = $this->model->whereIn('id', $id);
            if ($this->user() && $this->withActivities) {
                foreach ($query->get() as $r) $activity->addSubject($r);
                $activity->save();
            }
            if (in_array('deleted_by', $this->model->columns())) $query->update(['deleted_by' => $this->uid()]);
            return $query->delete();
        } else {
            $data = $this->findById($id);
            if ($data) {
                if ($this->user() && $this->withActivities) $activity->addSubject($data)->save();
                if (in_array('deleted_by', $this->model->columns())) $data->update(['deleted_by' => $this->uid()]);
                return $data->delete();
            } else {
                return null;
            }
        }
    }
}
