<?php

use yii\helpers\Html;
use backend\components\TreeBuilder\TreeBuilder;

$this->title = 'Роль';
$this->params['breadcrumbs'][] = ['label' => 'RBAC', 'url' => 'index'];
$this->params['breadcrumbs'][] = ['label' => 'Роли', 'url' => 'roles'];
$this->params['breadcrumbs'][] = $this->title;

$as = \backend\models\AuthSingleton::getInstance();
$treeBuilder = new TreeBuilder();
$treeBuilder->auth_item = $as->getAuthItem();
$treeBuilder->tree = $as->getTree($name);
echo $treeBuilder->buildList($treeBuilder->tree);