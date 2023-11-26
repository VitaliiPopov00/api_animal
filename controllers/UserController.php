<?php

namespace app\controllers;

use app\models\Order;
use app\models\Pet;
use app\models\Status;
use app\models\Subscription;
use app\models\User;
use Yii;
use yii\filters\auth\HttpBearerAuth;

class UserController extends \yii\rest\ActiveController
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
            'except' => ['options', 'subscription', 'register', 'login'],
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

    public function actionSubscription()
    {
        if (User::findOne(['email' => Yii::$app->request->post('email')])) {
            if (!Subscription::findOne(['email' => Yii::$app->request->post('email')])) {
                $subscription = new Subscription();
                $subscription->email = Yii::$app->request->post('email');
                $subscription->save();
                Yii::$app->response->statusCode = 204;
            } else {
                Yii::$app->response->statusCode = 409;
                return $this->asJson([
                    'data' => [
                        'error' => [
                            'code' => 409,
                            'message' => 'Пользователь уже имеет подписку',
                        ]
                    ]
                ]);
            }
        } else {
            Yii::$app->response->statusCode = 404;
            return $this->asJson([
                'data' => [
                    'error' => [
                        'code' => 404,
                        'message' => 'Пользователь не найден',
                    ]
                ]
            ]);
        }
    }

    public function actionRegister()
    {
        $user = new User(['scenario' => User::SCENARIO_REGISTER]);

        if ($user->load(Yii::$app->request->post(), '') && $user->validate()) {
            $user->password = $user->getPasswordHash($user->password);
            $user->save(false);
            Yii::$app->response->statusCode = 204;
        } else {
            Yii::$app->response->statusCode = 422;

            return $this->asJson([
                'data' => [
                    'error' => [
                        'code' => 422,
                        'message' => 'Validation error',
                        'errors' => $user->errors
                    ]
                ]
            ]);
        }
    }

    public function actionLogin()
    {
        $user = new User();

        if ($user->load(Yii::$app->request->post(), '') && $user->validate()) {
            $password = $user->password;

            if (($user = User::findOne(['email' => $user->email])) && $user->validatePassword($password)) {
                $user->token = Yii::$app->security->generateRandomString();
                $user->save(false);

                Yii::$app->response->statusCode = 200;
                return $this->asJson([
                    'data' => [
                        'token' => $user->token
                    ]
                ]);
            } else {
                Yii::$app->response->statusCode = 401;
            }
        } else {
            Yii::$app->response->statusCode = 422;

            return $this->asJson([
                'data' => [
                    'error' => [
                        'code' => 422,
                        'message' => 'Validation error',
                        'errors' => $user->errors
                    ]
                ]
            ]);
        }
    }

    public function actionInfo()
    {
        $identity = Yii::$app->user->identity;

        if (($user = User::findOne(['email' => $identity->email])) && $user->password) {
            $ordersCount = Order::find()
                ->where(['user_id' => $user->id])
                ->count();

            $petsCount = Order::find()
                ->where(['user_id' => $user->id])
                ->andWhere(['status_id' => (new Status())->getStatusId('wasFound')])
                ->count();

            Yii::$app->response->statusCode = 200;

            return $this->asJson([
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'phone' => $user->phone,
                        'email' => $user->email,
                        'name' => $user->name,
                        'registrationDate' => $user->created_at,
                        'ordersCount' => $ordersCount,
                        'petsCount' => $petsCount,
                    ]
                ]
            ]);
        } else {
            Yii::$app->response->statusCode = 401;
        }
    }

    public function actionChangePhone()
    {
        $identity = Yii::$app->user->identity;

        if ($user = User::findOne(['id' => $identity->id])) {
            $user->phone = Yii::$app->request->post('phone');

            if ($user->save()) {
                Yii::$app->response->statusCode = 200;

                return $this->asJson([
                    'data' => [
                        'status' => true,
                    ]
                ]);
            } else {
                Yii::$app->response->statusCode = 422;

                return $this->asJson([
                    'data' => [
                        'error' => [
                            'code' => 422,
                            'message' => 'Validation error',
                            'errors' => $user->errors,
                        ]
                    ]
                ]);
            }
        }
    }

    public function actionChangeEmail()
    {
        $identity = Yii::$app->user->identity;

        if ($user = User::findOne(['id' => $identity->id])) {
            $user->email = Yii::$app->request->post('email');

            if ($user->save()) {
                Yii::$app->response->statusCode = 200;

                return $this->asJson([
                    'data' => [
                        'status' => true,
                    ]
                ]);
            } else {
                Yii::$app->response->statusCode = 422;

                return $this->asJson([
                    'data' => [
                        'error' => [
                            'code' => 422,
                            'message' => 'Validation error',
                            'errors' => $user->errors,
                        ]
                    ]
                ]);
            }
        }
    }

    public function actionOrder()
    {
        $identity = Yii::$app->user->identity;

        if ($user = User::findOne(['id' => $identity->id])) {
            $orders = [];
            $result = [
                'data' => [
                    'orders' => []
                ]
            ];

            $orders['active'] = Order::find()
                ->with(['pet', 'district', 'pet.kind'])
                ->where(['user_id' => $user->id])
                ->andWhere(['status_id' => (new Status())->getStatusId('active')])
                ->asArray()
                ->all();

            $orders['wasFound'] = Order::find()
                ->with(['pet', 'district', 'pet.kind'])
                ->where(['user_id' => $user->id])
                ->andWhere(['status_id' => (new Status())->getStatusId('wasFound')])
                ->asArray()
                ->all();

            $orders['onModeration'] = Order::find()
                ->with(['pet', 'district', 'pet.kind'])
                ->where(['user_id' => $user->id])
                ->andWhere(['status_id' => (new Status())->getStatusId('onModeration')])
                ->asArray()
                ->all();

            $orders['archive'] = Order::find()
                ->with(['pet', 'district', 'pet.kind'])
                ->where(['user_id' => $user->id])
                ->andWhere(['status_id' => (new Status())->getStatusId('archive')])
                ->asArray()
                ->all();

            foreach ($orders as $status => $order) {
                foreach($order as $orderDetail) {
                    $result['data']['orders'][$status][] = [
                        'id' => $orderDetail['id'],
                        'kind' => $orderDetail['pet']['kind']['kind'],
                        'photo' => (new Pet())->getPhotoPet($orderDetail['pet']['id']),
                        'description' => $orderDetail['pet']['description'],
                        'mark' => $orderDetail['pet']['mark'],
                        'district' => $orderDetail['district']['district'],
                        'date' => $orderDetail['created_at'],
                    ];
                }
            }

            if ($result['data']['orders']) {
                Yii::$app->response->statusCode = 200;
                return $this->asJson($result);
            } else {
                Yii::$app->response->statusCode = 204;
            }
        }
    }

    public function actionDeleteOrder($order_id)
    {
        $identity = Yii::$app->user->identity;

        if ($user = User::findOne(['id' => $identity->id])) {
            if ($order = Order::findOne(['id' => $order_id])) {
                if ($order->user_id == $user->id) {
                    if ($order->status_id == (new Status())->getStatusId('active') || $order->status_id == (new Status())->getStatusId('onModeration')) {
                        $order->delete();
                        
                        Yii::$app->response->statusCode = 200;

                        return $this->asJson([
                            'data' => [
                                'status' => true,
                            ]
                        ]);
                    } else {
                        Yii::$app->response->statusCode = 403;
                    }
                } else {
                    Yii::$app->response->statusCode = 403;
                }
            } else {
                Yii::$app->response->statusCode = 404;

                return $this->asJson([
                    'data' => [
                        'error' => [
                            'code' => 404,
                            'message' => 'Not found',
                        ]
                    ]
                ]);
            }
        }
    }
}
