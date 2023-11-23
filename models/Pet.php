<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "pet".
 *
 * @property int $id
 * @property string $mark
 * @property string $photo1
 * @property string|null $photo2
 * @property string|null $photo3
 * @property string $description
 * @property int $kind_id
 *
 * @property Kind $kind
 * @property Order[] $orders
 */
class Pet extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'pet';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['photo1', 'description', 'kind_id'], 'required'],
            [['description'], 'string'],
            [['kind_id'], 'integer'],
            [['mark'], 'string', 'max' => 100],
            [['photo1', 'photo2', 'photo3'], 'string', 'max' => 255],
            [['kind_id'], 'exist', 'skipOnError' => true, 'targetClass' => Kind::class, 'targetAttribute' => ['kind_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'mark' => 'Mark',
            'photo1' => 'Photo1',
            'photo2' => 'Photo2',
            'photo3' => 'Photo3',
            'description' => 'Description',
            'kind_id' => 'Kind ID',
        ];
    }

    public function getPhotoPet($id)
    {
        $pet = Pet::findOne(['id' => $id]);
        $result = [];

        for ($i = 1; $i < 4; $i++) {
            $photo = 'photo' . $i;

            if ($pet->{$photo}) {
                $result[] = $pet->{$photo};
            }
        }

        return $result;
    }

    /**
     * Gets query for [[Kind]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getKind()
    {
        return $this->hasOne(Kind::class, ['id' => 'kind_id']);
    }

    /**
     * Gets query for [[Orders]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getOrder()
    {
        return $this->hasOne(Order::class, ['pet_id' => 'id']);
    }
}
