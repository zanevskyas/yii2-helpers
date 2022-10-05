<?php
/**
 * Created by PhpStorm.
 * User: Yarmaliuk Mikhail
 * Date: 08.01.2018
 * Time: 15:34
 */

namespace Kakadu\Yii2Helpers\Queue;

use yii\queue\ExecEvent;

/**
 * Class    JobEventHandlers
 * @package Kakadu\Yii2Helpers\Queue
 * @author  Yarmaliuk Mikhail
 * @version 1.0
 */
class JobEventHandlers
{
    const ERROR_JOB_METHOD = 'handleError';

    /**
     * Error event queue job
     *
     * @param ExecEvent $event
     *
     * @return void
     */
    public static function onError(ExecEvent $event)
    {
        if (method_exists($event->job, self::ERROR_JOB_METHOD)) {
            call_user_func([$event->job, self::ERROR_JOB_METHOD], $event);
        }
    }
}
