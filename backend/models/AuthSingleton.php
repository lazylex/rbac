<?php
/**
 * Created by PhpStorm.
 * User: Anonimus
 * Date: 20.11.2018
 * Time: 10:50
 */

namespace backend\models;


/**
 * Class AuthSingleton
 * @package backend\models
 * @property AuthSingleton $_instance экземпляр данного класса
 * @property array $auth_item массив ролей и разрешений. В качестве ключа выступает имя роли или разрешения
 * @property array $tree массив деревье ролей и разрешений. В качестве ключа выступает имя роли или разрешения
 */
class AuthSingleton
{
    protected static $_instance;

    private $auth_item = [];
    private $auth_assignment = [];
    private $tree = [];

    /**
     * Функция для получения экземпляра данного класса
     * @return AuthSingleton
     */
    public static function getInstance()
    {
        if (self::$_instance === null) {
            self::$_instance = new self;
        }
        return self::$_instance;
    }

    /**
     * Конструктор, заполняет таблицу ролей и прав
     */
    private function __construct()
    {
        $this->fillAuthItem();
        $this->fillAuthAssignment();
    }

    /**
     * заполняем таблицу ролей и прав
     */
    public function fillAuthItem()
    {
        $auth_items = AuthItem::find()->select(['name', 'type', 'rule_name', 'description'])->asArray()->all();
        foreach ($auth_items as $auth_item) {
            $this->auth_item[$auth_item['name']] =
                [
                    'type' => $auth_item['type'],
                    'rule_name' => $auth_item['rule_name'],
                    'description' => $auth_item['description']
                ];
        }
    }

    /**
     * заполняем теблицу соответствия пользователей и ролей/прав
     */
    public function fillAuthAssignment()
    {
        $auth_assignments = AuthAssignment::find()->select(['user_id','item_name'])->asArray()->all();
        foreach ($auth_assignments as $auth_assignment) {
            $this->auth_assignment['user_id'] =
                [
                    'item_name' => $auth_assignment['user_id']
                ];
        }
    }

    /**
     * Функция возвращающая все возможные роли
     * @return array все роли
     */
    public function getRoles()
    {
        $result = [];
        foreach ($this->auth_item as $key => $item) {
            if ($item['type'] == 1)
                $result[] = $key;
        }
        return $result;
    }

    /**
     * Функция возвращающая все возможные разрешения
     * @return array все разрешения
     */
    public function getPermissions()
    {
        $result = [];
        foreach ($this->auth_item as $key => $item) {
            if ($item['type'] == 2)
                $result[] = $key;
        }
        return $result;
    }

    /**
     * Возвращаем описание по имени роли/разрешения
     * @param string $name имя роли/разрешения
     * @return string Описание роли/правила
     */
    public function getItemDescription($name)
    {
        if (isset($this->auth_item[$name]['description']))
            return $this->auth_item[$name]['description'];
        return '';
    }

    /**
     * Возвращаем правило по имени роли/разрешения
     * @param string $name имя роли/разрешения
     * @return string Название правила
     */
    public function getItemRule($name)
    {
        if (isset($this->auth_item[$name]['rule_name']))
            return $this->auth_item[$name]['rule_name'];
        return '';
    }

    /**
     * Построитель дерева в виде массива
     * @param string $parent имя роли, для которой необходимо построить дерево
     */
    public function BuildTree($parent)
    {
        //$this->tree=[];
        if ($this->isRole($parent))
            $this->tree['roles'][$parent] = $this->getChildrenRolesAndPermissions($parent);
        else
            $this->tree['permissions'][$parent] = $this->getChildrenRolesAndPermissions($parent);
    }

    public function getAuthItem()
    {
        return $this->auth_item;
    }


    public function getTree($parent)
    {
        if (!isset($this->tree['roles'][$parent]) && !isset($this->tree['permission'][$parent]))
            $this->BuildTree($parent);
        return $this->tree;
    }

    public function getBranch($parent)
    {
        if ($this->isRole($parent)) {
            if (!isset($this->tree['roles'][$parent]))
                $this->BuildTree($parent);
            return $this->tree['roles'][$parent];
        }
        if ($this->isPermission($parent)) {
            if (!$this->tree['permission'][$parent])
                $this->BuildTree($parent);
            return $this->tree['permission'][$parent];
        }
    }

    /**
     * Рекурсивная функция поиска потомков
     * @param string $parent роль или разрешение, для которых необходимо найти потомков
     * @return array|null массив потомков для заданной роли или разрешения
     */
    private function getChildrenRolesAndPermissions($parent)
    {
        $children = $this->getChildren($parent);
        $child_role = [];
        if (is_null($children)) {
            return null;
        }

        foreach ($children as $child) {
            if ($this->isRole($child['child'])) {
                $child_role['roles'][$child['child']] = $this->getChildrenRolesAndPermissions($child['child']);
            }
            if ($this->isPermission($child['child'])) {
                $child_role['permissions'][$child['child']] = $this->getChildrenRolesAndPermissions($child['child']);
            }
        }
        return $child_role;
    }


    /**
     * Функиция проверяющая, является ли $name ролью
     * @param string $name имя предполагаемой роли
     * @return bool
     */
    public function isRole($name)
    {
        if (!isset($this->auth_item[$name]))
            return false;
        return ($this->auth_item[$name]['type'] == '1') ? true : false;
    }

    /**
     * Функция проверяющая, является ли $name разрешением
     * @param string $name имя предполагаемого разрешения
     * @return bool
     */
    public function isPermission($name)
    {
        if (!isset($this->auth_item[$name]))
            return false;
        return ($this->auth_item[$name]['type'] == '2') ? true : false;
    }

    /**
     * @param string $parent имя роли или разрешения, для которых необходимо вернуть прямых потомков
     * @return array|null массив прямых потомков
     */
    private function getChildren($parent)
    {
        $children = AuthItemChild::find()->select('child')->where(['parent' => $parent])->asArray()->all();
        return count($children) == 0 ? null : $children;
    }

    /**
     * запрещаем клонирование объекта модификатором private
     */
    private function __clone()
    {
    }

    /**
     * запрещаем клонирование объекта модификатором private
     */
    private function __wakeup()
    {
    }
}