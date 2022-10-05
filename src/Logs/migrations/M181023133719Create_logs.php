<?php
/**
 * Created by Yii2.
 * User: Yarmaliuk Mikhail
 * Date: 23.10.2018
 * Time: 13:37
 */

namespace Kakadu\Yii2Helpers\Logs\migrations;

use yii\db\Migration;

/**
 * Class    M181023133719Create_logs
 * @package Kakadu\Yii2Helpers\Logs\migrations
 * @author  Yarmaliuk Mikhail
 * @version 1.0
 */
class M181023133719Create_logs extends Migration
{
    /**
     * @var string
     */
    public $tableName = '{{%logs}}';

    /**
     * @inheritdoc
     */
    public function safeUp(): void
    {
        $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';

        $this->createTable($this->tableName, [
            'id'       => $this->bigPrimaryKey(),
            'project'  => $this->string(20),
            'level'    => $this->integer(),
            'category' => $this->string(),
            'log_time' => $this->double(),
            'prefix'   => $this->text(),
            'message'  => $this->text(),
        ], $tableOptions);

        $this->createIndex('idx_log_project', $this->tableName, 'project');
        $this->createIndex('idx_log_level', $this->tableName, 'level');
        $this->createIndex('idx_log_category', $this->tableName, 'category');
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): void
    {
        $this->dropTable($this->tableName);
    }
}