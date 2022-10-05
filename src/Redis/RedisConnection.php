<?php
/**
 * Created by mikhail.
 * Date: 2019-08-13
 * Time: 11:50
 */

namespace Zanevsky\Yii2Helpers\Redis;

use yii\helpers\ArrayHelper;
use yii\redis\Connection;

/**
 * Class    RedisConnection
 *
 * @package Zanevsky\Yii2Helpers\Redis
 * @author  Yarmaliuk Mikhail
 * @version 1.0
 */
class RedisConnection extends Connection
{
    /**
     * @var string srv dns record
     */
    public $srv;

    /**
     * @inheritDoc
     */
    public function init(): void
    {
        if ($this->srv) {
            $srv = str_replace('.srv', '', $this->srv);
            $srvRecords = dns_get_record($srv, DNS_SRV);

            if (!empty($srvRecords)) {
                ArrayHelper::multisort($srvRecords, ['pri', 'weight'], [SORT_ASC, SORT_ASC]);

                $firstSrv = $srvRecords[0];

                $this->hostname = $firstSrv['target'] ?? $this->host;
                $this->port     = $firstSrv['port'] ?? $this->port;
            }
        }

        parent::init();
    }
}
