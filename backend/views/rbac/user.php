<?php

use yii\helpers\Html;

//чтобы не делать лишних запросов, передаю username через get (параметр для красоты и на редактирование не влияет)
$this->title = 'Редактировать: ' . $user['name'];
$this->params['breadcrumbs'][] = $this->title;
?>
    <div>

    <h1><?= Html::encode($this->title) ?></h1>

<?php



$auth = \Yii::$app->authManager;
$allPermissions = $auth->getPermissions();
$allRoles = $auth->getRoles();

if (!\Yii::$app->user->can('changeAllRoles') && !\Yii::$app->user->can('changeRole', ['roles' => $user['roles']])) {
    echo Html::tag('div', 'У Вас нет прав для редактирования данного пользователя');
    die;
}
/*
foreach ($user['roles'] as $role) {
    echo $role_name = $role['role'];
    $childRoles = $auth->getChildRoles($role_name);
    foreach ($childRoles as $childRole) {
        // Чтобы не выводить собственую роль, как дочернюю
        //if ($role_name != $childRole->name)
         {
            echo '<br>    ' . $childRole->name;
            $role_permissions = $auth->getPermissionsByRole($childRole->name);

            foreach ($role_permissions as $permission) {
                echo '<br>        ' . $permission->name;
            }
        }
    }


}*/
echo Html::beginTag('div',['class'=>'row']);
echo Html::beginTag('div',['class'=>'col-2', 'style'=>'width: 1000px']);
$tr=new backend\components\TreeBuilder\TreeBuilder();
$tr->BuildTree($user['roles'][0]['role']);
echo Html::endTag('div');
echo Html::endTag('div');
//echo '<pre>' . print_r($user['roles'], true) . '</pre>';
//    echo '<pre>'.print_r($user['roles'],true).'</pre>';
//echo '<pre>'.print_r($user['permissions'],true).'</pre>';