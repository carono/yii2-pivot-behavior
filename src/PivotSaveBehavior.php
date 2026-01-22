<?php


namespace carono\yii2\behaviors;


use yii\db\ActiveRecord;

/**
 * Class PivotSaveBehavior
 *
 * @package app\behaviors
 * @property PivotTrait|ActiveRecord $owner
 */
class PivotSaveBehavior extends \yii\base\Behavior
{
    protected $_pivots;
    protected $_attributes = [];
    public $attribute;
    public $pivotClass;
    public $modelClass;
    public $prepareValues;
    public $deletePivotsBeforeSave = true;
    public $savePivots;
    public $inverseInsertPivot = false;

    public function canSetProperty($name, $checkVars = true)
    {
        return $name == $this->attribute || str_starts_with($name, $this->attribute . '[');
    }

    public function canGetProperty($name, $checkVars = true)
    {
        return $name == $this->attribute || str_starts_with($name, $this->attribute . '[');
    }

    public function __set($name, $value)
    {
        if ($this->canSetProperty($name)) {
            $this->setPivots($value);
        } else {
            parent::__set($name, $value);
        }
    }

    public function __get($name)
    {
        if ($this->canGetProperty($name)) {
            return $this->getStoredPivots($name);
        } else {
            return parent::__get($name, $value);
        }
    }
    
    public function getStoredPivots($attribute)
    {
        if ($attribute == $this->attribute) {
            return $this->_pivots[$this->attribute] ?? [];
        }

        return null;
    }

    /**
     * @return ActiveRecord
     */
    protected function getModelClass()
    {
        return $this->modelClass;
    }

    protected function getPivotCondition($class, $data)
    {
        $condition = [];
        foreach ($class::getTableSchema()->primaryKey as $key) {
            $condition[$key] = $data[$key] ?? null;
        }
        return $condition;
    }

    protected function setPivots($values)
    {
        $class = $this->getModelClass();

        $this->_pivots[$this->attribute] = [];
        if ($this->prepareValues instanceof \Closure) {
            $values = call_user_func($this->prepareValues, (array)$values);
        }
        foreach ((array)$values as $value) {
            if (is_numeric($value)) {
                $this->_pivots[$this->attribute][] = ['model' => $class::findOne($value), 'attributes' => []];
            } elseif ($value instanceof $class) {
                $this->_pivots[$this->attribute][] = ['model' => $value, 'attributes' => []];
            } elseif (is_array($value) && array_key_exists('model', $value)) {
                $this->_pivots[$this->attribute][] = ['model' => $value['model'], 'attributes' => $value['attributes'] ?? []];
            }
        }
        $eventName = $this->owner->isNewRecord ? ActiveRecord::EVENT_AFTER_INSERT : ActiveRecord::EVENT_AFTER_UPDATE;
        if ($this->savePivots instanceof \Closure) {
            call_user_func($this->savePivots, $this, $this->pivotClass, $this->_pivots[$this->attribute]);
        } else {
            $this->owner->on($eventName, [$this, 'savePivots'], $this->attribute);
        }
    }

    public function savePivots($event)
    {
        $pivots = $this->getStoredPivots($event->data);

        if (!method_exists($this->owner, 'addPivot')) {
            throw new \Exception('Class ' . get_class($this->owner) . ' must use carono\yii2migrate\traits\PivotTrait trait');
        }

        if ($this->deletePivotsBeforeSave && $pivots !== null) {
            $this->owner->deletePivots($this->pivotClass);
        }

        foreach ($pivots ?: [] as $pv) {
            if ($this->inverseInsertPivot) {
                $pv['model']->addPivot($this->owner, $this->pivotClass, $pv['attributes']);
            } else {
                $this->owner->addPivot($pv['model'], $this->pivotClass, $pv['attributes']);
            }
        }
    }
}