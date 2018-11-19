<?php

use yii\helpers\Html;
use backend\components\TreeBuilder\TreeBuilder;

$this->title = 'Редактировать: ' . $user['name'];
$this->params['breadcrumbs'][] = ['label' => 'RBAC', 'url' => 'index'];
$this->params['breadcrumbs'][] = ['label' => 'Пользователи', 'url' => 'users'];
$this->params['breadcrumbs'][] = $this->title;

$auth = \Yii::$app->authManager;
$allPermissions = $auth->getPermissions();//все возможные разрешения
$allRoles = $auth->getRoles();//все возможные роли
$userPermissions = $auth->getPermissionsByUser($user['id']);//все разрешения пользователя (включая унаследованные)
$userOriginalPermissions = [];//все разрешения пользователя (без унаследованных) (массив строк)
$userRoles = $auth->getRolesByUser($user['id']);//все роли пользователя
$userPrivatePermissions = [];//личные разрешения пользователя

if (!(isset($roles_selector_type) && ($roles_selector_type == 'radio' || $roles_selector_type == 'checkbox'))) {
    $roles_selector_type = 'radio';
}

if (!\Yii::$app->user->can('changeAllRoles') && !\Yii::$app->user->can('changeRole', ['roles' => $user['roles']])) {
    \Yii::$app->session->setFlash('error', "У Вас нет прав для редактирования данного пользователя");
    return $this->redirect('users');
}

?>

<div style="background: white; border: solid 1px #e8e8e8; border-radius: 5px; padding: 5px">
    <form method="post" action="' <?= \yii\helpers\Url::to(['/rbac/index']) ?> '">'

        <div class="row">
            <div class="col-md-3">
                <div class="list-group-item list-group-item-warning">Пользователь <?= $user['name'] ?></div>

                <?php

                $treeBuilder = new TreeBuilder();

                foreach ($user['roles'] as $userRole) {
                    $treeBuilder->BuildTree($userRole['role']);

                    /* заполняем разрешения, принадлежащие непосредственно роли, а не ее наследникам */
                    if (isset($treeBuilder->tree['roles'][$userRole['role']]['permissions']))
                        foreach ($treeBuilder->tree['roles'][$userRole['role']]['permissions'] as $key => $originalPermission) {
                            $userOriginalPermissions[] = $key;
                        }
                    echo $treeBuilder->buildList($treeBuilder->tree);
                }

                $PrivatePermissions = \backend\models\AuthAssignment::find()->select('item_name')->where(['user_id' => $user['id']])->asArray()->all();
                foreach ($PrivatePermissions as $permission) {
                    if ($treeBuilder->isPermission($permission['item_name'])) {
                        $userPrivatePermissions[] = $permission['item_name'];

                        $treeBuilder->BuildTree($permission['item_name']);
                        echo $treeBuilder->buildList($treeBuilder->tree);
                    }
                }
                ?>

            </div>
            <div class="col-md-5">

                <!-- Вывод таблицы разрешений -->
                <div class="list-group-item list-group-item-warning">Разрешения пользователя <?= $user['name'] ?></div>
                <table class="table table-bordered table-striped table-hover">
                    <thead>
                    <th>Личные разрешения пользователя</th>
                    <th>Разрешения от своих ролей</th>
                    <th>Разрешения, полученные с наследуемыми ролями</th>
                    <th>Название разрешения</th>
                    <th>Описание разрешения</th>
                    <th>Правило</th>
                    </thead>

                    <?php

                    foreach ($allPermissions as $permission) {

                        if ($permission->name == 'changeAllRoles' && (!\Yii::$app->user->can('changeAllRoles')))
                            continue;
                        ?>
                        <tr>
                            <td>
                                <input name="private_roles[]"
                                       type="checkbox"
                                       value="<?= $permission->name ?>"
                                    <?= in_array($permission->name, $userPrivatePermissions) ? 'checked="checked"' : '' ?>>
                            </td>
                            <td>
                                <input type="checkbox" disabled="disabled"
                                    <?= in_array($permission->name, $userOriginalPermissions) ? 'checked="checked"' : '' ?>>
                            </td>
                            <td>
                                <input type="checkbox" disabled="disabled"
                                    <?= in_array($permission, $userPermissions)
                                    && !in_array($permission->name, $userOriginalPermissions)
                                    && !in_array($permission->name, $userPrivatePermissions) ? 'checked="checked"' : '' ?>>
                            </td>
                            <td><?= $permission->name ?></td>
                            <td><?= $permission->description ?></td>
                            <td><?= $permission->ruleName ?></td>
                        </tr>
                        <?php
                    }
                    ?>
                </table>
            </div>
            <div class="col-md-4">
                <div class="list-group-item list-group-item-warning">Роли пользователя <?= $user['name'] ?></div>
                <!--Вывод таблицы ролей-->
                <table class="table table-bordered table-striped table-hover">
                    <thead>
                    <th>Активно</th>
                    <th>Название</th>
                    <th>Описание</th>
                    <th>Правило</th>
                    </thead>

                    <?php foreach ($allRoles as $role) : ?>
                        <tr>
                            <td>
                                <input name="role[]"
                                       value="<?= $role->name ?>"
                                       type="<?= $roles_selector_type ?>"
                                       <?= in_array($role, $userRoles) ? 'checked="checked"' : '' ?>
                                >
                            </td>
                            <td><?= $role->name ?></td>
                            <td><?= $role->description ?></td>
                            <td><?= $role->ruleName ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php
                    if ($roles_selector_type == 'radio') {
                        echo Html::beginTag('tr');
                        echo Html::tag('td', Html::tag('input', '', ['name' => 'role[]', 'value' => '', 'type' => $roles_selector_type, 'checked' => false]));
                        echo Html::tag('td', 'Нет ролей');
                        echo Html::tag('td', 'У пользователя нет ни одной роли');
                        echo Html::tag('td', '');
                        echo Html::endTag('tr');
                    }

                    echo '</table>';
                    echo Html::endTag('div');


                    echo Html::endTag('div');

                    echo Html::beginTag('div', ['style' => 'text-align:center']);
                    echo Html::tag('button', 'Сохранить', ['class' => 'btn btn-primary', 'type' => 'submit']);
                    echo Html::endTag('div');
                    echo Html::input("hidden", "_csrf-backend", Yii::$app->request->getCsrfToken()); ?>
    </form>
</div>