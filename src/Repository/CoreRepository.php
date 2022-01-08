<?php

namespace Cuytamvan\BasePattern\Repository;

use Illuminate\Database\Eloquent\Collection;

use Exception;
use Illuminate\Database\Eloquent\Builder;

abstract class CoreRepository {
    protected $guard = null;
    protected $withActivities = true;
    protected $orderBy = null;
    protected $payload = [];

    public function user() {
        return auth($this->guard)->user();
    }

    public function uid() {
        return auth($this->guard)->id();
    }

    public function setGuard(string $guard) {
        $this->guard = $guard;
    }

    public function store($id = null, $input = []) {
        try {
            $type = 'create';
            if (!$id && method_exists('columns', $this->model) && in_array('created_by', $this->model->columns())) $input['created_by'] = $this->uid();
            if ($id && method_exists('columns', $this->model) && in_array('updated_by', $this->model->columns())) $input['updated_by'] = $this->uid();

            if ($id) {
                $type = 'update';
                $data = $this->findById($id);
                if (!$data) throw new Exception('Not found');
                $data->update($input);
            } else $data = $this->model->create($input);

            if ($this->user() && $this->withActivities) {
                activity($this->moduleName ?? '')
                    // ->setDescription('lorem ipsum dolor sit amet')
                    ->setProperties([ 'color' => '#3B82F6', 'type' => $type ])
                    ->setCauser($this->user())
                    ->addSubject($data)
                    ->save();
            }

            return $data;
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function count($where = null) {
        $query = $this->model->query();
        if ($where) $query = $query->where($where);
        return $query->count();
    }

    public function sum($field, $where = null) {
        $query = $this->model->query();
        if ($where) $query = $query->where($where);
        return $query->sum($field);
    }

    public function all($where = null, $with = []): Collection {
        $data = $this->model->query()->with($with);
        if ($where) $data->where($where);
        if ($this->orderBy && is_array($this->orderBy)) {
            foreach($this->orderBy as $field => $type) {
                $data = $data->orderBy($field, $type);
            }
        }
        $data = $this->searchable($data);
        return $data->get();
    }

    public function setOrderBy($orderBy) {
        $this->orderBy = $orderBy;
    }

    public function validateColumns(string $name) {
        $columns = method_exists($this->model, 'columns') ? $this->model->columns() : [];
        $column = str_replace('!', '', $name);

        return in_array($column, $columns) ? $column : null;
    }

    public function setPayload($payload) {
        $this->payload = $payload;
    }

    public function searchable(Builder $data): Builder {
        $payload = $this->payload;

        $search = [];
        if (isset($payload['search'])) $search = extract_params($payload['search']);

        $order = [];
        if (isset($payload['order'])) $order = extract_params($payload['order']);

        $searchLike = null;
        if (isset($payload['search_like'])) $searchLike = extract_params_like($payload['search_like']);

        if (count($search)) {
            foreach ($search as $params) {
                if ($column = $this->validateColumns($params['key'])) {
                    $value = $params['value'];
                    $importantCheck = explode('!', $params['key']);

                    if ($value == 'not_null') $data = $data->whereNotNull($column);
                    else if ($value == 'is_null') $data = $data->whereNull($column);
                    else if (isset($importantCheck[1])) $data = $data->where($column, $value);
                    else $data = $data->whereRaw($column.' = ? OR '.$column.' like ?', [$value, '%'.$value.'%']);
                }
            }
        }

        if (count($order)) {
            $available = ['asc', 'desc'];
            foreach($order as $r) {
                $column = $r['key'];
                if (method_exists('columns', $this->model) && in_array($column, $this->model->columns())) {
                    $value = isset($r['value']) && in_array($r['value'], $available) ? $r['value'] : 'asc';
                    $data->orderBy($column, $value);
                }
            }
        }

        if ($searchLike) {
            $data = $data->where(function($q) use($searchLike) {
                $index = 0;
                foreach($searchLike->columns as $r) {
                    if (method_exists('columns', $this->model) && in_array($r, $this->model->columns())) {
                        if ($index === 0) $q->where($r, 'LIKE', "%{$searchLike->value}%");
                        else $q->orWhere($r, 'LIKE', "%{$searchLike->value}%");
                        $index++;
                    }
                }
            });
        }

        return $data;
    }

    public function paginate($limit = 10, $where = null, $with = [], $whereHas = null) {
        $data = $this->model->query()->with($with);
        if ($where) $data = $data->where($where);
        if (isset($whereHas) && is_array($whereHas) && count($whereHas)) {
            foreach($whereHas as $relateable => $func) $data = $data->whereHas($relateable, $func);
        }

        if ($this->orderBy && is_array($this->orderBy)) {
            foreach($this->orderBy as $field => $type) {
                $data = $data->orderBy($field, $type);
            }
        }

        $data = $this->searchable($data);
        return $data->paginate($limit);
    }

    public function findById($id, $where = null) {
        $data = $this->model->query();
        if ($where) $data->where($where);
        return $data->find($id);
    }

    public function delete(...$id) {
        if ($this->user() && $this->withActivities) {
            $activity = activity($this->moduleName ?? '')
                // ->setDescription('lorem ipsum dolor sit amet')
                ->setProperties([ 'color' => '#EF4444', 'type' => 'delete' ])
                ->setCauser($this->user());
        }

        if (is_array($id)) {
            $query = $this->model->whereIn('id', $id);
            if ($this->user() && $this->withActivities) {
                foreach($query->get() as $r) $activity->addSubject($r);
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
