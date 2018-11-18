<?php

use yii\helpers\Html;

$this->title = 'Редактировать: ' . $user['name'];
$this->params['breadcrumbs'][] = ['label' => 'Пользователи', 'url' => 'users'];
$this->params['breadcrumbs'][] = $this->title;

echo Html::beginTag('div', [ 'style' => 'background: white; border: solid 1px #e8e8e8; border-radius: 5px; padding: 5px']);
echo '<form method="post" target="_self">';

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

//echo '<pre>'.print_r($user['roles'][0]['role'],true).'</pre>';
echo Html::beginTag('div', ['class' => 'row']);

echo Html::beginTag('div', ['class' => 'col-md-3']);
echo Html::tag('div', 'Пользователь ' . $user['name'], ['class' => 'list-group-item list-group-item-warning']);
$treeBuilder = new backend\components\TreeBuilder\TreeBuilder();


//echo '<pre>'.print_r($userPrivatePermissions,true).'</pre>';

foreach ($user['roles'] as $userRole) {
    $treeBuilder->BuildTree($userRole['role']);

    //echo '<pre>'.print_r($treeBuilder->tree,true).'</pre>';
    /* заполняем разрешения, принадлежащие непосредственно роли, а не ее наследникам */
    if (isset($treeBuilder->tree['roles'][$userRole['role']]['permissions']))
        foreach ($treeBuilder->tree['roles'][$userRole['role']]['permissions'] as $originalPermission) {
            $userOriginalPermissions[] = $originalPermission;

        }
    echo $treeBuilder->buildList($treeBuilder->tree);
}
/*    вывести в первом столбце разрешений   */
$PrivatePermissions = \backend\models\AuthAssignment::find()->select('item_name')->where(['user_id' => $user['id']])->asArray()->all();
foreach ($PrivatePermissions as $permission) {
    if ($treeBuilder->isPermission($permission['item_name'])) {
        $userPrivatePermissions[] = $permission['item_name'];
        /*echo '<li class="list-group-item list-group-item-info">'
            . '<span class="glyphicon glyphicon-lock"></span> '
            . $permission['item_name'] . ' <span class="label label-info">' . $allPermissions[$permission['item_name']]->description . '</span></li>';*/
        $treeBuilder->BuildTree($permission['item_name']);
        echo $treeBuilder->buildList($treeBuilder->tree);
    }
}
//echo '<pre>'.print_r($treeBuilder->tree,true).'</pre>';
echo Html::endTag('div');

echo Html::beginTag('div', ['class' => 'col-md-5']);
/* Вывод таблицы разрешений */
echo Html::tag('div', 'Разрешения пользователя ' . $user['name'], ['class' => 'list-group-item list-group-item-warning']);

echo Html::beginTag('table', ['class' => 'table table-bordered table-striped table-hover']);
echo Html::beginTag('thead');
echo Html::tag('th', 'Личные разрешения пользователя');
echo Html::tag('th', 'Разрешения от своих ролей');
echo Html::tag('th', 'Разрешения, полученные с наследуемыми ролями');
echo Html::tag('th', 'Название разрешения');
echo Html::tag('th', 'Описание разрешения');
echo Html::tag('th', 'Правило');
echo Html::endTag('thead');

foreach ($allPermissions as $permission) {

    if ($permission->name == 'changeAllRoles' && (!\Yii::$app->user->can('changeAllRoles')))
        continue;
    echo Html::beginTag('tr');

    echo Html::tag('td', Html::tag('input', '',
        [
            'name'=>'private_roles[]',
            'value'=>$permission->name,
            'type' => 'checkbox',
            'checked' => in_array($permission->name, $userOriginalPermissions),
        ]));


    echo Html::tag('td', Html::tag('input', '',
        [
            'type' => 'checkbox',
            'checked' => in_array($permission->name, $userOriginalPermissions),
            'disabled' => true//разрешения своих ролей отменять нельзя
        ]));


    echo Html::tag('td', Html::tag('input', '',
        [
            'type' => 'checkbox',
            'checked' => in_array($permission, $userPermissions) && !in_array($permission->name, $userOriginalPermissions),
            'disabled' => true//наследуемые разрешения отменять нельзя
        ]));

    echo Html::tag('td', $permission->name);
    echo Html::tag('td', $permission->description);
    echo Html::tag('td', $permission->ruleName);
    echo Html::endTag('tr');

}
echo Html::endTag('table');
echo Html::endTag('div');

echo Html::beginTag('div', ['class' => 'col-md-4']);
/* Вывод таблицы ролей*/
echo Html::tag('div', 'Роли пользователя ' . $user['name'], ['class' => 'list-group-item list-group-item-warning']);

echo Html::beginTag('table', ['class' => 'table table-bordered table-striped table-hover']);
echo Html::beginTag('thead');
echo Html::tag('th', 'Активно');
echo Html::tag('th', 'Название');
echo Html::tag('th', 'Описание');
echo Html::tag('th', 'Правило');
echo Html::endTag('thead');

foreach ($allRoles as $role) {
    echo Html::beginTag('tr');
    echo Html::tag('td', Html::tag('input', '', ['name' => 'role[]', 'value' => $role->name, 'type' => $roles_selector_type, 'checked' => in_array($role, $userRoles)]));
    echo Html::tag('td', $role->name);
    echo Html::tag('td', $role->description);
    echo Html::tag('td', $role->ruleName);
    echo Html::endTag('tr');
}
echo Html::endTag('table');
echo Html::endTag('div');


echo Html::endTag('div');

echo Html::beginTag('div',['style'=>'text-align:center']);
echo Html::tag('button','Сохранить',['class'=>'btn btn-primary','type'=>'submit','target'=>'rbac/user','method'=>'post']);
echo Html::endTag('div');

echo '</form>';

echo Html::endTag('div');