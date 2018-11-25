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
use backend\models\AuthSingleton;
use backend\models\UserRolesAndPermissions;
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
        $as = AuthSingleton::getInstance();
        $name = \Yii::$app->request->get('name');//поменять на пост (не забыть поменять и ссылки в виде ролей)
        if ($name == null || !$as->isRole($name)) {
            \Yii::$app->session->setFlash('error', "Роль не выбрана или не существует");
            return $this->redirect('roles');
        }
        return $this->render('role', ['name' => $name]);
    }

    public function actionRoles()
    {
        $as = AuthSingleton::getInstance();
        $all_roles = $as->getRoles();
        foreach ($all_roles as $role) {
            $roles[$role]['description'] = $as->getItemDescription($role);
            $roles[$role]['rule'] = $as->getItemRule($role);
        }
        //echo '<pre>'.print_r($roles).'</pre>';die;
        return $this->render('roles', ['roles' => $roles]);
    }

    public function actionUser()
    {
        $id = \Yii::$app->request->post('id');
        $identity = User::findIdentity($id);
        if ($identity == null) {
            \Yii::$app->session->setFlash('error', "Пользователь не выбран или не существует");
            return $this->redirect('users');
        }
        $private_permissions = \Yii::$app->request->post('private_permissions');
        $roles = \Yii::$app->request->post('roles');
        if (isset($private_permissions) || isset($roles)) {// || потому, что даже роли можно пользователя лишить
            //echo 'die';die;
            $urar = new UserRolesAndPermissions();
            $urar->setUserById($id);
            $urar->setUserRoles($roles);
            $urar->setUserPrivatePermissions($private_permissions);

            return $this->redirect('users');
        }


        $user['name'] = $identity->username;
        $as = AuthSingleton::getInstance();
        $allPermissions = $as->getPermissions();//все возможные разрешения
        $allRoles = $as->getRoles();//все возможные роли
        $userPrivatePermissions = $as->getPrivatePermissionsByUser($id);//разрешения, принадлежащие пользователю, а не его ролям
        $userPermissions = $as->getPermissionsByUser($id);//все разрешения пользователя (включая унаследованные)
        $userOriginalPermissions = [];//все разрешения пользователя (без унаследованных) (массив строк)
        $userRoles = $as->getRolesByUser($id);

        //$roles = $as->getRolesByUser($id);
        if (count($userRoles) > 0) {
            foreach ($userRoles as $role)
                $user['roles'][] =
                    [
                        'role' => $role,
                        'description' => $as->getItemDescription($role),
                        'rule' => $as->getItemRule($role)
                    ];
        } else
            $user['roles'] = [];
        $user['id'] = $id;
        /* При запрете на владение пользователем многими ролями, в roles_selector_type передать radio
        или вообще не передавать эту переменную. При разрешении на владение многими ролями передать checkbox
         */
        return $this->render('user',
            [
                'user' => $user,
                'roles_selector_type' => 'checkbox',
                'allPermissions' => $allPermissions,
                'allRoles' => $allRoles,
                'userPrivatePermissions' => $userPrivatePermissions,
                'userPermissions' => $userPermissions,
                'userOriginalPermissions' => $userOriginalPermissions,
                'userRoles' => $userRoles,
                'as' => $as,
            ]);
    }

    public function actionUsers()
    {
        $as = AuthSingleton::getInstance();
        $users = User::find()->select(['id', 'username', 'status', 'created_at'])->asArray()->all();

        /* добавляю массиву пользователей поля, содержащее массивы названий ролей и прав*/
        foreach ($users as &$user) {

            $roles = $as->getRolesByUser($user['id']);

            foreach ($roles as $role)
                $user['roles'][] = $role;

            $permissions = $as->getPermissionsByUser($user['id']);

            foreach ($permissions as $permission) {
                $user['permissions'][] = ['name' => $permission, 'description' => $as->getItemDescription($permission)];
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
        $role_deputy = $auth->getRole('Владелец');
        //$role_deputy->description = 'Владелец домена';
        //$auth->add($role_deputy);
        $per = $auth->createPermission('Зарплата');
        $per->description = 'Платить зарплату';
        $auth->add($per);
        //$auth->addChild($auth->getRole('Консильери'),$role_deputy);

        //$auth->addChild($role_deputy,$auth->getPermission('boy'));//даем заместителю разрешение на создание ролей

        //$pizza = $auth->getPermission('pizza');

        //$auth->addChild($pizza, $role_deputy);
        $auth->addChild($role_deputy, $per);

        echo 'new';
    }
}
