<?php

use yii\helpers\Html;

$this->title = 'Создать роль';
$this->params['breadcrumbs'][] = ['label' => 'RBAC', 'url' => 'index'];
$this->params['breadcrumbs'][] = ['label' => 'Роли', 'url' => 'roles'];
$this->params['breadcrumbs'][] = $this->title;
$as = \backend\models\AuthSingleton::getInstance();
$all_roles = $as->getRoles();
$all_permissions = $as->getPermissions();
$all_rules = $as->getRules();
if (!\Yii::$app->user->can('changeAllRoles')) {
    if (($key = array_search('changeAllRoles', $all_permissions)) !== false) {
        unset($all_permissions[$key]);
    }
    if (($key = array_search('Главный', $all_roles)) !== false) {
        unset($all_roles[$key]);
    }
}
if (($key = array_search('Default', $all_roles)) !== false) {
    unset($all_roles[$key]);
}
?>
<div style="background: white; border-radius: 5px; padding: 10px">
    <?php
    $form = \yii\bootstrap\ActiveForm::begin();
    echo $form->field($model, 'name')->textInput()->label('Название роли');
    echo $form->field($model, 'description')->textInput()->label('Описание роли')
    ?>
    <label>Правило</label>
    <select name="rule">
        <option value="" selected="selected">Отсутствует</option>
        <?php foreach ($all_rules as $key => $rule): ?>
            <option value="<?= $rule ?>"><?= $rule ?></option>
        <?php endforeach; ?>
    </select>

    <div><label>Наследуемые разрешения:</label></div>
    <table class="table">
        <thead>
        <th style="width: 10%">Активно</th>
        <th style="width: 20%">Название</th>
        <th>Описание</th>
        </thead>
        <?php foreach ($all_permissions as $permission): ?>
            <tr>
                <td><input type="checkbox" name="permissions[]" value="<?= $permission ?>"></td>
                <td><?= $permission ?></td>
                <td><?= $as->getItemDescription($permission) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>

    <div><label>Наследуемые роли:</label></div>
    <table class="table">
        <thead>
        <th style="width: 10%">Активно</th>
        <th style="width: 20%">Название</th>
        <th>Описание</th>
        </thead>
        <?php foreach ($all_roles as $role): ?>
            <tr>
                <td><input type="checkbox" name="roles[]" value="<?= $role ?>"></td>
                <td><?= $role ?></td>
                <td><?= $as->getItemDescription($role) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
    <div class="text-center">
        <button type="submit" class="btn btn-success">Создать роль</button>
        <button type="reset" class="btn btn-danger">Сброс</button>
    </div>
    <?php $form::end(); ?>
</div>
