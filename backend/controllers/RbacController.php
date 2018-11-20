<?php
/**
 * Created by PhpStorm.
 * User: Lex
 * Date: 08.11.2018
 * Time: 19:46
 */

namespace backend\controllers;


use backend\models\AuthAssignment;
use backend\models\AuthItem;
use backend\models\AuthItemChild;
use backend\models\AuthRule;
use common\models\User;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\Controller;

class RbacController extends Controller
{

    public $layout = 'rbac';

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
        $role_count = count(\Yii::$app->authManager->getRoles());
        $user_count = (new \yii\db\Query())->select('id')->from('user')->count();
        $auth_assignment_count = AuthAssignment::find()->count();
        $auth_item_count = AuthItem::find()->count();
        $auth_item_child_count = AuthItemChild::find()->count();
        $auth_rule_count = AuthRule::find()->count();
        return $this->render('index',
            [
                'role_count' => $role_count,
                'user_count' => $user_count,
                'auth_assignment_count' => $auth_assignment_count,
                'auth_item_count' => $auth_item_count,
                'auth_item_child_count' => $auth_item_child_count,
                'auth_rule_count' => $auth_rule_count,
            ]);
    }

    public function actionRole()
    {
        return $this->render('role');
    }

    public function actionRoles()
    {
        return $this->render('roles');
    }

    public function actionUser()
    {
        $id = \Yii::$app->request->post('id');
        $identity = User::findIdentity($id);
        if ($identity == null) {
            \Yii::$app->session->setFlash('error', "Пользователь не выбран или не существует");
            return $this->redirect('users');
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
        $user['id'] = $id;
        /*$permissions = \Yii::$app->authManager->getPermissionsByUser($id);
        if (count($permissions) > 0) {
            foreach ($permissions as $permission)
                $user['permissions'][] =
                    [
                        'permission' => $permission->name,
                        'description' => $permission->description,
                        'rule' => $permission->ruleName
                    ];
        }*/
        /* При запрете на владение пользователем многими ролями, в roles_selector_type передать radio
        или вообще не передавать эту переменную. При разрешении на владение многими ролями передать checkbox
         */
        return $this->render('user', ['user' => $user, 'roles_selector_type' => 'checkbox']);
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

            foreach ($permissions as $permission) {
                $user['permissions'][] = ['name' => $permission->name, 'description' => $permission->description];
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
        $role_deputy = $auth->createPermission('Ассорти');
        $role_deputy->description = 'Ассорти';
        $auth->add($role_deputy);

        //$auth->addChild($auth->getRole('Консильери'),$role_deputy);

        //$auth->addChild($role_deputy,$auth->getPermission('boy'));//даем заместителю разрешение на создание ролей

        $pizza = $auth->getPermission('pizza');

        $auth->addChild($pizza, $role_deputy);


        echo 'new';
    }
}
