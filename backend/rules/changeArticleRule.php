<?php

namespace backend\rules;

use yii\rbac\Rule;

/**
 * Проверяем authorID на соответствие с пользователем, переданным через параметры
 * Взято с https://yiiframework.com.ua/ru/doc/guide/2/security-authorization/
 */
class changeArticleRule extends Rule
{
    public $name = 'ChangeArticle';

    /**
     * @param string|int $user the user ID.
     * @param Item $item the role or permission that this rule is associated width.
     * @param array $params parameters passed to ManagerInterface::checkAccess().
     * @return bool a value indicating whether the rule permits the role or permission it is associated with.
     */
    public function execute($user, $item, $params)
    {
        return isset($params['article']) ? $params['article']->createdBy == $user : false;
    }
}