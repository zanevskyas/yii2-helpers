<?php
/**
 * Created by PhpStorm.
 * User: mikhail
 * Date: 2/27/19
 * Time: 12:08 AM
 */

namespace Zanevsky\Yii2Helpers\Logs;

use yii\log\DbTarget;
use yii\di\Instance;
use Zanevsky\Yii2Helpers\App\Project;
use yii\helpers\VarDumper;

/**
 * Class    DbTargetProject
 * @package Zanevsky\Yii2Helpers\Logs
 * @author  Yarmaliuk Mikhail
 * @version 1.0
 */
class DbTargetProject extends DbTarget
{
    /**
     * @var string|Project
     */
    public $project = 'project';

    /**
     * @var string
     */
    public $logTable = 'logs';

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();

        $this->project = Instance::ensure($this->project, Project::class);
    }

    /**
     * @inheritdoc
     */
    public function export()
    {
        if ($this->db->getTransaction()) {
            // create new database connection, if there is an open transaction
            // to ensure insert statement is not affected by a rollback
            $this->db = clone $this->db;
        }

        $tableName = $this->db->quoteTableName($this->logTable);
        $sql       = "INSERT INTO $tableName ([[project]], [[level]], [[category]], [[log_time]], [[prefix]], [[message]])
                VALUES (:project, :level, :category, :log_time, :prefix, :message)";
        $command   = $this->db->createCommand($sql);
        foreach ($this->messages as $message) {
            list($text, $level, $category, $timestamp) = $message;
            if (!is_string($text)) {
                // exceptions may not be serializable if in the call stack somewhere is a Closure
                if ($text instanceof \Throwable || $text instanceof \Exception) {
                    $text = (string) $text;
                } else {
                    $text = VarDumper::export($text);
                }
            }
            if ($command->bindValues([
                    ':project'  => $this->project->getId(),
                    ':level'    => $level,
                    ':category' => $category,
                    ':log_time' => $timestamp,
                    ':prefix'   => $this->getMessagePrefix($message),
                    ':message'  => $text,
                ])->execute() > 0) {
                continue;
            }
            throw new LogRuntimeException('Unable to export log through database!');
        }
    }
}