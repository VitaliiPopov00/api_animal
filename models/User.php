<?php

namespace app\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\db\Expression;
use yii\web\IdentityInterface;

/**
 * This is the model class for table "user".
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string|null $phone
 * @property string|null $token
 * @property string $password
 * @property string|null $remember_token
 * @property string|null $created_at
 * @property string|null $updated_at
 *
 * @property Order[] $orders
 */
class User extends \yii\db\ActiveRecord implements IdentityInterface
{
    const SCENARIO_LOGIN = 'login';
    const SCENARIO_REGISTER = 'register';

    public $password_repeat;
    public $confirm;

    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => ['created_at'],
                    ActiveRecord::EVENT_BEFORE_UPDATE => ['updated_at'],
                ],
                'value' => new Expression('NOW()'),
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'user';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['email', 'password'], 'required'],

            [['name', 'phone'], 'required', 'on' => static::SCENARIO_REGISTER],

            [['name'], 'match', 'pattern' => '/^[А-Яа-яёЁ\s\-]+$/u', 'on' => static::SCENARIO_REGISTER],
            [['phone'], 'match', 'pattern' => '/^(?:\+7|8)[\d]{10}$/'],
            [['email'], 'email'],
            [['email'], 'unique', 'on' => static::SCENARIO_REGISTER],
            [['password'], 'match', 'pattern' => '/^(?=.*\d)(?=.*[a-z])(?=.*[A-Z])[a-zA-Z\d]{7,}$/', 'on' => static::SCENARIO_REGISTER],
            [['password_repeat'], 'compare', 'compareAttribute' => 'password', 'on' => static::SCENARIO_REGISTER],
            [['confirm'], 'required', 'requiredValue' => 1, 'on' => static::SCENARIO_REGISTER],

            [['created_at', 'updated_at'], 'safe'],
            [['name', 'password'], 'string', 'max' => 255],
            [['email', 'token', 'remember_token'], 'string', 'max' => 100],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Имя',
            'email' => 'E-mail',
            'phone' => 'Номер телефона',
            'token' => 'Token',
            'password' => 'Пароль',
            'password_repeat' => 'Подтверждения пароля',
            'remember_token' => 'Remember Token',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    public function getPasswordHash($password)
    {
        return Yii::$app->security->generatePasswordHash($password);
    }

    public function validatePassword($password)
    {
        return Yii::$app->security->validatePassword($password, $this->password);
    }

    /**
     * Gets query for [[Orders]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getOrders()
    {
        return $this->hasMany(Order::class, ['user_id' => 'id']);
    }

    public static function findIdentity($id)
    {
        // return static::findOne($id);
    }

    public static function findIdentityByAccessToken($token, $type = null)
    {
        return static::findOne(['token' => $token]);
    }

    public function getId()
    {
        return $this->id;
    }

    public function getAuthKey()
    {
        // return $this->authKey;
    }

    public function validateAuthKey($authKey)
    {
        // return $this->authKey === $authKey;
    }
}
