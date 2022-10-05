<?php
/**
 * Created by PhpStorm.
 * User: mikhail
 * Date: 2/26/19
 * Time: 10:46 PM
 */

namespace Zanevsky\Yii2Helpers\Queue;

use yii\queue\JobInterface;
use yii\base\Model;

/**
 * Class    ProjectJob
 * @package Zanevsky\Yii2Helpers\Queue
 * @author  Yarmaliuk Mikhail
 * @version 1.0
 */
class ProjectJob extends Model implements JobInterface
{
    /**
     * @var string
     */
    public $projectId;

    /**
     * @var object
     */
    public $job;

    /**
     * @inheritdoc
     */
    public function execute($queue)
    {
    }
}