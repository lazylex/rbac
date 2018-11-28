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
use yii\web\Controller;
use yii\db\Query;
use yii\base\DynamicModel;

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
        $user_count = (new Query())->select('id')->from('user')->count();
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

    public function actionCreateRole()
    {
        $model = new DynamicModel(['name', 'description']);

        $model
            ->addRule(['name'], 'required', ['message' => 'Необходимо ввести название роли'])
            ->addRule('name', 'string',
                [
                    'max' => 64,
                    'min' => 3,
                    'tooLong' => 'Название роли не может быть длиннее 64 символов',
                    'tooShort' => 'Название роли не может быть короче трех символов'
                ])
            ->addRule('description', 'string',
                [
                    'max' => 128,
                    'min' => 3,
                    'tooLong' => 'Описание не может быть длиннее 128 символов',
                    'tooShort' => 'Описание не может быть короче трех символов'
                ]);

        if ($model->load(\Yii::$app->request->post()) && $model->validate()) {
            // проводим любые действия
            $as = AuthSingleton::getInstance();
            $auth = \Yii::$app->authManager;
            $new_role = $auth->createRole($model->name);
            if ($model->description != '') {
                $new_role->description = $model->description;
            }
            $rule = \Yii::$app->request->post('rule');
            if (!is_null($rule) && in_array($rule, $as->getRules())) {
                $new_role->ruleName=$rule;
            }
            $auth->add($new_role);
            $roles = \Yii::$app->request->post('roles');

            if (!is_null($roles)) {
                foreach ($roles as $role) {
                    if ($role == 'Главный') {
                        if (\Yii::$app->user->can('changeAllRoles')) {
                            $auth->addChild($new_role, $auth->getRole($role));//если разрешат наследовать роль главного
                        }
                    } else {
                        if ($as->isRole($role)) {
                            $auth->addChild($new_role, $auth->getRole($role));
                        }
                    }
                }
            }
            $permissions = \Yii::$app->request->post('permissions');
            if (!is_null($permissions)) {
                foreach (\Yii::$app->request->post('permissions') as $permission) {
                    if ($permission == 'changeAllRoles') {
                        if (\Yii::$app->user->can('changeAllRoles')) {
                            $auth->addChild($new_role, $auth->getPermission($permission));//если разрешат наследовать
                        }
                    } else {
                        if ($as->isPermission($permission)) {
                            $auth->addChild($new_role, $auth->getPermission($permission));
                        }
                    }
                }
            }
            \Yii::$app->session->setFlash('success', "Роль {$model->name} успешно создана");
            return $this->redirect('roles');
        }

        return $this->render('create-role', ['model' => $model]);
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
        //echo \Yii::$app->requestedRoute; die;
        $id = \Yii::$app->request->post('id');
        $identity = User::findIdentity($id);
        if ($identity == null) {
            \Yii::$app->session->setFlash('error', "Пользователь не выбран или не существует");
            return $this->redirect('users');
        }
        $private_permissions = \Yii::$app->request->post('private_permissions');
        $roles = \Yii::$app->request->post('roles');
        if (isset($private_permissions) || isset($roles)/*||\Yii::$app->requestedRoute=='rbac/user'*/) {// || потому, что даже роли можно пользователя лишить
            $urap = new UserRolesAndPermissions();
            $urap->setUserById($id);
            $urap->setUserRoles($roles);
            $urap->setUserPrivatePermissions($private_permissions);

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
                'roles_selector_type' => 'radio',
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
        $role_deputy = $auth->createRole('Default');
        $role_deputy->description = 'Роль по умолчанию. Не содержит разрешений';
        //$auth->add($role_deputy);
        //$per = $auth->createPermission('Зарплата');
        //$per->description = 'Платить зарплату';
        $auth->add($role_deputy);
        //$auth->addChild($auth->getRole('Консильери'),$role_deputy);

        //$auth->addChild($role_deputy,$auth->getPermission('boy'));//даем заместителю разрешение на создание ролей

        //$pizza = $auth->getPermission('pizza');

        //$auth->addChild($pizza, $role_deputy);
        //$auth->addChild($role_deputy, $per);

        echo 'new';
    }
}
