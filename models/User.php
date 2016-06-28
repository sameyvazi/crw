<?php

namespace app\models;

use Yii;
use yii\web\IdentityInterface;

/**
 * This is the model class for table "users".
 *
 * @property integer $id
 * @property string $username
 * @property string $password
 * @property string $email
 * @property string $name
 * @property string $family
 * @property string $mobile
 * @property string $password_reset
 * @property integer $superuser
 * @property integer $status
 */
class User extends \yii\db\ActiveRecord implements IdentityInterface
{

    const STATUS_ACTIVE = 1;
    const STATUS_DISABLE = 0;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'users';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['superuser', 'status'], 'integer'],
            [['username', 'password', 'email', 'name', 'family', 'mobile', 'password_reset'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'username' => 'Username',
            'password' => 'Password',
            'email' => 'Email',
            'name' => 'Name',
            'family' => 'Family',
            'mobile' => 'Mobile',
            'password_reset' => 'Password Reset',
            'superuser' => 'Superuser',
            'status' => 'Status',
        ];
    }

    public static function findIdentity($id)
    {
        return static::findOne($id);
    }

    public static function findIdentityByAccessToken($token, $type = null)
    {
        return Token::getUser($token, Token::TYPE_LOGIN);
    }

    public static function findByUsername($username)
    {
        return User::find()->where(['username'=>$username])->one();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getAuthKey()
    {
        return $this->authKey;
    }

    public function validateAuthKey($authKey)
    {
        return $this->authKey === $authKey;
    }

    public function beforeSave($insert)
    {
        if ($insert){
            if (Yii::$app->params['verification'] == true){
                $this->status = self::STATUS_DISABLE;
            }else{
                $this->status = self::STATUS_ACTIVE;
            }
            return parent::beforeSave($insert); // TODO: Change the autogenerated stub
        }else{
            return true;
        }
    }

    public function afterSave($insert, $changedAttributes)
    {
        if($insert){
            if ($this->status == self::STATUS_DISABLE && Yii::$app->params['emailValidation']){
                $token = Token::createToken($this, Token::TYPE_ACTIVE_ACCOUNT);
                $text = '<a href ="'.Yii::$app->params['frontedBaseUrl'].'rsp?token='.base64_encode($this->id.'-'.$token->token).'">Email Validation</a>';
                Yii::$app->email->sendEmail($this->email, '', 'Email Validation', $text);
            }elseif ($this->status == self::STATUS_DISABLE && Yii::$app->params['mobileValidation']){
                $token = Token::createToken($this, Token::TYPE_ACTIVE_ACCOUNT,'small');
                $text = $this->id.'-'.$token->token;

                Yii::$app->sms->sendSms();
            }
            parent::afterSave($insert, $changedAttributes); // TODO: Change the autogenerated stub
        }
    }
}