<?php
/**
 * Created by PhpStorm.
 * User: Anonimus
 * Date: 20.11.2018
 * Time: 7:52
 */

namespace backend\models;


use yii\base\Model;

/**
 * Class UserRolesAndPermissions
 * @package backend\models
 * @property integer $id идентификатор пользователя
 * @property array $auth_item таблица с ролями и правами, позволяет делать меньше запросов к БД
 */
class UserRolesAndPermissions extends Model
{
    public $id;
    private $auth_item = [];

    public function setAuthItem($auth_item)
    {
        $this->auth_item = $auth_item;
    }

    public function rules()
    {
        return [
            [['id'], 'required'],
            [['id'], 'integer'],
        ];
    }
}