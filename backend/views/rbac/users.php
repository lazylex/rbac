<?php

use yii\helpers\Html;

$this->title = 'Пользователи';
$this->params['breadcrumbs'][] = ['label' => 'RBAC', 'url' => 'index'];
$this->params['breadcrumbs'][] = $this->title;

?>
<div>

    <?php
    $status[\common\models\User::STATUS_ACTIVE] = Html::tag('span', 'Активен', ['class' => 'label label-success']);
    $status[\common\models\User::STATUS_DELETED] = Html::tag('span', 'Удален', ['class' => 'label label-danger']);
    ?>
    <table class="table table-striped table-bordered table-hover">
        <thead style="background: gray; text-align:center">
        <th>ID</th>
        <th>Статус</th>
        <th>Имя пользователя</th>
        <th>Роли</th>
        <th>Права</th>
        <th>Создан</th>
        <th>Редактировать</th>
        </thead>
        <tbody style="background: lightgrey">
        <?php
        foreach ($users as $user):
            $change_user;

            $roles = '';
            if (isset($user['roles'])) {
                foreach ($user['roles'] as $role) {
                    $roles .= Html::tag('li', $role);
                }
            } else
                continue;

            $permissions = '';
            if (isset($user['permissions']))
                foreach ($user['permissions'] as $permission) {

                    //кроме главного никто не должен знать название разрешения на полный доступ к изменению ролей
                    if (($permission['name'] == "changeAllRoles") && (!\Yii::$app->user->can('changeAllRoles')))
                        continue;
                    $permissions .= Html::tag('li', $permission['name'] . '<i> (' . $permission['description'] . ')</i>');
                }
            $permissions = Html::tag('ul', $permissions);

            if (\Yii::$app->user->can('changeAllRoles') || \Yii::$app->user->can('changeRole', ['roles' => $user['roles']])) {
                $change_user = Html::tag('a', Html::tag('span', '', ['class' => 'glyphicon glyphicon-pencil']) . ' Редактировать',
                    [
                        'class' => 'btn btn-primary',
                        'href' => \yii\helpers\Url::to(['user']),
                        'data' => [
                            'method' => 'post',
                            'params' => ['id' => $user['id']], // <- extra level
                        ],]);
            } else {
                $change_user = Html::tag('button', '<span class="glyphicon glyphicon-ban-circle"></span> Редактировать', ['class' => 'btn', 'disabled' => 'true']);
            } ?>
            <tr style="text-align: center">
                <td><?= $user['id'] ?></td>
                <td><?= isset($status[$user['status']]) ? $status[$user['status']] : $user['status'] ?></td>
                <td><?= $user['username'] ?></td>
                <td style="text-align: left"><?= $roles ?></td>
                <td style="text-align: left"><?= $permissions ?></td>
                <td><?= date('d/m/Y G:i', $user['created_at']) ?></td>
                <td><?= $change_user ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
