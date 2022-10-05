<?php
/**
 * Created by mikhail.
 * Date: 2019-07-30
 * Time: 11:49
 */

namespace Kakadu\Yii2Helpers\ActiveRecord;

use yii\db\Connection;
use yii\helpers\ArrayHelper;

/**
 * Class    MysqlConnection
 *
 * @package common
 * @author  Yarmaliuk Mikhail
 * @version 1.0
 */
class MysqlConnection extends Connection
{
    /**
     * @var string
     */
    public $host;

    /**
     * @var string
     */
    public $port;

    /**
     * @var string
     */
    public $database;

    /**
     * @var string srv dns record
     */
    public $srv;

    /**
     * @var string
     */
    public $slavesBalancers;

    /**
     * @inheritDoc
     */
    public function init(): void
    {
        if ($this->srv) {
            $srvRecords = dns_get_record($this->srv, DNS_SRV);

            if (!empty($srvRecords)) {
                ArrayHelper::multisort($srvRecords, ['pri', 'weight'], [SORT_ASC, SORT_ASC]);

                $firstSrv = $srvRecords[0];

                $this->host = $firstSrv['target'] ?? $this->host;
                $this->port = $firstSrv['port'] ?? $this->port;
            }
        }

        if (!empty($this->slavesBalancers)) {
            $slaveBalancers = is_string($this->slavesBalancers)
                ? [$this->slavesBalancers]
                : $this->slavesBalancers;

            foreach ($slaveBalancers as $slaveBalancer) {
                $this->slaves[] = [
                    'dsn' => "mysql:host={$slaveBalancer};dbname={$this->database}",
                ];
            }
        }

        if (empty($this->dsn) && !empty($this->host)) {
            $this->dsn = "mysql:host={$this->host}:{$this->port};dbname={$this->database}";
        }

        parent::init();
    }
}
