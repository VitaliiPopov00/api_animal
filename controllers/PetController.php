<?php

namespace app\controllers;

use app\models\District;
use app\models\Kind;
use app\models\Order;
use app\models\Pet;
use app\models\Status;
use app\models\User;
use Yii;
use yii\filters\auth\HttpBearerAuth;
use yii\web\UploadedFile;

class PetController extends \yii\rest\ActiveController
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
            'except' => ['options', 'slider', 'search', 'last-find', 'show', 'new'],
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

    public function actionSlider()
    {
        $orders = Order::find()
            ->with(['pet', 'pet.kind'])
            ->where(['status_id' => (new Status())->getStatusId('wasFound')])
            ->all();

        if ($orders) {
            $result = [
                'data' => [
                    'pets' => [],
                ],
            ];

            foreach ($orders as $order) {
                $result['data']['pets'][] = [
                    'id' => $order->id,
                    'kind' => $order->pet->kind->kind,
                    'description' => $order->pet->description,
                    'image' => (new Pet())->getPhotoPet($order->pet->id),
                ];
            }

            Yii::$app->response->statusCode = 200;
            return $this->asJson($result);
        } else {
            Yii::$app->response->statusCode = 204;
        }
    }

    public function actionSearch()
    {
        if (Yii::$app->request->get('query')) {
            $orders = Order::find()
                ->with(['district', 'pet.kind'])
                ->innerJoin('pet', 'order.pet_id = pet.id')
                ->where(['like', 'pet.description', Yii::$app->request->get('query')])
                ->all();
        }

        if (Yii::$app->request->get('district') || Yii::$app->request->get('kind')) {
            $orders = Order::find()
            ->innerJoin('pet', 'order.pet_id = pet.id')
            ->innerJoin('district', 'order.district_id = district.id')
            ->innerJoin('kind', 'pet.kind_id = kind.id');
            
            if (Yii::$app->request->get('district')) {
                $orders->andWhere(['district.district' => Yii::$app->request->get('district')]);
            }
            
            if (Yii::$app->request->get('kind')) {
                $orders->andWhere(['like', 'kind.kind', Yii::$app->request->get('kind')]);
            }
            
            $orders = $orders->all();
        }

        if (isset($orders)) {
            $result = [
                'data' => [
                    'orders' => [],
                ],
            ];

            foreach ($orders as $order) {
                $result['data']['orders'][] = [
                    'id' => $order->id,
                    'phone' => $order->user->phone,
                    'name' => $order->user->name,
                    'email' => $order->user->email,
                    'kind' => $order->pet->kind->kind,
                    'photo' => (new Pet())->getPhotoPet($order->pet->id),
                    'description' => $order->pet->description,
                    'mark' => $order->pet->mark,
                    'district' => $order->district->district,
                    'date' => $order->created_at,
                    'registred' => $order->user->created_at ? true : false,
                ];
            }

            Yii::$app->response->statusCode = 200;
            return $this->asJson($result);
        } else {
            Yii::$app->response->statusCode = 204;
        }
    }

    public function actionLastFind()
    {
        $orders = Order::find()
            ->with(['pet', 'user', 'district'])
            ->orderBy(['created_at' => SORT_DESC])
            ->limit(6)
            ->all();

        if ($orders) {
            $result = [
                'data' => [
                    'orders' => [],
                ],
            ];

            foreach ($orders as $order) {
                $result['data']['orders'][] = [
                    'id' => $order->id,
                    'phone' => $order->user->phone,
                    'name' => $order->user->name,
                    'email' => $order->user->email,
                    'kind' => $order->pet->kind->kind,
                    'photo' => (new Pet())->getPhotoPet($order->pet->id),
                    'description' => $order->pet->description,
                    'mark' => $order->pet->mark,
                    'district' => $order->district->district,
                    'date' => $order->created_at,
                    'registred' => $order->user->password ? true : false,
                ];
            }

            Yii::$app->response->statusCode = 200;
            return $this->asJson($result);
        } else {
            Yii::$app->response->statusCode = 204;
        }
    }

    public function actionShow($id)
    {
        if ($order = Order::findOne(['id' => $id])) {

            $result = [
                'data' => [
                    'pet' => [
                        'id' => $order->id,
                        'phone' => $order->user->phone,
                        'email' => $order->user->email,
                        'name' => $order->user->name,
                        'kind' => $order->pet->kind->kind,
                        'photos' => [
                            $order->pet->photo1,
                            $order->pet->photo2,
                            $order->pet->photo3,
                        ],
                        'description' => $order->pet->description,
                        'mark' => $order->pet->mark,
                        'district' => $order->district->district,
                        'date' => $order->created_at,
                    ]
                ]
            ];

            Yii::$app->response->statusCode = 200;
            return $this->asJson($result);
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

    public function actionNew()
    {
        $data = Yii::$app->request->post();

        if (isset($data['register']) && !(User::findOne(['email' => $data['email']]))) {
            $user = new User();

            if ($user->load($data, '') && $user->validate()) {
                $user->password = $user->getPasswordHash($user->password);
                $user->save(false);
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

        if ($user = User::findOne(['email' => $data['email']])) {
            $pet = new Pet();
            $pet->kind_id = (new Kind())->getKindId($data['kind']);
            $pet->description = $data['description'];
            $pet->mark = isset($data['mark']) ? $data['mark'] : '';

            if (!empty($_FILES)) {
                foreach (array_keys($_FILES) as $file) {
                    $uploadedFile = UploadedFile::getInstanceByName($file);
                    $maxSize = 1024 * 1024 * 2;
                    $accessExtension = [
                        'png', 'jpeg', 'jpg',
                    ];

                    if (!($uploadedFile->size > $maxSize)) {
                        if (in_array($uploadedFile->extension, $accessExtension)) {
                            $filenameDublicate = Pet::find()
                                ->where("`photo1` like \"%$uploadedFile->baseName.%\"")
                                ->orWhere("`photo1` like \"%$uploadedFile->baseName (%).%\"")
                                ->where("`photo2` like \"%$uploadedFile->baseName.%\"")
                                ->orWhere("`photo2` like \"%$uploadedFile->baseName (%).%\"")
                                ->where("`photo3` like \"%$uploadedFile->baseName.%\"")
                                ->orWhere("`photo3` like \"%$uploadedFile->baseName (%).%\"")
                                ->count();

                            if ($filenameDublicate) {
                                $filename = $uploadedFile->baseName . " ({$filenameDublicate})";
                            } else {
                                $filename = $uploadedFile->baseName;
                            }

                            $pet->{$file} = Yii::getAlias('@app') . '/upload/' . $filename . '.' . $uploadedFile->extension;
                            copy($uploadedFile->tempName, $pet->{$file});
                        } else {
                            Yii::$app->response->statusCode = 422;

                            return $this->asJson([
                                'data' => [
                                    'error' => [
                                        'code' => 422,
                                        'message' => 'Validation error',
                                        'errors' => [
                                            $file => ['Invalid file format'],
                                        ]
                                    ]
                                ]
                            ]);
                        }
                    } else {
                        Yii::$app->response->statusCode = 422;

                        return $this->asJson([
                            'data' => [
                                'error' => [
                                    'code' => 422,
                                    'message' => 'Validation error',
                                    'errors' => [
                                        $file => ['File is too large']
                                    ]
                                ]
                            ]
                        ]);
                    }
                }
            }

            if ($pet->save()) {
                $order = new Order();
                $order->user_id = $user->id;
                $order->pet_id = $pet->id;
                $order->district_id = (new District())->getDistrictId($data['district']);
                $order->status_id = (new Status())->getStatusId('onModeration');

                if ($order->save()) {
                    Yii::$app->response->statusCode = 200;

                    return $this->asJson([
                        'data' => [
                            'status' => true,
                        ]
                    ]);
                }
            } else {
                Yii::$app->response->statusCode = 422;

                return $this->asJson([
                    'data' => [
                        'error' => [
                            'code' => 422,
                            'message' => 'Validation error',
                            'errors' => $pet->errors,
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
                        'message' => 'User not found',
                    ]
                ]
            ]);
        }
    }

    public function actionUpdateOrder($order_id)
    {
        $identity = Yii::$app->user->identity;

        if ($order = Order::findOne(['id' => $order_id])) {
            if ($order->user_id == $identity->id) {
                if ($order->status_id == (new Status())->getStatusId('active') || $order->status_id == (new Status())->getStatusId('onModeration')) {
                    if ($pet = Pet::findOne(['id' => $order->pet_id])) {
                        if ($pet->load(Yii::$app->request->post(), '') && $pet->validate()) {
                            if (!empty($_FILES)) {
                                foreach (array_keys($_FILES) as $file) {
                                    $uploadedFile = UploadedFile::getInstanceByName($file);
                                    $maxSize = 1024 * 1024 * 2;
                                    $accessExtension = [
                                        'png', 'jpeg', 'jpg',
                                    ];
                
                                    if (!($uploadedFile->size > $maxSize)) {
                                        if (in_array($uploadedFile->extension, $accessExtension)) {
                                            $filenameDublicate = Pet::find()
                                                ->where("`photo1` like \"%$uploadedFile->baseName.%\"")
                                                ->orWhere("`photo1` like \"%$uploadedFile->baseName (%).%\"")
                                                ->where("`photo2` like \"%$uploadedFile->baseName.%\"")
                                                ->orWhere("`photo2` like \"%$uploadedFile->baseName (%).%\"")
                                                ->where("`photo3` like \"%$uploadedFile->baseName.%\"")
                                                ->orWhere("`photo3` like \"%$uploadedFile->baseName (%).%\"")
                                                ->count();
                
                                            if ($filenameDublicate) {
                                                $filename = $uploadedFile->baseName . " ({$filenameDublicate})";
                                            } else {
                                                $filename = $uploadedFile->baseName;
                                            }
                
                                            unlink(Yii::getAlias('@app') . substr($pet->{$file}, strpos($pet->{$file}, '/upload')));
                                            $pet->{$file} = Yii::getAlias('@app') . '/upload/' . $filename . '.' . $uploadedFile->extension;
                                            copy($uploadedFile->tempName, $pet->{$file});
                                        } else {
                                            Yii::$app->response->statusCode = 422;
                
                                            return $this->asJson([
                                                'data' => [
                                                    'error' => [
                                                        'code' => 422,
                                                        'message' => 'Validation error',
                                                        'errors' => [
                                                            $file => ['Invalid file format'],
                                                        ]
                                                    ]
                                                ]
                                            ]);
                                        }
                                    } else {
                                        Yii::$app->response->statusCode = 422;
                
                                        return $this->asJson([
                                            'data' => [
                                                'error' => [
                                                    'code' => 422,
                                                    'message' => 'Validation error',
                                                    'errors' => [
                                                        $file => ['File is too large']
                                                    ]
                                                ]
                                            ]
                                        ]);
                                    }
                                }
                            }

                            if ($pet->save()) {
                                Yii::$app->response->statusCode = 200;
    
                                return $this->asJson([
                                    'data' => [
                                        'status' => true,
                                    ]
                                ]);
                            }

                        } else {
                            Yii::$app->response->statusCode = 422;

                            return $this->asJson([
                                'data' => [
                                    'error' => [
                                        'code' => 422,
                                        'message' => 'Validation error',
                                        'errors' => $pet->errors,
                                    ]
                                ]
                            ]);
                        }
                    } else {
                        Yii::$app->response->statusCode = 404;

                        return $this->asJson([
                            'data' => [
                                'error' => 404,
                                'message' => 'Not found',
                            ]
                        ]);
                    }
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
                    'error' => 404,
                    'message' => 'Not found',
                ]
            ]);
        }
    }
}
