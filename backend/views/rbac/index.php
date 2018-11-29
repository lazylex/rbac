<?php

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Role based access control';

$this->params['breadcrumbs'][] = $this->title;
?>

<ul class="list-group" style="width: 300px">
    <li class="list-group-item">
        <span class="badge"><?= $user_count ?></span>
        <a href="<?= Url::to(['users']) ?>">Пользователи</a>
    </li>
    <li class="list-group-item">
        <span class="badge"><?= $role_count ?></span>
        <a href="<?= Url::to(['roles']) ?>">Роли</a>
    </li>
    <li class="list-group-item">
        <span class="badge"><?= $auth_assignment_count ?></span>
        <a href="<?= Url::to(['/auth-assignment']) ?>">auth_assignment</a>
    </li>
    <li class="list-group-item">
        <span class="badge"><?= $auth_item_count ?></span>
        <a href="<?= Url::to(['/auth-item']) ?>">auth_item</a>
    </li>
    <li class="list-group-item">
        <span class="badge"><?= $auth_item_child_count ?></span>
        <a href="<?= Url::to(['/auth-item-child']) ?>">auth_item-child</a>
    </li>
    <li class="list-group-item">
        <span class="badge"><?= $auth_rule_count ?></span>
        <a href="#">auth_rule</a>
    </li>
</ul>
