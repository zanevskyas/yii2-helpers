<?php
/**
 * Created by PhpStorm.
 * User: Yarmaliuk Mikhail
 * Date: 08.01.2018
 * Time: 16:58
 */

namespace Kakadu\Yii2Helpers\Queue\Job;

use yii\base\BaseObject;
use yii\di\NotInstantiableException;
use yii\queue\ExecEvent;
use Kakadu\Yii2Helpers\Queue\IProjectJob;
use yii\queue\JobInterface;

/**
 * Class    BaseJobObject
 * @package Kakadu\Yii2Helpers\Queue\Job
 * @author  Yarmaliuk Mikhail
 * @version 1.0
 */
abstract class BaseJobObject extends BaseObject implements IProjectJob, JobInterface
{
    /**
     * @var string
     */
    private $_originClassName;

    /**
     * Run this task again
     *
     * @param ExecEvent $event
     *
     * @return void
     */
    public static function handleError(ExecEvent $event)
    {
    }

    /**
     * @inheritdoc
     */
    public function getProjectInstance()
    {
        $interface = $this->getJobInterface();

        if (!$interface)
            return null;

        /** @var BaseJobObject $objClass */
        try {
            $objClass = \Yii::$container->get($interface, get_object_vars($this));

            $objClass->setOriginClassName(get_class($this));
        } catch (NotInstantiableException $e) {
            return null;
        }

        return $objClass;
    }

    /**
     * @inheritdoc
     */
    public function getJobInterface(): ?string
    {
        return null;
    }

    /**
     * Get origin class name
     *
     * @return null|string
     */
    public function getOriginClassName(): ?string
    {
        return $this->_originClassName;
    }

    /**
     * Set origin class name
     *
     * @param string $originClassName
     */
    public function setOriginClassName(string $originClassName): void
    {
        $this->_originClassName = $originClassName;
    }
}
