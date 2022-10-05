<?php
/**
 * Created by PhpStorm.
 * User: mikhail
 * Date: 1/9/19
 * Time: 3:29 PM
 */

namespace Zanevsky\Yii2Helpers\Traits;

use yii\db\ActiveQuery;

/**
 * Trait    DataProviderTrait
 *
 * @package Zanevsky\Yii2Helpers\Traits
 * @author  Yarmaliuk Mikhail
 * @version 1.0
 */
trait DataProviderTrait
{
    /**
     * Clear model rules
     * Remove required, default, relation rules
     *
     * @param array $rules
     *
     * @return array
     */
    protected function clearModelRules(array $rules): array
    {
        $keys = [
            'required', 'default', 'relation', 'unique', 'custom',
        ];

        foreach ($rules as $name => $rule) {
            $count = 0;
            str_ireplace($keys, '', $name, $count);

            if ($count > 0) {
                unset($rules[$name]);
            }
        }

        return $rules;
    }

    /**
     * Filter by timestamp
     *
     * @param ActiveQuery $query
     * @param string      $attribute
     *
     * @return void
     */
    protected function andFilterByTimestamp(ActiveQuery $query, string $attribute): void
    {
        $created    = explode('-', $this->$attribute);
        $created[0] = !empty($created[0]) ? strtotime(trim($created[0])) : null;

        if (\count($created) === 2) {
            $created[1] = !empty($created[1]) ? strtotime(trim($created[1])) : null;
            $query->andFilterWhere(['BETWEEN', $attribute, $created[0], $created[1]]);
        } else {
            $query->andFilterWhere([$attribute => $created[0]]);
        }
    }
}
