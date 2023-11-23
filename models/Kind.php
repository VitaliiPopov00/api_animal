<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "kind".
 *
 * @property int $id
 * @property string $kind
 *
 * @property Pet[] $pets
 */
class Kind extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'kind';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['kind'], 'required'],
            [['kind'], 'string', 'max' => 15],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'kind' => 'Kind',
        ];
    }

    public function getKindId($kind)
    {
        return Kind::findOne(['kind' => $kind])->id;
    }

    /**
     * Gets query for [[Pets]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPets()
    {
        return $this->hasMany(Pet::class, ['kind_id' => 'id']);
    }
}
