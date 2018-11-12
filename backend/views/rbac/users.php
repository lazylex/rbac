<?php

use yii\helpers\Html;

$this->title = 'Пользователи';
$this->params['breadcrumbs'][] = $this->title;
?>
<div>

    <h1><?= Html::encode($this->title) ?></h1>

    <?php
    $status[\common\models\User::STATUS_ACTIVE] = Html::tag('span', 'Активен', ['class' => 'label label-success']);
    $status[\common\models\User::STATUS_DELETED] = Html::tag('span', 'Удален', ['class' => 'label label-danger']);
    //echo Html::beginTag('form',['action'=>'user','mathod'=>'post']);
    echo Html::beginTag('table', ['class' => 'table table-striped table-bordered table-hover']);
    echo Html::beginTag('thead');
    echo Html::tag('th', 'ID', ['style' => 'text-align:center']);
    echo Html::tag('th', 'Статус', ['style' => 'text-align:center']);
    echo Html::tag('th', 'Имя пользователя', ['style' => 'text-align:center']);
    echo Html::tag('th', 'Роли', ['style' => 'text-align:center']);
    echo Html::tag('th', 'Права', ['style' => 'text-align:center']);
    echo Html::tag('th', 'Создан', ['style' => 'text-align:center']);
    echo Html::tag('th', 'Редактировать', ['style' => 'text-align:center']);
    echo Html::endTag('thead');
    echo Html::beginTag('tbody', ['class' => 'table-striped']);
    foreach ($users as $user) {
        $change_user;

        $roles = '';
        foreach ($user['roles'] as $role) {
            $roles .= Html::tag('li', $role);
        }
        $roles == Html::tag('ul', $roles);

        $permissions = '';
        foreach ($user['permissions'] as $permission) {
            $permissions .= Html::tag('li', $permission);
        }
        $permissions = Html::tag('ul', $permissions);


        if (\Yii::$app->user->can('changeAllRoles') || \Yii::$app->user->can('changeRole', ['roles' => $user['roles']])) {
            $change_user = Html::tag('a', Html::tag('span', '', ['class' => 'glyphicon glyphicon-pencil']) . ' Редактировать',
                [
                    'class' => 'btn btn-primary',
                    'href' => \yii\helpers\Url::to(['user']),
                    'data' => [
                        'method' => 'post',
                        'params' => ['id'=>$user['id']], // <- extra level
                    ],]);
        } else {
            $change_user = Html::tag('a', 'Редактировать', ['class' => 'btn', 'disabled' => 'true']);
        }
        echo Html::beginTag('tr');
        echo Html::tag('td', $user['id'], ['style' => 'text-align:center']);
        echo Html::tag('td', isset($status[$user['status']]) ? $status[$user['status']] : $user['status'], ['style' => 'text-align:center']);
        echo Html::tag('td', $user['username'], ['class' => 'h4', 'style' => 'text-align:center']);
        echo Html::tag('td', $roles);
        echo Html::tag('td', $permissions);
        echo Html::tag('td', date('d/m/Y G:i', $user['created_at']), ['style' => 'text-align:center']);
        echo Html::tag('td', $change_user, ['style' => 'text-align:center']);
        echo Html::endTag('tr');
    }
    echo Html::endTag('tbody');
    echo Html::endTag('table');
    //echo Html::endTag('form');
    ?>


</div>
