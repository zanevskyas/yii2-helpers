<?php
/**
 * Created by PhpStorm.
 * User: mikhail
 * Date: 3/29/19
 * Time: 6:06 PM
 */

namespace Zanevsky\Yii2Helpers\Rbac;

use Yii;
use yii\console\Controller;
use yii\rbac\ManagerInterface;
use yii\rbac\Permission;
use yii\rbac\Rule;

/**
 * Class    RbacController
 *
 * @package Zanevsky\Yii2Helpers\Rbac
 * @author  Yarmaliuk Mikhail
 * @version 1.0
 */
class RbacController extends Controller
{
    /**
     * @var string
     */
    public $rbacConfig = null;

    /**
     * @var array
     */
    public $rbacModules = [];

    /**
     * @var string
     */
    public $roleCustomerField = 'role';

    /**
     * @var string
     */
    public $roleCustomerTemplate = __DIR__ . '/CustomerRoleRuleTemplate.txt';

    /**
     * @var string
     */
    public $roleCustomerNamespace = 'console\runtime\rbac';

    /**
     * @var string
     */
    public $roleCustomerClassname = 'CustomerRoleRule';

    /**
     * @var string
     */
    public $generatedFolder = '@console/runtime/rbac';

    public function init()
    {
        if (empty($this->rbacConfig)) {
            throw InvalidConfigException('"rbacConfig" must be set.');
        }
    }

    /**
     * Generate rbac rules and permissions
     *
     * @return void
     */
    public function actionInit(): void
    {
        $auth = Yii::$app->authManager;

        // Remove old data
        $auth->removeAll();

        // Generate general customer rule
        $this->generateCustomerRoleRule();

        // Add general customer rule
        $roleClassname = '\\' . $this->roleCustomerNamespace . '\\' . $this->roleCustomerClassname;
        $rule          = new $roleClassname();
        $auth->add($rule);

        // Add roles
        $this->generateRoles($auth, $rule);

        // Add permissions and rules
        $this->generatePermissionsRelations($auth);
    }

    /**
     * Genearte roles
     *
     * @param ManagerInterface $auth
     * @param Rule             $rule
     *
     * @return array
     */
    public function generateRoles(ManagerInterface $auth, Rule $rule): void
    {
        $rbacConfig    = $this->rbacConfig;
        $roleClassName = $rbacConfig::$roleClass;

        foreach ($rbacConfig::$roleRelationships as $roleName => $childrens) {
            if (is_string($childrens)) {
                $roleName = $childrens;
            }

            $role                = $auth->createRole($roleName);
            $role->description   = $roleClassName::get($roleName);
            $role->data['value'] = $roleName;
            $role->ruleName      = $rule->name;
            $auth->add($role);

            if (is_array($childrens)) {
                foreach ($childrens as $children) {
                    $childrenRole = $auth->getRole($children);

                    // Add children role
                    $childrenRole && $auth->addChild($role, $childrenRole);
                }
            }
        }
    }

    /**
     * Generate customer general role rule
     *
     * @return void
     */
    private function generateCustomerRoleRule(): void
    {
        $rbacConfig = $this->rbacConfig;
        $template   = file_get_contents(Yii::getAlias($this->roleCustomerTemplate));

        // Replace customer field role
        $template = str_replace('__ROLE_FIELD__', $this->roleCustomerField, $template);

        // Replace namespace
        $template = str_replace('__NAMESPACE__', $this->roleCustomerNamespace, $template);

        // Replace classname
        $template = str_replace('__CLASSNAME__', $this->roleCustomerClassname, $template);

        $rules = '';

        $analyticRelations = [];
        // Compose childrens
        foreach ($rbacConfig::$roleRelationships as $aRole => $aChildrens) {
            if (is_string($aChildrens)) {
                $analyticRelations[$aChildrens] = [];
            } else {
                foreach ($aChildrens as $aChildren) {
                    if (!in_array($aRole, $analyticRelations[$aChildren] ?? [])) {
                        $analyticRelations[$aChildren][] = $aRole;
                    }
                }
            }
        }

        $composeChildrens = function ($role, $childrens) use (&$analyticRelations, &$composeChildrens) {
            if (is_array($childrens)) {
                foreach ($childrens as $childRole) {
                    if (!in_array($childRole, $analyticRelations[$role])) {
                        $analyticRelations[$role][] = $childRole;
                    }

                    if (!empty($analyticRelations[$childRole])) {
                        $composeChildrens($role, $analyticRelations[$childRole]);
                    }
                }
            }
        };

        // Final childrens compose
        foreach ($analyticRelations as $role => $childrens) {
            $composeChildrens($role, $childrens);
        }

        foreach ($rbacConfig::$roleRelationships as $role => $childrens) {
            if (is_string($childrens)) {
                $role = $childrens;
            }

            $roles   = array_map(function ($child) {
                return '$role === \'' . $child . "'";
            }, $analyticRelations[$role] ?? []);
            $roles[] = '$role === \'' . $role . '\';';

            $rules .= empty($rules) ? '                if ' : ' elseif ';
            $rules .= '($item->name === \'' . $role . '\') {' . "\n";
            $rules .= '                    return ' . implode(' || ', $roles) . "\n";
            $rules .= '                }';
        }

        // Replace rules
        $template = str_replace('__RULES__', $rules, $template);

        $storeFolder = Yii::getAlias($this->generatedFolder);

        if (!file_exists($storeFolder)) {
            mkdir($storeFolder);
        }

        file_put_contents($this->getRoleCustomerPath(), $template);
    }

    /**
     * Get role customer class path
     *
     * @return string
     */
    private function getRoleCustomerPath(): string
    {
        return Yii::getAlias("$this->generatedFolder/$this->roleCustomerClassname.php");
    }

    /**
     * Generate permissions/rule/role relationships
     *
     * @param ManagerInterface $auth
     *
     * @return void
     */
    private function generatePermissionsRelations(ManagerInterface $auth): void
    {
        $rbacConfig      = $this->rbacConfig;
        $rbacPermissions = $rbacConfig::$permissions;

        if (!empty($this->rbacModules)) {
            foreach ($this->rbacModules as $rbacModule) {
                $rbacPermissions = array_merge($rbacPermissions, $rbacModule::$permissions);
            }
        }

        foreach ($rbacPermissions as $permissionClass) {
            $roleRelationships = $permissionClass::$roleRelationships;

            foreach ($roleRelationships as $roleName => $permissions) {
                $role = $auth->getRole($roleName);

                foreach ($permissions as $permissionName) {
                    $permission = $this->getOrCreatePermission($auth, $permissionName, $permissionClass);

                    $permission && $auth->addChild($role, $permission);
                }
            }
        }
    }

    /**
     * Get or create permission
     *
     * @param ManagerInterface $auth
     * @param string           $permissionName
     * @param string|object    $permissionClass
     *
     * @return Permission
     */
    private function getOrCreatePermission(ManagerInterface $auth, string $permissionName, string $permissionClass): Permission
    {
        $permission = $auth->getPermission($permissionName);

        if ($permission !== null) {
            return $permission;
        }

        $rules     = $permissionClass::$ruleRelationships;
        $childrens = $permissionClass::$permissionRelationships;

        $permission              = $auth->createPermission($permissionName);
        $permission->description = str_replace('_', ' ', ucfirst(strtolower($permissionName)));

        // Attach rule if exist
        if ($ruleClass = ($rules[$permissionName] ?? null)) {
            $permission->ruleName = $this->getOrCreateRule($auth, $ruleClass)->name;
        }

        $auth->add($permission);

        // Add children
        if ($permissionChildrens = ($childrens[$permissionName] ?? null)) {
            foreach ($permissionChildrens as $permissionChildren) {
                $childPermission = $this->getOrCreatePermission($auth, $permissionChildren, $permissionClass);

                $childPermission && $auth->addChild($permission, $childPermission);
            }
        }

        return $permission;
    }

    /**
     * Get or create rule
     *
     * @param ManagerInterface $auth
     * @param string           $ruleClassname
     *
     * @return Rule
     */
    private function getOrCreateRule(ManagerInterface $auth, string $ruleClassname): Rule
    {
        $ruleInsatance = new $ruleClassname();
        $rule          = $auth->getRule($ruleInsatance->name);

        if ($rule !== null) {
            return $rule;
        }

        $auth->add($ruleInsatance);

        return $ruleInsatance;
    }
}
