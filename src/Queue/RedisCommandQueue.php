<?php
/**
 * Created by PhpStorm.
 * User: mikhail
 * Date: 2/26/19
 * Time: 6:31 PM
 */

namespace Zanevsky\Yii2Helpers\Queue;

use yii\queue\redis\Command;

/**
 * Class    RedisCommandQueue
 * @package Zanevsky\Yii2Helpers\Queue
 * @author  Yarmaliuk Mikhail
 * @version 1.0
 */
class RedisCommandQueue extends Command
{
    /**
     * @var string
     */
    public $projectId = null;

    /**
     * @inheritdoc
     */
    public function options($actionID)
    {
        $options   = parent::options($actionID);
        $options[] = 'projectId';

        return $options;
    }

    /**
     * @inheritdoc
     */
    public function runAction($id, $params = [])
    {
        // Add project param (will added to exec command queue)
        if ($id === 'listen') {
            $params['projectId'] = '';
        }

        return parent::runAction($id, $params);
    }

    /**
     * @inheritdoc
     */
    protected function handleMessage($id, $message, $ttr, $attempt)
    {
        /** @var ProjectJob $wrapJob */
        $wrapJob = $this->queue->serializer->unserialize($message);

        // Delete unknown job
        if (!$wrapJob instanceof ProjectJob) {
            return $this->queue->remove($id);
        }

        // Set project id for job executor (pass in command line)
        $this->projectId = $wrapJob->projectId;

        // Unwrap project job
        $message = $this->queue->serializer->serialize($wrapJob->job);

        return parent::handleMessage($id, $message, $ttr, $attempt);
    }
}