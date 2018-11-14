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

class TreeBuilder
{
    private $auth_item;//будет храниться таблица с ролями и правами, чтобы делать меньше запросов к бд
    public $tree = [];//массив ролей и разрешений

    /**
     * Построитель дерева
     * @param string $role имя роли, для которой необходимо построить дерево
     * @return Item
     */
    public function BuildTree($role)
    {
        /* Заполняю массив ролей и прав */
        $auth_items = AuthItem::find()->select(['name', 'type', 'rule_name', 'description'])->asArray()->all();
        foreach ($auth_items as $auth_item) {
            $this->auth_item[$auth_item['name']] =
                [
                    'type' => $auth_item['type'],
                    'rule_name' => $auth_item['rule_name'],
                    'description' => $auth_item['description']
                ];
        }


        //echo '<pre>' . print_r($this->auth_item, true) . '</pre>';
        //echo '<strong>'.$role.'</strong><br>';
       // echo '<div style="border: solid 1px black">';
        //$this->drawChildrenBranch($role, 0);
        $this->tree['roles'][$role] = $this->getChildrenRoles($role);
        //echo '</div>';
    }


    public function buildList($tree)
    {
        $result = '';

        $result.='<ul class="">';
        if (isset($tree['permissions'])) {
            //$result .= '<ul>';
            foreach ($tree['permissions'] as $perm) {

                $result .= '<li class="list-group-item d-flex justify-content-between align-items-center list-group-item-info">'.'<span class="glyphicon glyphicon-lock"></span> '.$perm.' <span class="label label-info">'.$this->auth_item[$perm]['description'].'</span></li>';
            }
            //$result .= '</ul>';
        }

        if(isset($tree['roles']))
        {


            foreach ($tree['roles'] as $key=>$role) {
                $result.='<li class="list-group-item d-flex justify-content-between align-items-center list-group-item-success"><span class="glyphicon glyphicon-user"></span> '.$key.' <span class="label label-success">'.$this->auth_item[$key]['description'].'</span></li>';
                $result.=$this->buildList($role);
            }

        }
        $result.='</ul>';


        return $result;
    }

    private function getChildrenRoles($role)
    {

        $children = $this->getChildren($role);
        $child_role = [];
        if (is_null($children)) {
            return;
        }

        foreach ($children as $child) {
            if ($this->isRole($child['child'])) {
                $child_role['roles'][$child['child']] = $this->getChildrenRoles($child['child']);
            }
            if ($this->isPermission($child['child'])) {
                $child_role['permissions'][] = $child['child'];
            }
        }


        //$child_role=$this->getChildrenRoles($role);

        return $child_role;
    }

    private function isRole($name)
    {
        if (!isset($this->auth_item[$name]))
            return false;
        return ($this->auth_item[$name]['type'] == '1') ? true : false;
    }

    private function isPermission($name)
    {
        if (!isset($this->auth_item[$name]))
            return false;
        return ($this->auth_item[$name]['type'] == '2') ? true : false;
    }

    private function levelColor($level)
    {
        $c = 255 - $level * 20 + 25;
        if ($c < 100)
            $c = 100;
        return "background: rgb({$c},{$c},{$c})";
    }

    /**
     * Построитель дочерней ветки
     * @param string $role имя роли, для которой необходимо построить ветвь
     * #papam int $level уровень вложености
     * @return Item
     */
    private function drawChildrenBranch($role, $level)
    {
        $level++;
        $children = $this->getChildren($role);

        if (is_null($children)) {
            return;
        }
        $roles = [];
        $permissions = [];
        foreach ($children as $child) {
            if ($this->isRole($child['child'])) {
                $roles[] = $child['child'];

            }
            if ($this->isPermission($child['child'])) {
                $permissions[] = $child['child'];
            }
        }

        if ($level == 1) {
            echo '<div class=""  style=" ">';
            echo '<div class=""><strong style="padding: 3px">' . $role . '</strong></div>';
        } else {
            echo '<div class=""  style=" border-left: solid 1px darkgrey">';
            echo '<div class=""><strong style="padding: 3px">' . $role . '</strong></div>';
        }


        echo '<div class="panel-body" style=" ' . $this->levelColor($level) . '; ">';
        if (isset($permissions)) {
            echo '<table class=""  style=" "><thead ><th width="175px">Название разрешения</th><th>Описание</th></thead>';
            foreach ($permissions as $permission) {
                echo '<tr>' .
                    '<td>' . $permission . '</td>' .
                    '<td><i>(' . $this->auth_item[$permission]['description'] . ')</i></td>' .
                    '</tr>';
            }
            echo '</table>';
        }

        if (isset($roles)) {
            foreach ($roles as $role) {
                $this->drawChildrenBranch($role, $level);
            }
        }


        echo '</div>';
        echo '</div>';
    }

    private function getChildren($role)
    {
        $children = AuthItemChild::find()->select('child')->where(['parent' => $role])->asArray()->all();
        return count($children) == 0 ? null : $children;
    }

}