<?php
/**
 * Created by PhpStorm.
 * User: Anonimus
 * Date: 12.11.2018
 * Time: 8:11
 */

namespace backend\components\TreeBuilder;

use backend\models\AuthItem;
use backend\models\AuthItemChild;

/**
 * Class TreeBuilder
 * @package backend\components\TreeBuilder
 * @property array $auth_item таблица с ролями и правами, позволяет делать меньше запросов к БД
 * @property array $tree массив ролей и разрешений
 */
class TreeBuilder
{
    private $auth_item=[];
    public $tree = [];

    /**
     * Конструктор, заполняет таблицу ролей и прав
     */
    public function __construct()
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
     * Построитель дерева в виде массива
     * @param string $role имя роли, для которой необходимо построить дерево
     */
    public function BuildTree($role)
    {
        $this->tree=[];
        $this->tree['roles'][$role] = $this->getChildrenRoles($role);
    }

    /**
     * Построитель дерева в виде списка, доступного для вывода на экран
     * @param array $tree построенное функцией BuildTree дерево роль/потомки
     * @return string список ролей и их разрешений в формате html
     */
    public function buildList($tree)
    {
        $result = '';

        $result .= '<ul class="" style="margin: 0">';
        if (isset($tree['permissions'])) {
            foreach ($tree['permissions'] as $key =>$perm) {
                $result .= '<li class="list-group-item list-group-item-info">'
                    . '<span class="glyphicon glyphicon-lock"></span> '
                    . $key . ' <span class="label label-info">' . $this->auth_item[$key]['description'] . '</span></li>';
                $result .= $this->buildList($perm);
            }
        }

        if (isset($tree['roles'])) {
            foreach ($tree['roles'] as $key => $role) {
                $result .= '<li class="list-group-item list-group-item-success">
                        <span class="glyphicon glyphicon-user"></span> <a href="role?id='.$key.'">' . $key
                    . '</a> <span class="label label-success">' . $this->auth_item[$key]['description'] . '</span></li>';
                $result .= $this->buildList($role);
            }

        }
        $result .= '</ul>';

        return $result;
    }

    /**
     * Рекурсивная функция поиска потомков
     * @param string $role роль для которой необходимо найти потомков первого уровня
     * @return array|null массив потомков первого уровня для заданной роли
     */
    private function getChildrenRoles($role)
    {
        $children = $this->getChildren($role);
        $child_role = [];
        if (is_null($children)) {
            return null;
        }

        foreach ($children as $child) {
            if ($this->isRole($child['child'])) {
                $child_role['roles'][$child['child']] = $this->getChildrenRoles($child['child']);
            }
            if ($this->isPermission($child['child'])) {
                $child_role['permissions'][$child['child']] = $this->getChildrenRoles($child['child']);
            }
        }

        return $child_role;
    }

    public function isRole($name)
    {
        if (!isset($this->auth_item[$name]))
            return false;
        return ($this->auth_item[$name]['type'] == '1') ? true : false;
    }

    public function isPermission($name)
    {
        if (!isset($this->auth_item[$name]))
            return false;
        return ($this->auth_item[$name]['type'] == '2') ? true : false;
    }

    private function getChildren($role)
    {
        $children = AuthItemChild::find()->select('child')->where(['parent' => $role])->asArray()->all();
        return count($children) == 0 ? null : $children;
    }

}