<?php
/**
 * Created by PhpStorm.
 * User: Yarmaliuk Mikhail
 * Date: 07.02.2017
 * Time: 10:59
 */

namespace Zanevsky\Yii2Helpers\ActiveRecord;

use Yii;
use yii\base\InvalidConfigException;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\db\ActiveRecordInterface;
use MP\Services\ImplementServices;
use yii\db\Exception;
use Kakadu\Yii2BaseHelpers\BaseHelper;

/**
 * Class    ARHelper
 * @package Zanevsky\Yii2Helpers\ActiveRecord
 * @version 1.0
 */
abstract class ARHelper extends ActiveRecord
{
    use ImplementServices;

    const PARAM_INCREMENT      = 'increment++';
    const PARAM_DECREMENT      = 'decrement--';
    const PARAM_DECREMENT_ZERO = 'decrement_zero--';

    /**
     * Automatically convert associative array attributes to json
     *
     * @var bool
     */
    protected $convertArrayAttributes = true;

    /**
     * Array attributes in model
     *
     * @return array
     */
    public static function arrayAttributes()
    {
        return [];
    }

    /**
     * Set model attributes
     * This method provide return self
     *
     * @param array $values
     * @param bool  $safeOnly
     *
     * @return self
     */
    public function setModelAttributes(array $values, bool $safeOnly = true): self
    {
        $this->setAttributes($values, $safeOnly);

        return $this;
    }

    /**
     * Set only empty model attributes
     *
     * @param array $values
     * @param bool  $safeOnly
     *
     * @return ARHelper
     */
    public function setEmptyModelAttributes(array $values, bool $safeOnly = true): self
    {
        foreach ($values as $attribute => $value) {
            if (empty($this->$attribute)) {
                $this->setAttributes([
                    $attribute => $value,
                ], $safeOnly);
            }
        }

        return $this;
    }

    /**
     * Get model param
     *
     * Example: db_field:key1.key2 and etc...
     *
     * @param string     $param
     * @param mixed|null $default
     *
     * @return mixed
     */
    public function getParam(string $param, $default = null)
    {
        list($field, $params) = explode(':', $param);
        $path = explode('.', trim($params));

        if (!empty($param) && $this->hasAttribute($field)) {
            $value = $this->{$field};

            foreach ($path as $item) {
                if (isset($value[$item])) {
                    $value = $value[$item];
                } else {
                    return $default;
                }
            }

            return $value;
        }

        return $default;
    }

    /**
     * Set model param
     *
     * Example: db_field:key1.key2 and etc...
     *
     * @param string $param
     * @param mixed  $value
     *
     * @return ARHelper
     */
    public function setParam(string $param, $value): self
    {
        list($field, $params) = explode(':', $param);
        $path = explode('.', trim($params));

        if (!empty($param) && $this->hasAttribute($field)) {
            $tmp_param = $this->{$field};
            $current   = &$tmp_param;

            foreach ($path as $item) {
                if (!isset($current[$item])) {
                    $current[$item] = null;
                }

                $current = &$current[$item];
            }

            if ($value === self::PARAM_INCREMENT) {
                $current = is_numeric($current) ? ++$current : 1;
            } elseif ($value === self::PARAM_DECREMENT) {
                $current = is_numeric($current) ? --$current : -1;
            } elseif ($value === self::PARAM_DECREMENT_ZERO) {
                $current = is_numeric($current) && $current > 0 ? --$current : $current;
            } else {
                $current = $value;
            }

            $this->{$field} = $tmp_param;
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function save($runValidation = true, $attributeNames = null)
    {
        $json_attributes = [];

        if ($this->convertArrayAttributes) {
            $attributes = $this->getAttributes(static::arrayAttributes());

            if (empty($attributes)) {
                $attributes = $this->attributes;
            }

            if (!empty($attributes)) {
                foreach ($attributes as $attribute => $value) {
                    if (is_array($value)) {
                        if (empty($value)) {
                            $this->$attribute = '';
                        } else {
                            $this->$attribute = json_encode($this->$attribute, JSON_UNESCAPED_UNICODE);
                        }
                        $json_attributes[] = $attribute;
                    }
                }
            }
        }

        $result = parent::save($runValidation, $attributeNames);

        if (!empty($json_attributes)) {
            foreach ($json_attributes as $json_attribute) {
                $this->$json_attribute = json_decode($this->$json_attribute, true);
            }
        }

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function afterFind()
    {
        if ($this->convertArrayAttributes) {
            $attributes = $this->getAttributes(static::arrayAttributes());

            if (empty($attributes)) {
                $attributes = $this->attributes;
            }

            if (!empty($attributes)) {
                foreach ($attributes as $attribute => $value) {
                    if (is_string($value)) {
                        $result = json_decode($this->$attribute, true);

                        if (json_last_error() === JSON_ERROR_NONE && is_array($result)) {
                            $this->$attribute = $result;
                        } elseif (in_array($attribute, static::arrayAttributes())) {
                            $this->$attribute = [];
                        }
                    } elseif (is_null($value) && in_array($attribute, static::arrayAttributes())) {
                        $this->$attribute = [];
                    }
                }
            }
        }

        parent::afterFind();
    }

    /**
     * Delete all models
     *
     * @param ActiveRecordInterface $models
     *
     * @return int
     * @throws
     */
    public function deleteModels(array $models): int
    {
        $count = 0;

        if (!empty($models)) {
            foreach ($models as $model) {
                $model->delete() ? $count++ : null;
            }
        }

        return $count;
    }

    /**
     * Get first message error
     *
     * @param string $defaultMessage
     *
     * @return string
     */
    public function getFirstTextError(string $defaultMessage = 'Unknown error'): ?string
    {
        return array_values($this->getFirstErrors())[0] ?? $defaultMessage;
    }

    /**
     * Get random models
     *
     * @param ActiveQuery $query
     * @param int         $num number random models
     *
     * @return array|ActiveRecord[]
     */
    public static function findRandom(ActiveQuery $query, int $num = 1): array
    {
        $count_models = (int) $query->count();

        if ($count_models === 0 || $num === 0) {
            return [];
        }

        if ($count_models < $num) {
            $num = $count_models;
        }

        $count_models = $count_models > 1 ? ($count_models - 1) : $count_models;

        $rand_arr = BaseHelper::getRandomArray(0, $count_models, $num);

        $models = [];

        if (isset($rand_arr[0])) {
            $models = $query->limit(1)->offset($rand_arr[0]);

            if ($num > 1) {
                $base_model = clone $models;

                for ($i = 1; $i <= $num; $i++) {
                    $models->union((clone $base_model)->offset((isset($rand_arr[$i]) ? $rand_arr[$i] : $rand_arr[$i - 1])));
                }
            }

            $models = $models->all();
        }

        return $models;
    }

    /**
     * Get model form name
     *
     * @return string
     */
    public static function formNameModel(): string
    {
        return (new static())->formName();
    }

    /**
     * Get form name attribute model
     *
     * @param string $attribute
     *
     * @return string
     */
    public static function formNameAttribute(string $attribute): string
    {
        return self::formNameModel() . "[$attribute]";
    }

    /**
     * Get test model
     *
     * @return self
     * @throws InvalidConfigException
     */
    public static function getTestModel(): self
    {
        $model      = new static();
        $attributes = $model->attributeLabels();

        if (!empty($attributes)) {
            foreach ($attributes as $attribute => &$value) {
                $types = self::getTableSchema()->getColumn($attribute);
                if (!empty($types)) {
                    settype($value, $types->phpType);
                }
            }
        }

        $model->load($attributes, '');

        return $model;
    }

    /**
     * Insert or update models values
     *
     * Example "$values" parameter:
     * [
     *      ['attribute' => 'value'], // 1 ROW
     *      OR
     *      ['attribute' => 'value'], ['attribute2' => 'value', 'attribute3' => 'value3'] // 2 ROWS
     * ]
     *
     * Example short syntax for 1 ROW "$values":
     * ['attribute' => 'value', ... attributes]
     *
     * @param array $values
     * @param array $update_values
     *
     * @return bool
     * @throws Exception
     */
    public static function insertOrUpdate(array $values, array $update_values = []): bool
    {
        if (!empty($values)) {
            // Short syntax
            if (empty($values[0])) {
                return self::insertOrUpdate([$values], !empty($update_values) ? [$update_values] : []);
            }

            // Run bulk insert or update if custom update value exist
            if (count($values) > 1 && !empty($update_values)) {
                $bulk_result = true;

                foreach ($values as $key => $value) {
                    $bulk_result = self::insertOrUpdate([$value], [$update_values[$key] ?? []]) ? ($bulk_result ? : false) : false;
                }

                return $bulk_result;
            }

            $primary_keys  = self::primaryKey();
            $insert_fields = (new static())->attributes();
            $update_fields = [];

            if (!empty($insert_fields)) {
                foreach ($insert_fields as $insert_field) {
                    if (!in_array($insert_field, $primary_keys)) {
                        // Custom or default update value
                        $update_value = $update_values[0][$insert_field] ?? 'VALUES(' . Yii::$app->db->quoteColumnName($insert_field) . ')';

                        if (empty($update_values) || !empty($update_values[0][$insert_field])) {
                            $update_fields[] = Yii::$app->db->quoteColumnName($insert_field) . ' = ' . $update_value;
                        }
                    }
                }

                $update_fields = implode(', ', $update_fields);
            } else {
                return false;
            }

            $rows = [];

            foreach ($values as $model_values) {
                $tmp_model = new static($model_values);
                $tmp_model->validate(); // set default values
                $tmp_row = $tmp_model->attributes;

                if (!empty($tmp_row)) {
                    foreach ($tmp_row as &$value) {
                        if (is_array($value)) {
                            $value = json_encode($value, JSON_UNESCAPED_UNICODE);
                        }
                    }
                }

                $rows[] = $tmp_row;
            }

            $query = Yii::$app->db->createCommand()->batchInsert(self::tableName(), $insert_fields, $rows)->getRawSql();
            $query .= " ON DUPLICATE KEY UPDATE $update_fields;";

            return (bool) Yii::$app->db->createCommand($query)->execute();
        }

        return false;
    }

    /**
     * Get dirty attributes not strictly
     *
     * @param array $names
     *
     * @return array
     */
    public function getDirtyAttributesNotStrictly(array $names = null): array
    {
        $dirty_attributes = $this->getDirtyAttributes($names);

        if (!empty($dirty_attributes)) {
            foreach ($dirty_attributes as $dirty_attribute => $value) {
                if (is_array($this->$dirty_attribute) && json_encode($this->$dirty_attribute) == $this->getOldAttribute($dirty_attribute)) {
                    unset($dirty_attributes[$dirty_attribute]);
                    continue;
                }

                if ($this->$dirty_attribute == $this->getOldAttribute($dirty_attribute)) {
                    unset($dirty_attributes[$dirty_attribute]);
                }
            }
        }

        return $dirty_attributes;
    }

    /**
     * Get model attributes wrap in 'ANY_VALUE()'
     *
     * @param array  $attributes
     * @param string $tablePrefix
     * @param array  $exclude
     *
     * @return array
     */
    public static function queryValues(array $attributes = [], string $tablePrefix = null, array $exclude = []): array
    {
        if (empty($attributes)) {
            $attributes = (new static())->attributes();
        }

        if ($tablePrefix) {
            $tablePrefix = $tablePrefix . '.';
        }

        $result = [];

        foreach ($attributes as $attribute) {
            if (!in_array($attribute, $exclude)) {
                $result[] = "ANY_VALUE($tablePrefix$attribute) as $attribute";
            }
        }

        return $result;
    }
}
