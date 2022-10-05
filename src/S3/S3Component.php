<?php
/**
 * Created by PhpStorm.
 * User: mikhail
 * Date: 3/13/19
 * Time: 1:15 PM
 */

namespace Zanevsky\Yii2Helpers\S3;

use frostealth\yii2\aws\s3\Service;

/**
 * Class    S3Component
 * @package Zanevsky\Yii2Helpers\S3
 * @author  Yarmaliuk Mikhail
 * @version 1.0
 */
class S3Component extends Service
{
    /**
     * CDN domains for bucket
     *
     * @var array
     */
    public $cdns = [];

    /**
     * Get file url with cdn domain
     *
     * @return string
     */
    public function getCdnUrl(string $fileName): string
    {
        if (empty($this->cdns)) {
            return $fileName;
        }

        return $this->cdns[mt_rand(0, count($this->cdns) - 1)] . '/' . $fileName;
    }
}