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
        $tree = $this->getChildrenBranch($role, 1);
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
        $c = 255 - $level * 20 + 5;
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
    private function getChildrenBranch($role, $level)
    {
        $children = $this->getChildren($role);

        if (is_null($children)) {
            return '';
        }
        $roles = [];
        $permissions = [];
        foreach ($children as $child) {
            if ($this->isRole($child['child'])) {
                $roles[] = $child['child'];
            }
            if ($this->isPermission($child['child']))
                $permissions[] = $child['child'];
        }

        if ($level == 1) {
            echo '<div class="panel panel-primary">';
            echo '<div class="panel-heading"><strong>Основная роль: ' . $role . '</strong></div>';
        } else {
            echo '<div class="panel panel-success">';
            echo '<div class="panel-heading"><strong>Наследуемая роль: ' . $role . '</strong></div>';
        }

        echo '<div class="panel-body" style="' . $this->levelColor($level) . '">';
        if (isset($permissions)) {
            echo '<table class="table"><thead><th width="175px">Название разрешения</th><th>Описание</th></thead>';
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
                $this->getChildrenBranch($role, ++$level);
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