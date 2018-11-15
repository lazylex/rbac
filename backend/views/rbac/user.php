<?php

use yii\helpers\Html;

$this->title = 'Редактировать: ' . $user['name'];
$this->params['breadcrumbs'][] = ['label'=>'Пользователи','url'=>'users'];
$this->params['breadcrumbs'][] = $this->title;

echo Html::beginTag('div',['style'=>'background: white; border: solid 1px #e8e8e8; border-radius: 5px']);


$auth = \Yii::$app->authManager;
$allPermissions = $auth->getPermissions();//все возможные разрешения
$allRoles = $auth->getRoles();//все возможные роли
$userPermissions=$auth->getPermissionsByUser($user['id']);//все разрешения пользователя (включая унаследованные)
$userOriginalPermissions=[];//все разрешения пользователя (без унаследованных)
$userRoles=$auth->getRolesByUser($user['id']);//все роли пользователя


/*   Убрать из результата запроса роли и вывести в первом столбце разрешений   */
$userPrivatePermissions=\backend\models\AuthAssignment::find()->select('item_name')->where(['user_id'=>$user['id']])->asArray()->all();
foreach ($userPrivatePermissions as $permission)
{
    echo $permission['item_name'];
}
echo '<pre>'.print_r($userPrivatePermissions,true).'</pre>';



if (!\Yii::$app->user->can('changeAllRoles') && !\Yii::$app->user->can('changeRole', ['roles' => $user['roles']])) {
    echo Html::tag('div', 'У Вас нет прав для редактирования данного пользователя');
    die;
}

//echo '<pre>'.print_r($user['roles'][0]['role'],true).'</pre>';
echo Html::beginTag('div',['class'=>'row']);

echo Html::beginTag('div',['class'=>'col-md-3']);
echo Html::tag('div','Пользователь '.$user['name'],['class'=>'list-group-item list-group-item-warning']);
$treeBuilder=new backend\components\TreeBuilder\TreeBuilder();
$rebuild=true;
foreach ($user['roles'] as $userRole)
{
    $treeBuilder->BuildTree($userRole['role'],$rebuild);
    $rebuild=false;
    //echo '<pre>'.print_r($treeBuilder->tree,true).'</pre>';
    /* заполняем разрешения, принадлежащие непосредственно роли, а не ее наследникам */
    if(isset($treeBuilder->tree['roles'][$userRole['role']]['permissions']))
        foreach ($treeBuilder->tree['roles'][$userRole['role']]['permissions'] as $originalPermission)
        {
            $userOriginalPermissions[]=$originalPermission;
            //echo $ownPermission;
            //echo '<pre>'.print_r($treeBuilder->tree['roles'][$userRole['role']]['permissions'][0],true).'</pre>';
        }
    echo $treeBuilder->buildList($treeBuilder->tree);
}

echo Html::endTag('div');

echo Html::beginTag('div',['class'=>'col-md-5']);
/* Вывод таблицы разрешений */
echo Html::tag('div','Разрешения пользователя '. $user['name'],['class'=>'list-group-item list-group-item-warning']);

echo Html::beginTag('table',['class'=>'table table-bordered table-striped table-hover']);
echo Html::beginTag('thead');
echo Html::tag('th','Личные разрешения пользователя');
echo Html::tag('th','Разрешения от своих ролей');
echo Html::tag('th','Разрешения, полученные с наследуемыми ролями');
echo Html::tag('th','Название разрешения');
echo Html::tag('th','Описание разрешения');
echo Html::tag('th','Правило');
echo Html::endTag('thead');

foreach ($allPermissions as $permission)
{

    if ($permission->name=='changeAllRoles'&&(!\Yii::$app->user->can('changeAllRoles')))
        continue;
        echo Html::beginTag('tr');

        echo Html::tag('td', Html::tag('input', '',
            [
                'type' => 'checkbox',
                'checked' => in_array($permission->name, $userOriginalPermissions),
            ])) ;


        echo Html::tag('td', Html::tag('input', '',
            [
                'type' => 'checkbox',
                'checked' => in_array($permission->name, $userOriginalPermissions),
                'disabled' => true//разрешения своих ролей отменять нельзя
            ])) ;


        echo Html::tag('td', Html::tag('input', '',
             [
                 'type' => 'checkbox',
                 'checked' => in_array($permission, $userPermissions)&&!in_array($permission->name, $userOriginalPermissions),
                 'disabled' => true//наследуемые разрешения отменять нельзя
             ]));

        echo Html::tag('td', $permission->name);
        echo Html::tag('td', $permission->description);
        echo Html::tag('td', $permission->ruleName);
        echo Html::endTag('tr');

}
echo Html::endTag('table');
echo Html::endTag('div');

echo Html::beginTag('div',['class'=>'col-md-4']);
/* Вывод таблицы ролей*/
echo Html::tag('div','Роли пользователя '. $user['name'],['class'=>'list-group-item list-group-item-warning']);

echo Html::beginTag('table',['class'=>'table table-bordered table-striped table-hover']);
echo Html::beginTag('thead');
echo Html::tag('th','Активно');
echo Html::tag('th','Название');
echo Html::tag('th','Описание');
echo Html::tag('th','Правило');
echo Html::endTag('thead');

foreach ($allRoles as $role)
{
    echo Html::beginTag('tr');
    echo Html::tag('td', Html::tag('input', '', ['type' => 'checkbox', 'checked' => in_array($role, $userRoles)]));
    echo Html::tag('td', $role->name);
    echo Html::tag('td', $role->description);
    echo Html::tag('td', $role->ruleName);
    echo Html::endTag('tr');
}
echo Html::endTag('table');
echo Html::endTag('div');


echo Html::endTag('div');


echo Html::endTag('div');