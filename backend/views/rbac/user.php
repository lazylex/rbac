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
$userPerm=$auth->getPermissionsByUser($user['id']);

if (!\Yii::$app->user->can('changeAllRoles') && !\Yii::$app->user->can('changeRole', ['roles' => $user['roles']])) {
    echo Html::tag('div', 'У Вас нет прав для редактирования данного пользователя');
    die;
}

$tr=new backend\components\TreeBuilder\TreeBuilder();
$tr->BuildTree($user['roles'][0]['role']);
echo $tr->buildList($tr->tree);




echo Html::beginTag('table',['class'=>'table']);
echo Html::tag('thead');
echo Html::tag('th','');
echo Html::tag('th','Название');
echo Html::tag('th','Описание');
echo Html::tag('th','Правило');
foreach ($allPermissions as $permission)
{
    if ($permission->name=='changeAllRoles'&&!\Yii::$app->user->can('changeAllRoles'))
        continue;
        echo Html::beginTag('tr');
        if ($permission->name == 'changeAllRoles')
            echo Html::tag('td', Html::tag('input', '', ['type' => 'checkbox', 'checked' => in_array($permission, $userPerm), 'disabled' => true])) ;
        else
            echo Html::tag('td', Html::tag('input', '', ['type' => 'checkbox', 'checked' => in_array($permission, $userPerm)]));

        echo Html::tag('td', $permission->name);
        echo Html::tag('td', $permission->description);
        echo Html::tag('td', $permission->ruleName);
        echo Html::endTag('tr');

}