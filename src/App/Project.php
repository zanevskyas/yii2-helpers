<?php
/**
 * Created by PhpStorm.
 * User: mikhail
 * Date: 2/22/19
 * Time: 2:01 PM
 */

namespace Zanevsky\Yii2Helpers\App;

use Yii;
use yii\base\Component;
use yii\helpers\ArrayHelper;

/**
 * Class    Project
 *
 * @package Zanevsky\Yii2Helpers\App
 * @author  Yarmaliuk Mikhail
 * @version 1.0
 */
abstract class Project extends Component
{
    const DEFAULT = 'default';

    /**
     * @var array list projects
     */
    protected static $projects = [];

    /**
     * @var string default project
     */
    protected static $projectId = self::DEFAULT;

    /**
     * @var string
     */
    protected static $apiDomain = '';

    /**
     * @var array
     */
    protected $params;

    /**
     * Get project special settings
     *
     * @param string $projectId
     *
     * @return array
     */
    public static function getProjectConfig(string $projectId = null): array
    {
        $defaultParams = static::getDefaultParams();
        $domain        = $_SERVER['HTTP_HOST'] ?? null;
        $projectId     = $projectId ?? $defaultParams['domainsToProject'][$domain] ?? null;

        if (!$projectId && defined('YII_CONSOLE')) {
            $projectId = static::getConsoleProject();

            if ($projectId === self::DEFAULT && !empty($defaultParams['defaultProject'])) {
                $projectId = $defaultParams['defaultProject'];
            }
        }

        if (!$projectId) {
            echo 'Проект не найден, неизвестный домен.';
            exit;
        }

        $conf = [];

        if ($projectId !== self::DEFAULT) {
            $mainConfProject = Yii::getAlias("@common/config/projects/$projectId/main.php");

            if (!file_exists($mainConfProject)) {
                echo 'Для проекта "' . $projectId . '" не создан файл конфигурации';
                exit;
            }

            $envPath = Yii::getAlias("@common/config/projects/$projectId");

            if (file_exists("$envPath/.env")) {
                $dotenv = \Dotenv\Dotenv::create($envPath);
                $dotenv->overload();
            }

            $conf = require $mainConfProject;
        }

        static::$projectId = $projectId;
        static::$apiDomain = array_search($projectId, $defaultParams['domainsToProject']);

        $_SERVER['PROJECT_ID'] = static::$projectId;

        return $conf;
    }

    /**
     * Get default app params (not depend on project)
     *
     * @return array
     */
    private static function getDefaultParams(): array
    {
        $commonParams = require Yii::getAlias('@common/config/params.php');
        $localParams  = require Yii::getAlias('@common/config/params-local.php');

        $result = ArrayHelper::merge($commonParams, $localParams);

        if (!empty($localParams['domainsToProject'])) {
            $result['domainsToProject'] = $localParams['domainsToProject'];
        }

        return $result;
    }

    /**
     * Get console project name
     *
     * @return array
     */
    private static function getConsoleProject(): ?string
    {
        $projectId = null;

        foreach ($_SERVER['argv'] ?? [] as $key => $param) {
            if (strpos($param, '--projectId') !== false) {
                if (($pos = strrpos($param, '=')) !== false) {
                    $projectId = substr($param, $pos + 1);
                    unset($_SERVER['argv'][$key]);

                } else {
                    $projectId = $_SERVER['argv'][$key + 1] ?? null;
                    unset($_SERVER['argv'][$key]);
                    unset($_SERVER['argv'][$key + 1]);
                }

                // Reset keys
                $_SERVER['argv'] = array_values($_SERVER['argv']);
                break;
            }
        }

        return !empty($projectId) && in_array($projectId, static::$projects) ?
            $projectId : static::$projectId;
    }

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        $this->params = Yii::$app->params['projectSettings'] ?? [];
    }

    /**
     * Is default project
     *
     * @return bool
     */
    public function isDefault(): bool
    {
        return static::$projectId === self::DEFAULT;
    }

    /**
     * Get project id
     *
     * @return string
     */
    public function getId(): string
    {
        return static::$projectId;
    }

    /**
     * Get cors domains for project
     *
     * @return array
     */
    public function getCorsDomains(): array
    {
        $corsDomains   = $this->params['corsDomains'] ?? [];
        $corsDomains[] = $this->getCommonDomain();

        return $corsDomains;
    }

    /**
     * Get common domain
     *
     * @return string
     */
    public function getCommonDomain(): string
    {
        if (empty($this->getApiDomain())) {
            return '';
        }

        $subDomains      = explode('.', $this->getApiDomain());
        $subDomainsCount = count($subDomains);
        $scheme          = YII_ENV_PROD
            ? 'https'
            : ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? $_SERVER['REQUEST_SCHEME'] ?? 'http');

        return "$scheme://{$subDomains[$subDomainsCount-2]}.{$subDomains[$subDomainsCount-1]}";
    }

    /**
     * Get api domain
     *
     * @return string
     */
    public function getApiDomain(): string
    {
        return static::$apiDomain;
    }
}
