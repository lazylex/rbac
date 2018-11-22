<?php

namespace backend\models;


/**
 * Class AuthSingleton
 * @package backend\models
 * @property AuthSingleton $_instance экземпляр данного класса
 * @property array $tree массив деревье ролей и разрешений. В качестве ключа выступает имя роли или разрешения
 * @property array $auth_item массив ролей и разрешений. В качестве ключа выступает имя роли или разрешения
 * @property array $auth_assignment массив ролей/разрешений пользователей. В качестве ключа выступает id пользователя
 * @property array $auth_item_child массив отношений родитель/потомок. В качестве ключа выступает имя родителя
 * @property array $user_permissions временный массив, необходимый функции getPermissionsByUser
 */
class AuthSingleton
{
    protected static $_instance;

    private $tree = [];
    private $auth_item = [];
    private $auth_assignment = [];
    private $auth_item_child=[];
    private $user_permissions = [];

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
        $this->fillAuthItemChild();
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
     * Функция Функция выборки данных из базы в массив $auth_assignment
     * (заполняем таблицу соответствия пользователей и ролей/прав)
     */
    public function fillAuthAssignment()
    {
        $auth_assignments = AuthAssignment::find()->select(['user_id', 'item_name'])->asArray()->all();
        foreach ($auth_assignments as $auth_assignment) {
            $this->auth_assignment[$auth_assignment['user_id']][] = $auth_assignment['item_name'];
        }
    }

    /**
     * Функция выборки данных из базы в массив $auth_item_child
     * (заполняем таблицу отношений родитель/потомок)
     */
    public function fillAuthItemChild()
    {
        $auth_item_childs=AuthItemChild::find()->select('*')->asArray()->all();

        foreach ($auth_item_childs as $auth_item_child) {
            $this->auth_item_child[$auth_item_child['parent']][]=$auth_item_child['child'];
        }
    }

    /**
     * Функция возвращает разрешения, назначенные непосредственно пользователю
     * @param integer $id идентификатов пользователя
     * @return array массив разрешений пользователя
     */
    public function getPrivatePermissionsByUser($id)
    {
        $result = [];
        foreach ($this->auth_assignment[$id] as $item) {
            if ($this->isPermission($item))
                $result[] = $item;
        }
        return $result;
    }

    /**
     * Функция возвращает все разрешения пользователя
     * @param integer $id идентификатов пользователя
     * @return array массив разрешений пользователя
     */
    public function getPermissionsByUser($id)
    {
        $this->user_permissions = [];
        foreach ($this->auth_assignment[$id] as $item) {
            if ($this->isPermission($item))
                $this->user_permissions[] = $item;
            $this->getPermissionsByItem($item);
        }

        return $this->user_permissions;
    }

    /**
     * Рекурсивная функция поиска разрешений
     * @param $parent элемент, для которого необходимо найти разрешения
     */
    private function getPermissionsByItem($parent)
    {
        $childs = $this->getChildren($parent);
        if (is_null($childs))
            return;
        foreach ($childs as $child) {
            if ($this->isPermission($child)) {
                $this->user_permissions[] = $child;
            }
            $this->getPermissionsByItem($child);
        }
    }

    /** Функция возвращает роли пользователя (без наследуемых)
     * @param integer $id идентификатор пользователя
     * @return array роли пользователя
     */
    public function getRolesByUser($id)
    {
        $user_roles = [];
        foreach ($this->auth_assignment[$id] as $item) {
            if ($this->isRole($item))
                $user_roles[] = $item;

        }
        return $user_roles;
    }

    /**
     * Функция возвращающая все существующие роли
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
     * Функция возвращающая все существующие разрешения
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
        if ($this->isRole($parent))
            $this->tree['roles'][$parent] = $this->getChildrenRolesAndPermissions($parent);
        else
            $this->tree['permissions'][$parent] = $this->getChildrenRolesAndPermissions($parent);
    }

    /** Геттер. Возвращает массив ролей и разрешений.
     * @return array массив ролей и разрешений. В качестве ключа выступает имя роли или разрешения
     */
    public function getAuthItem()
    {
        return $this->auth_item;
    }

    /**
     * Функция возвращает дерево ролей/разрешений (массив $tree)
     * @param $parent элемент, для которого необходимо вернуть дерево
     * @return array дерево ролей/разрешений
     */
    public function getTree($parent)
    {
        if (!isset($this->tree['roles'][$parent]) && !isset($this->tree['permission'][$parent]))
            $this->BuildTree($parent);
        return $this->tree;
    }

    /**
     * Функция возвращает ветвь дерева ролей/разрешений (из массива $tree)
     * @param $parent элемент, являющийся корнем ветви
     * @return array ветвь дерева ролей/разрешений
     */
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
            if ($this->isRole($child)) {
                $child_role['roles'][$child] = $this->getChildrenRolesAndPermissions($child);
            }
            if ($this->isPermission($child)) {
                $child_role['permissions'][$child] = $this->getChildrenRolesAndPermissions($child);
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
       if(isset($this->auth_item_child[$parent])&&count($this->auth_item_child[$parent])>0)
       {
           $result=[];
           foreach ($this->auth_item_child[$parent] as $child)
           {
               $result[]=$child;
           }
           return $result;
       }
       else
           return null;
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