# yii2-helpers

Yii2 Helpers


## Installation

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```bash
php composer.phar require --prefer-dist zanevskyas/yii2-helpers "@dev"
```

or add

```
"zanevskyas/yii2-helpers": "@dev"
```

to the require section of your `composer.json` file.

## RBAC
Add below code to console main configuration:
```php
'controllerMap' => [
    ...
    'rbac'    => [
        'class'      => Kakadu\Yii2Helpers\Rbac::class,
        'rbacConfig' => RbacConfig::class,
    ],
    ...
]
```

Create RbacConfigClass in `common/rbac`, sample:
```php
abstract class RbacConfig
{
    /**
     * @var string|Enum
     */
    public static $roleClass = CustomerRole::class;

    /**
     * @var array
     */
    public static $roleRelationships = [
        CustomerRole::CUSTOMER,
        CustomerRole::AUTHOR      => [
            CustomerRole::CUSTOMER,
        ],
        ...
    ];

    /**
     * @var array|PermissionCustomer
     */
    public static $permissions = [
        // Customers
        PermissionCustomer::class,
        PermissionSettings::class,

        // Countries
        ...
        // Cities
        ...
    ];
}
```

Create permissions and rules...

Permission example:
```php
abstract class PermissionSettings
{
    public const CREATE     = 'CUSTOMER_SETTINGS_CREATE';
    public const UPDATE     = 'CUSTOMER_SETTINGS_UPDATE';
    public const UPDATE_OWN = 'CUSTOMER_SETTINGS_UPDATE_OWN';
    public const VIEW       = 'CUSTOMER_SETTINGS_VIEW';
    public const VIEW_OWN   = 'CUSTOMER_SETTINGS_VIEW_OWN';

    /**
     * @var array
     */
    public static $ruleRelationships = [
        self::UPDATE_OWN => RuleOwnerCustomerSettings::class,
        self::VIEW_OWN   => RuleOwnerCustomerSettings::class,
    ];

    /**
     * @var array
     */
    public static $permissionRelationships = [
        self::UPDATE_OWN => [
            self::UPDATE,
        ],
        self::VIEW_OWN   => [
            self::VIEW,
        ],
    ];

    /**
     * @var array
     */
    public static $roleRelationships = [
        CustomerRole::CUSTOMER => [
            self::UPDATE_OWN,
            self::VIEW_OWN,
        ],
        CustomerRole::ADMIN    => [
            self::CREATE,
            self::UPDATE,
            self::VIEW,
        ],
    ];
}
```

Run console command to generate rbac cache:
```bash
php yii rbac/init
```

That's all. Check it.