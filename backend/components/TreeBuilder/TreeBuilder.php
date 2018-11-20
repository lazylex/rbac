<?php

namespace backend\components\TreeBuilder;

use backend\models\AuthItem;
use backend\models\AuthItemChild;
use yii\helpers\Html;

/**
 * Class TreeBuilder
 * @package backend\components\TreeBuilder
 * @property array $auth_item таблица с ролями и правами, позволяет делать меньше запросов к БД
 * @property array $tree массив ролей и разрешений
 */
class TreeBuilder
{
    public $auth_item=[];
    public $tree = [];

    /**
     * Конструктор, заполняет таблицу ролей и прав
     */
    public function __construct()
    {
        /*$auth_items = AuthItem::find()->select(['name', 'type', 'rule_name', 'description'])->asArray()->all();
        foreach ($auth_items as $auth_item) {
            $this->auth_item[$auth_item['name']] =
                [
                    'type' => $auth_item['type'],
                    'rule_name' => $auth_item['rule_name'],
                    'description' => $auth_item['description']
                ];
        }*/
    }

    /**
     * Построитель дерева в виде массива
     * @param string $parent имя роли, для которой необходимо построить дерево
     */
    public function BuildTree($parent)
    {
        $this->tree=[];
        if($this->isRole($parent))
            $this->tree['roles'][$parent] = $this->getChildrenRolesAndPermissions($parent);
        else
            $this->tree['permissions'][$parent] = $this->getChildrenRolesAndPermissions($parent);
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

                $result .=Html::tag('li',
                    Html::tag('span','',['class'=>'glyphicon glyphicon-lock']).' '
                    .Html::a($key.' ' ,'permission',
                        [
                            'data' => [
                                'method' => 'post',
                                'params' => ['name'=>$key],
                            ]
                        ])
                    .Html::tag('span',$this->auth_item[$key]['description'],['class'=>'label label-info'])
                    ,['class'=>'list-group-item list-group-item-info']);
                $result .= $this->buildList($perm);
            }
        }

        if (isset($tree['roles'])) {
            foreach ($tree['roles'] as $key =>$role) {

                $result .=Html::tag('li',
                    Html::tag('span','',['class'=>'glyphicon glyphicon-user']).' '
                    .Html::a($key.' ' ,'role',
                        [
                            'data' => [
                                'method' => 'post',
                                'params' => ['name'=>$key],
                            ]
                        ])
                    .Html::tag('span',$this->auth_item[$key]['description'],['class'=>'label label-success'])
                    ,['class'=>'list-group-item list-group-item-success']);
                $result .= $this->buildList($role);
            }
        }
        $result .= '</ul>';

        return $result;
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

}