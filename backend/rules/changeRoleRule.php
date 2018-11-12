<?php

namespace backend\rules;

use yii\rbac\Rule;

class changeRoleRule extends Rule
{
    public $name = "ChangeRole";

    /* Проверяем, не пытаются ли редактировать роль главного */
    public function execute($user, $item, $params)
    {

        if (isset($params['roles'])) {

            foreach ($params['roles'] as $role)
                if ($role == 'Главный')
                    return false;
        }
        else
            return false;
        return true;
    }
}