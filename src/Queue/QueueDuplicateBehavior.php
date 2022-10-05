<?php
/**
 * Created by PhpStorm.
 * User: mikhail
 * Date: 3/13/19
 * Time: 9:59 AM
 */

namespace Zanevsky\Yii2Helpers\Queue;

use yii\base\Behavior;
use yii\queue\ErrorEvent;
use yii\queue\ExecEvent;
use yii\queue\PushEvent;
use yii\queue\Queue;
use yii\queue\JobInterface;

/**
 * Class    QueueDuplicateBehavior
 * @package Zanevsky\Yii2Helpers\Queue
 * @author  Yarmaliuk Mikhail
 * @version 1.0
 */
class QueueDuplicateBehavior extends Behavior
{
    /**
     * @return array
     */
    public function events(): array
    {
        return [
            Queue::EVENT_BEFORE_PUSH => 'beforePush',
            Queue::EVENT_AFTER_EXEC  => 'afterExec',
            Queue::EVENT_AFTER_ERROR => 'afterError',
        ];
    }

    /**
     * Cancel duplicate job
     * Add job class name to redis for check
     *
     * @param PushEvent $event
     *
     * @return void
     */
    public function beforePush(PushEvent $event): void
    {
        /** @var RedisQueue $sender */
        $sender = $event->sender;
        $field  = $this->getJobClassName($event->job);

        if ($field === null) {
            return;
        }

        $exist = $sender->redis->hget("$sender->channel.unique_jobs", $field);

        if ($exist) {
            $event->handled = true;

            return;
        }

        $sender->redis->hset("$sender->channel.unique_jobs", $field, 1);
    }

    /**
     * Remove job from duplicate check
     *
     * @return void
     */
    public function afterExec(ExecEvent $event): void
    {
        $this->deleteJobFromDuplicateStack($event);
    }

    /**
     * Remove job from duplicate if not retryable
     *
     * @param ErrorEvent|ExecEvent $event
     *
     * @return void
     */
    public function afterError($event): void
    {
        if (!$event->retry) {
            $this->deleteJobFromDuplicateStack($event);
        }
    }

    /**
     * Delete job from duplicate stack
     *
     * @param ErrorEvent|ExecEvent $job
     *
     * @return void
     */
    private function deleteJobFromDuplicateStack($event): void
    {
        /** @var RedisQueue $sender */
        $sender = $event->sender;
        $field  = $this->getJobClassName($event->job);

        if (!empty($field)) {
            $sender->redis->hdel("$sender->channel.unique_jobs", $field);
        }
    }

    /**
     * Get job class name
     *
     * @param JobInterface $job
     *
     * @return null|string
     */
    private function getJobClassName(JobInterface $job): ?string
    {
        // Get origin class name
        if ($job instanceof IProjectJob) {
            if ($className = $job->getOriginClassName()) {
                return $className;
            }
        } elseif ($job instanceof ProjectJob) {
            $job = $job->job;
        }

        if ($this->hasAllowDuplicate($job)) {
            return null;
        }

        return basename(get_class($job));
    }

    /**
     * Is allow duplicate job
     *
     * @param JobInterface $job
     *
     * @return bool
     */
    private function hasAllowDuplicate(JobInterface $job): bool
    {
        return $job instanceof IJobDuplicate;
    }
}