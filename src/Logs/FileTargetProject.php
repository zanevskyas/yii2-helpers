<?php
/**
 * Created by PhpStorm.
 * User: mikhail
 * Date: 2/27/19
 * Time: 12:08 AM
 */

namespace Kakadu\Yii2Helpers\Logs;

use yii\log\FileTarget;

/**
 * Class    FileTargetProject
 * @package Kakadu\Yii2Helpers\Logs
 * @author  Yarmaliuk Mikhail
 * @version 1.0
 */
class FileTargetProject extends FileTarget
{
    /**
     * @var string
     */
    public $fileName = 'app';

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        $projectId = \Yii::$app->project->getId();

        $this->logFile = "@runtime/logs/$projectId-{$this->fileName}.log";

        parent::init();
    }
}