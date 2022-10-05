<?php
/**
 * Created by Yii2 Gii.
 * User: Yarmaliuk Mikhail
 * Date: 12.12.2017
 * Time: 09:20
 */

namespace Kakadu\Yii2Helpers\Logs\Models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * Class    Log
 * @package Kakadu\Yii2Helpers\Logs\Models
 * @author  Yarmaliuk Mikhail
 * @version 1.0
 *
 * This is the model class for table "{{%logs}}".
 *
 * @property int    $id
 * @property string $project
 * @property int    $level
 * @property int    $category
 * @property int    $log_time
 * @property string $prefix
 * @property int    $message
 */
class Log extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName(): string
    {
        return '{{%logs}}';
    }

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return [
            ['project', 'string', 'max' => 20],
            [['level'], 'integer'],
            [['log_time'], 'number'],
            [['category'], 'string', 'max' => 255],
            [['prefix', 'message'], 'string'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function behaviors(): array
    {
        return [
            [
                'class'              => TimestampBehavior::class,
                'createdAtAttribute' => 'log_time',
                'updatedAtAttribute' => false,
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels(): array
    {
        return [
            'id'       => Yii::t('app', 'ID'),
            'project'  => Yii::t('app', 'Project'),
            'level'    => Yii::t('app', 'Level'),
            'category' => Yii::t('app', 'Category'),
            'prefix'   => Yii::t('app', 'Prefix'),
            'message'  => Yii::t('app', 'Message'),
            'log_time' => Yii::t('app', 'Time'),
        ];
    }
}
