<?php
/**
 * Created by PhpStorm.
 * User: Anonimus
 * Date: 20.11.2018
 * Time: 7:52
 */

namespace backend\models;


use yii\base\Model;

/**
 * Class UserRolesAndPermissions
 * @package backend\models
 * @property integer $id идентификатор пользователя
 * @property AuthSingleton as вспомогательная модель для работы с RBAC
 * @var AuthSingleton $as
 */
class UserRolesAndPermissions extends Model
{
    private $id;
    private $as;

    /** Инициализировать модель этой функцией
     * @param integer $id идентификатор пользователя
     */
    public function setUserById($id)
    {
        $this->id = $id;
        $this->as = AuthSingleton::getInstance();
    }

    /** Функция добавления/удаления личных разрешений пользователя
     * @param array $permissions список разрешений (отсутствующие в списке удаляются и из БД)
     */
    public function setUserPrivatePermissions($permissions)
    {
        $auth = \Yii::$app->authManager;
        $all_permissions = $this->as->getPermissions();
        $current_permissions = $this->as->getPrivatePermissionsByUser($this->id);
        $all_user_permissions = $this->as->getPermissionsByUser($this->id);
        //echo '<pre>'.print_r($permissions,true).'</pre>';die;
        if (is_null($permissions)) {
            foreach ($current_permissions as $permission) {
                $auth->revoke($auth->getPermission($permission), $this->id);
            }
        } else {
            foreach ($current_permissions as $permission) {
                if (!in_array($permission, $permissions)) {
                    $auth->revoke($auth->getPermission($permission), $this->id);
                }
            }
            /* не мешало бы реализовать запрет на присвоение разрешения, которое содержит в себе присвоеное разрешение */
            foreach ($permissions as $permission) {
                if (!in_array($permission, $all_user_permissions) && in_array($permission, $all_permissions) && $permission != 'changeAllRoles') {
                    $auth->assign($auth->getPermission($permission), $this->id);
                }
            }
        }
    }

    /** Функция добавления/удаления ролей пользователя
     * @param array $roles список ролей пользователя (отсутствующие в списке удаляются и из БД)
     */
    public function setUserRoles($roles)
    {

        $auth = \Yii::$app->authManager;
        $all_roles = $this->as->getRoles();
        $all_user_roles = $this->as->getRolesByUser($this->id);
        if (is_null($roles)||count($roles)==0) {
            foreach ($all_user_roles as $role) {
                $auth->revoke($auth->getRole($role), $this->id);
            }
            $auth->assign($auth->getRole('Default'), $this->id);
        } else {
            foreach ($all_user_roles as $role) {
                if (!in_array($role, $roles)) {
                    $auth->revoke($auth->getRole($role), $this->id);
                }
            }

            foreach ($roles as $role) {
                if (!in_array($role, $all_user_roles) && in_array($role, $all_roles) && $role != 'Главный') {
                    $auth->assign($auth->getRole($role), $this->id);
                }
            }
            if (in_array('Главный', $roles) && \Yii::$app->user->can('changeAllRoles')) {
                $auth->assign($auth->getRole('Главный'), $this->id);
            }

        }
        $this->as->fillAuthAssignment();
        $all_user_roles = $this->as->getRolesByUser($this->id);
        if (count($all_user_roles) > 1 && in_array('Default', $all_user_roles)) {
            $auth->revoke($auth->getRole('Default'), $this->id);
        }
        if (count($all_user_roles) ==0) {
            $auth->revoke($auth->getRole('Default'), $this->id);
        }
    }
}