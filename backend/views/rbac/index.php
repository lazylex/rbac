<?php

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Role based access control';

$this->params['breadcrumbs'][] = $this->title;

echo '<ul class="list-group" style="width: 300px">';
echo '<li class="list-group-item"><span class="badge">'.$user_count.'</span>' . Html::a('Пользователи', Url::to(['users'])) . '</li>';
echo '<li class="list-group-item"><span class="badge">'.$role_count.'</span>' . Html::a('Роли', Url::to(['roles'])) . '</li>';
echo '<li class="list-group-item"><span class="badge">'.$auth_assignment_count.'</span>' . Html::a('auth_assignment', Url::to(['/auth-assignment'])) . '</li>';
echo '<li class="list-group-item"><span class="badge">'.$auth_item_count.'</span>' . Html::a('auth_item', Url::to(['/auth-item'])) . '</li>';
echo '<li class="list-group-item"><span class="badge">'.$auth_item_child_count.'</span>' . Html::a('auth_item-child', Url::to(['/auth-item-child'])) . '</li>';
echo '<li class="list-group-item"><span class="badge">'.$auth_rule_count.'</span>' . Html::a('auth_rule', Url::to(['/auth-rule'])) . '</li>';
echo '</ul>';