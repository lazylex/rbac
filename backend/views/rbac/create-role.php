<?php

use yii\helpers\Html;

$this->title = 'Создать роль';
$this->params['breadcrumbs'][] = ['label' => 'RBAC', 'url' => 'index'];
$this->params['breadcrumbs'][] = ['label' => 'Роли', 'url' => 'roles'];
$this->params['breadcrumbs'][] = $this->title;
$as = \backend\models\AuthSingleton::getInstance();
$all_roles = $as->getRoles();
$all_permissions = $as->getPermissions();
if (!\Yii::$app->user->can('changeAllRoles')) {
    if (($key = array_search('changeAllRoles', $all_permissions)) !== false) {
        unset($all_permissions[$key]);
    }
    if (($key = array_search('Главный', $all_roles)) !== false) {
        unset($all_roles[$key]);
    }
}
?>

<form method="post" style="background: white; border-radius: 5px; padding: 10px">
    <table class="table">
        <tr>
            <td style="width: 10%">
                <label>Название роли</label>
            </td>
            <td>
                <input type="text" name="role_name" size="30">
            </td>
        </tr>
        <tr>
            <td>
                <label>Описание роли</label>
            </td>
            <td>
                <input type="text" name="role_description" size="30">
            </td>
        </tr>
    </table>

    <div>Наследуемые разрешения:</div>
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

    <div>Наследуемые роли:</div>
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
    <input type="hidden" name="_csrf-backend" value="<?= Yii::$app->request->getCsrfToken() ?>">
</form>