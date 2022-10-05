<?php
/**
 * Created by PhpStorm.
 * User: mikhail
 * Date: 3/12/19
 * Time: 4:15 PM
 */

namespace Zanevsky\Yii2Helpers\Queue;

/**
 * Interface IProjectJob
 * @package  Zanevsky\Yii2Helpers\Queue
 * @author   Yarmaliuk Mikhail
 * @version  1.0
 *
 * Need for convert class to project class
 */
interface IProjectJob
{
    /**
     * Get project object by interface name
     *
     * @return static|null
     */
    public function getProjectInstance();

    /**
     * Get project job interface
     *
     * @return string|null
     */
    public function getJobInterface(): ?string;

    /**
     * Get origin class name
     *
     * @return null|string
     */
    public function getOriginClassName(): ?string;
}