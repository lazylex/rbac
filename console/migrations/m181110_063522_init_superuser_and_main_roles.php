<?php

use yii\db\Migration;
use common\models\User;

/**
 * Миграция для создания Главного пользователя и основных ролей в RBAC
 * выполняется после миграций, необходимых для создания таблиц
 * user, auth_assinment, auth_item, auth_item_child, auth_rule
 */
class m181110_063522_init_superuser_and_main_roles extends Migration
{

    /**
     * данные для регистрации пользователя, которому будет присвоена роль "Главный"
     * проверка их корректности не производится
     */

    public $username = 'admin';
    public $email = 'ghostofcapitalism@gmail.com';
    public $password = 'letmein';

    /* Данные зама */
    public $username_deputy = 'deputy';
    public $email_deputy = 'lazylex@mail.ru';
    public $password_deputy = 'letmein13';

    /* Данные менеджера */
    public $username_manager = 'manager1';
    public $email_manager = 'leningrad@gmail.com';
    public $password_manager = 'leningrad';


    public function registerUser($username, $password, $email)
    {
        $user = new User();
        $user->username = $username;
        $user->email = $email;
        $user->setPassword($password);
        $user->generateAuthKey();
        $user->save();
        return $user;
    }

    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $auth = Yii::$app->authManager;
        $auth->removeAll();//сношу все отношения, существовавшие ранее
        /* Создаю пользователей */
        $user = $this->registerUser($this->username, $this->password, $this->email);
        $user_deputy = $this->registerUser($this->username_deputy, $this->password_deputy, $this->email_deputy);
        $user_manager = $this->registerUser($this->username_manager, $this->password_manager, $this->email_manager);

        /* Создаю разрешение на создание ролей*/
        $createRole = $auth->createPermission('createRole');
        $createRole->description = 'Может создавать роли';
        $auth->add($createRole);

        /* Создаю разрешение на изменение всех ролей */
        $changeAllRoles = $auth->createPermission('changeAllRoles');
        $changeAllRoles->description = 'Может изменять все роли';
        $auth->add($changeAllRoles);


        /* Добавляю правило, проверяющее разрешение на изменение ролей */
        $changeRoleRule = new \backend\rules\changeRoleRule();
        $auth->add($changeRoleRule);
        /* Создаю разрешение на изменение всех ролей, кроме главного */
        $changeRole = $auth->createPermission('changeRole');
        $changeRole->description = 'Может изменять все роли, кроме роли Главного';
        $changeRole->ruleName = $changeRoleRule->name;
        $auth->add($changeRole);


        /* Создаю разрешение на просмотр, удаление, редактирование статей */
        $articleFullAccess = $auth->createPermission('articleFullAccess');
        $articleFullAccess->description = 'Полный доступ к статьям';

        $auth->add($articleFullAccess);

        /* Создаю роль суперпользователя */
        $role_superuser = $auth->createRole('Главный');
        $role_superuser->description = 'Суперпользователь';
        $auth->add($role_superuser);

        /* Создаю роль заместителя */
        $role_deputy = $auth->createRole('Заместитель');
        $role_deputy->description = 'Заместитель';
        $auth->add($role_deputy);

        /* Создаю роль менеджера */
        $role_manager = $auth->createRole('Менеджер');
        $role_manager->description = 'Менеджер';
        $auth->add($role_manager);

        $auth->addChild($role_deputy, $createRole);//даем заместителю разрешение на создание ролей
        $auth->addChild($role_deputy, $changeRole);//даем заместителю разрешение на изменение ролей
        $auth->addChild($role_manager, $articleFullAccess);//даем заместителю полный доступ к статьям
        $auth->addChild($role_deputy, $role_manager);//заместитель наследует менеджера
        $auth->addChild($role_superuser, $role_deputy);//главный наследует заместителя
        $auth->addChild($role_superuser, $changeAllRoles);//главный может менять все роли
        $auth->assign($role_superuser, $user->getId());//привязка роли Главного к суперпользователю



        $auth->assign($role_deputy, $user_deputy->getId());
        $auth->assign($role_manager, $user_manager->getId());

        /* Создаю роль по умолчанию */
        $role_default = $auth->createRole('Default');
        $role_default->description = 'Роль по умолчанию. Не содержит разрешений';
        $auth->add($role_default);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m181110_063522_init_superuser_and_main_roles cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m181110_063522_init_superuser_and_main_roles cannot be reverted.\n";

        return false;
    }
    */
}
