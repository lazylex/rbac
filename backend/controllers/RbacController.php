<?php
/**
 * Created by PhpStorm.
 * User: Lex
 * Date: 08.11.2018
 * Time: 19:46
 */

namespace backend\controllers;


use backend\models\AuthAssignment;
use common\models\User;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\Controller;

class RbacController extends Controller
{

    public $layout='rbac';

    public function behaviors()
    {
        return [
            'access' => [
                'class' => \yii\filters\AccessControl::className(),
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['Главный', 'Заместитель']
                    ],
                ],
            ],
        ];
    }

    public function actionIndex()
    {
        echo 'welcome';
        echo '<ul>';
        echo '<li>' . Html::a('Пользователи', Url::to(['users'])) . '</li>';
        echo '<li>' . Html::a('Роли', Url::to(['roles'])) . '</li>';
        echo '<li>' . Html::a('auth_assignment', Url::to(['/auth-assignment'])) . '</li>';
        echo '<li>' . Html::a('auth_item', Url::to(['/auth-item'])) . '</li>';
        echo '<li>' . Html::a('auth_item-child', Url::to(['/auth-item-child'])) . '</li>';
        echo '<li>' . Html::a('auth_rule', Url::to(['/auth-rule'])) . '</li>';
        echo '</ul>';
    }

    public function actionUser()
    {
        $id = \Yii::$app->request->post('id');
        $identity = User::findIdentity($id);
        if ($identity == null) {
            echo 'Пользователь с таким ID не существует';
            die();
        }
        $user['name'] = $identity->username;

        $roles = \Yii::$app->authManager->getRolesByUser($id);
        if (count($roles) > 0) {
            foreach ($roles as $role)
                $user['roles'][] =
                    [
                        'role' => $role->name,
                        'description' => $role->description,
                        'rule' => $role->ruleName
                    ];
        } else
            $user['roles'] = [];
        $user['id']=$id;
        $permissions = \Yii::$app->authManager->getPermissionsByUser($id);
        if (count($permissions) > 0) {
            foreach ($permissions as $permission)
                $user['permissions'][] =
                    [
                        'permission' => $permission->name,
                        'description' => $permission->description,
                        'rule' => $permission->ruleName
                    ];
        }
        return $this->render('user', ['user' => $user]);
    }

    public function actionUsers()
    {
        $users = User::find()->select(['id', 'username', 'status', 'created_at'])->asArray()->all();

        /* добавляю массиву пользователей поля, содержащее массивы названий ролей и прав*/
        foreach ($users as &$user) {

            $roles = \Yii::$app->authManager->getRolesByUser($user['id']);

            foreach ($roles as $role)
                $user['roles'][] = $role->name;

            $permissions = \Yii::$app->authManager->getPermissionsByUser($user['id']);

            foreach ($permissions as $permission)
            {
                $user['permissions'][] = ['name'=>$permission->name , 'description'=>$permission->description];
            }
        }


        if (count($users) > 0)
            return $this->render('users', ['users' => $users]);
        return 'Нет пользователей';
    }

    public function actionNew()
    {
        $auth = \Yii::$app->authManager;
        //$auth->removeAll();//сношу все отношения, существовавшие ранее
        /* Создаю пользователей */

        /* Создаю разрешение на создание ролей*/
    /*    $createRole1 = $auth->createPermission('boy');
        $createRole1->description = 'Ничего не может';
        $auth->add($createRole1);*/


        /* Создаю роль заместителя */
        //$role_deputy = $auth->createRole('Повелитель');
        //$role_deputy->description = 'Самый главный человек';
        //$auth->add($role_deputy);

        //$auth->addChild($auth->getRole('Консильери'),$role_deputy);

        //$auth->addChild($role_deputy,$auth->getPermission('boy'));//даем заместителю разрешение на создание ролей

        $pizza=$auth->createPermission('pizza');
        $pizza->description="Может заказывать на обед пиццу за счет фирмы";
        $auth->add($pizza);

$auth->assign($pizza,31);


        echo 'new';
    }
}
