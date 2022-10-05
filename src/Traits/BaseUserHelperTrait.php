<?php
/**
 * Created by PhpStorm.
 * User: Yarmaliuk Mikhail
 * Date: 02.03.2017
 * Time: 11:36
 */

namespace Zanevsky\Yii2Helpers\Traits;

use Yii;
use Zanevsky\Yii2JwtAuth\JwtBearerAuth;
use yii\filters\auth\HttpBearerAuth;

/**
 * Trait    BaseUserHelperTrait
 * @package Zanevsky\Yii2Helpers\Traits
 * @author  Yarmaliuk Mikhail
 * @version 3.0
 *
 * NOTE: add findExternalApi to user model
 */
trait BaseUserHelperTrait
{
    /**
     * Get customer identity
     *
     * @return \yii\web\IdentityInterface|static
     */
    public static function identity()
    {
        if (!(Yii::$app->user ?? null)) {
            return new static();
        }

        return Yii::$app->user->identity instanceof static ? Yii::$app->user->identity : new static();
    }

    /**
     * @inheritdoc
     */
    public function getId(): ?int
    {
        return $this->getPrimaryKey();
    }

    /**
     * @inheritdoc
     */
    public function getAuthKey(): string
    {
        return $this->authKey;
    }

    /**
     * @inheritdoc
     */
    public function validateAuthKey($authKey): bool
    {
        return $this->getAuthKey() === $authKey;
    }

    /**
     * Generates authentication key
     *
     * @return void
     */
    public function generateAuthKey(): void
    {
        $this->authKey = Yii::$app->security->generateRandomString();
    }


    /**
     * Validates password
     *
     * @param string $password password to validate
     *
     * @return bool if password provided is valid for current user
     */
    public function validatePassword($password): bool
    {
        return Yii::$app->security->validatePassword($password, $this->passwordHash);
    }

    /**
     * Generates password hash from password and sets it to the model
     *
     * @param string $password
     *
     * @return void
     */
    public function setPassword($password): void
    {
        $this->passwordHash = Yii::$app->security->generatePasswordHash($password);
    }

    /**
     * @inheritdoc
     */
    public static function findIdentityByAccessToken($token, $type = null): ?self
    {
        if ($type === HttpBearerAuth::class) {
            $tokens      = self::findExternalApi();
            $requestTime = (int) substr($token, 0, 10);
            $time        = time();

            // Check request time if not debug mode
            $apiDebug = Yii::$app->params['api-debug'] ?? false;

            if (!$apiDebug) {
                if (strlen($requestTime) !== 10 || $time - $requestTime > 60 || $time - $requestTime < -60) {
                    return null;
                }
            }

            foreach ($tokens as $apiUser) {
                $tmpToken = $requestTime . md5($requestTime . $apiUser->authKey . $requestTime);

                if ($tmpToken === $token) {
                    return $apiUser;
                }
            }
        } elseif ($type === JwtBearerAuth::class) {
            $jwtToken = Yii::$app->apiTokens->getJwtToken($token);

            if ($jwtToken && $customer = self::findIdentity($jwtToken->getUserID())) {
                return $customer;
            }
        }

        return null;
    }
}
