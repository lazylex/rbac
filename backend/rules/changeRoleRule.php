<?php

namespace backend\rules;

use yii\rbac\Rule;

class changeRoleRule extends Rule
{
    public $name = "ChangeRole";

    /* Проверяем, не пытаются ли редактировать роль Главного или Заместителя*/
    public function execute($user, $item, $params)
    {

        if (isset($params['roles'])) {

            foreach ($params['roles'] as $role)
                if ($role == 'Главный'||$role == 'Заместитель')
                    return false;
        }
        else
            return false;
        return true;
    }
}