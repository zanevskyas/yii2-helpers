<?php
/**
 * Created by PhpStorm.
 * User: mikhail
 * Date: 04.10.2018
 * Time: 17:13
 */

namespace Kakadu\Yii2Helpers\Queue\Job;

use yii\base\Exception;
use yii\queue\RetryableJobInterface;

/**
 * Class    BaseRetryableJob
 * @package Kakadu\Yii2Helpers\Queue\Job
 * @author  Yarmaliuk Mikhail
 * @version 1.0
 */
abstract class BaseRetryableJob extends BaseJobObject implements RetryableJobInterface
{
    /**
     * @var int
     */
    protected $maxAttempts = 500;

    /**
     * @var int
     */
    protected $maxTtl = 60 * 10;

    /**
     * @inheritdoc
     */
    public function getTtr(): int
    {
        return $this->maxTtl;
    }

    /**
     * @inheritdoc
     */
    public function canRetry($attempt, $error): bool
    {
        return ($attempt < $this->maxAttempts) && ($error instanceof Exception);
    }
}