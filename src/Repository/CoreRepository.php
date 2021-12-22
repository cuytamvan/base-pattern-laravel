<?php

namespace Cuytamvan\BasePattern\Repository;

use Illuminate\Database\Eloquent\Collection;

use Exception;

abstract class CoreRepository {
    protected $guard = null;
    protected $withActivities = true;
    protected $orderBy = null;

    public function user() {
        return auth($this->guard)->user();
    }

    public function uid() {
        return auth($this->guard)->id();
    }

    public function store($id = null, $input = []) {
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
        return $data->get();
    }

    public function setOrderBy($orderBy) {
        $this->orderBy = $orderBy;
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

        return $data->paginate($limit);
    }

    public function findById($id, $where = null) {
        $data = $this->model->query();
        if ($where) $data->where($where);
        return $data->find($id);
    }

    public function delete($id) {
        if ($this->user() && $this->withActivities) {
            $activity = activity('module')
                // ->setDescription('lorem ipsum dolor sit amet')
                ->setProperties([ 'color' => '#EF4444', 'type' => 'delete' ])
                ->setCauser($this->user());
        }

        if (is_array($id)) {
            if ($this->user() && $this->withActivities) $activity->save();
            $query = $this->model->whereIn('id', $id);
            if (in_array('deleted_by', $this->model->columns())) $query->update(['deleted_by' => $this->uid()]);
            return $this->model->whereIn('id', $id)->delete();
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
