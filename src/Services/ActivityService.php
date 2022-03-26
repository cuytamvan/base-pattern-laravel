<?php

namespace Cuytamvan\BasePattern\Services;

use Carbon\Carbon;
use Cuytamvan\BasePattern\Model\Activity;

use Exception;

class ActivityService
{
    protected $model;

    protected $logName = null;
    protected $properties = null;
    protected $description = null;
    protected $causerModel = null;
    protected $causerId = null;
    protected $subjects = [];

    public function __construct($logName = null)
    {
        $this->logName = $logName;
        $this->model = new Activity;
    }

    public function setDescription($text = null)
    {
        $this->description = $text;
        return $this;
    }

    public function setProperties($properties = [])
    {
        $this->properties = json_encode($properties);
        return $this;
    }

    public function setCauser($model)
    {
        $className = get_class($model);
        $keyName = $model->getKeyName();

        $this->causerModel = $className;
        $this->causerId = $model->{$keyName};
        return $this;
    }

    public function addSubject($model)
    {
        $className = get_class($model);
        $keyName = $model->getKeyName();

        $this->subjects[] = [
            'ref_model' => $className,
            'ref_id' => $model->{$keyName},
        ];
        return $this;
    }

    public function save(): bool
    {
        try {
            $now = Carbon::now();
            $input = [];

            if (count($this->subjects)) {
                foreach ($this->subjects as $r) {
                    $dummy = [
                        'log_name' => $this->logName,
                        'description' => $this->description,
                        'causer_model' => $this->causerModel,
                        'causer_id' => $this->causerId,
                        'properties' => $this->properties,
                        'ref_model' => $r['ref_model'],
                        'ref_id' => $r['ref_id'],
                        'created_at' => $now,
                    ];
                    $input[] = $dummy;
                }
            } else {
                $input = [
                    'log_name' => $this->logName,
                    'description' => $this->description,
                    'causer_model' => $this->causerModel,
                    'causer_id' => $this->causerId,
                    'properties' => $this->properties,
                    'created_at' => $now,
                ];
            }
            return $this->model->insert($input);
        } catch (Exception $e) {
            return false;
        }
    }
}
