<?php

namespace app\controllers;

use app\models\District;
use Yii;
use yii\filters\auth\HttpBearerAuth;

class DistrictController extends \yii\rest\ActiveController
{
    public $modelClass = '';
    public $enableCsrfValidation = false;

    public function behaviors()
    {
        $behaviors = parent::behaviors();

        // remove authentication filter
        $auth = $behaviors['authenticator'];
        unset($behaviors['authenticator']);

        // add CORS filter
        $behaviors['corsFilter'] = [
            'class' => \yii\filters\Cors::class,
            'cors' => [
                'Origin' => [
                    (isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : 'http://' . $_SERVER['REMOTE_ADDR'])
                ],
                'Access-Control-Request-Headers' => ['content-type', 'Authorization'],
                'Access-Control-Request-Method' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
            ],
            'actions' => [
                'login' => [
                    'Access-Control-Allow-Credentials' => true,
                ]
            ],
        ];

        $auth = [
            'class' => HttpBearerAuth::class,
            'except' => ['options', 'district'],
        ];

        // re-add authentication filter
        $behaviors['authenticator'] = $auth;

        return $behaviors;
    }

    public function actions()
    {
        $actions = parent::actions();

        unset($actions['delete'], $actions['create'], $actions['view'], $actions['update'], $actions['index']);

        return $actions;
    }

    public function actionDistrict()
    {
        Yii::$app->response->statusCode = 200;

        return $this->asJson([
            'data' => [
                'districts' => District::find()->all(),
            ]
        ]);
    }
}
