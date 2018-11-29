<?php

use yii\helpers\Html;

$this->title = 'Роли';
$this->params['breadcrumbs'][] = ['label' => 'RBAC', 'url' => 'index'];
$this->params['breadcrumbs'][] = $this->title;
$num = 1;
?>
<div style="background: white; width: 50%; margin: auto">
    <table class="table">
        <thead class="thead-default">
        <tr>
            <th>#</th>
            <th>Роль</th>
            <th>Описание</th>
            <th>Правило</th>
        </tr>
        </thead>
        <?php foreach ($roles as $role => $role_descr_and_rule): ?>
            <tr>
                <td><?= $num++ ?></td>
                <td>
                    <?php
                    if ($role != 'Главный'):?>
                        <a class="btn btn-primary" style="width: 200px; text-align: left"
                        <?= $role == 'Default' ? 'disabled="disabled">' : 'href="' . \yii\helpers\Url::to(['rbac/role', 'name' => $role]) . '">' ?>

                        <span class="glyphicon glyphicon-pencil"></span> <?= $role ?>
                        </a>
                    <?php elseif (\Yii::$app->user->can('changeAllRoles')): ?>
                        <a class="btn btn-primary" style="width: 200px; text-align: left"
                           href="<?= \yii\helpers\Url::to(['rbac/role', 'name' => $role]) ?>">
                            <span class="glyphicon glyphicon-pencil"></span> <?= $role ?>
                        </a>
                    <?php else: ?>
                        <button class="btn" style="width: 200px" disabled="disabled"><span
                                    class="glyphicon glyphicon-ban-circle"></span> <?= $role ?></button>
                    <?php endif; ?>
                </td>
                <td><?= $role_descr_and_rule['description'] ?></td>
                <td><?= $role_descr_and_rule['rule'] ?></td>
            </tr>

        <?php endforeach; ?>
        <tr>
            <td></td>
            <td>
                <a class="btn btn-success" style="width: 200px"
                   href="<?= \yii\helpers\Url::to(['rbac/create-role']) ?>">Создать новую роль</a>
            </td>
        </tr>
    </table>
</div>