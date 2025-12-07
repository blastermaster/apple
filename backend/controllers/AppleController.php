<?php

namespace backend\controllers;

use backend\models\Apple;
use backend\models\AppleSearch;
use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use yii\db\Exception;

/**
 * AppleController implements the CRUD actions for Apple model.
 */
class AppleController extends Controller
{
    /**
     * @inheritDoc
     */
    public function behaviors()
    {
        return array_merge(
            parent::behaviors(),
            [
                'access' => [
                    'class' => AccessControl::class,
                    'rules' => [
                        [
                            'allow' => true,
                            'roles' => ['@'],
                        ],
                    ],
                ],
                'verbs' => [
                    'class' => VerbFilter::class,
                    'actions' => [
                        'delete' => ['POST'],
                        'fall' => ['POST'],
                        'eat' => ['POST'],
                        'rotten' => ['POST'],
                    ],
                ],
            ]
        );
    }

    /**
     * Lists all Apple models.
     *
     * @return string
     */
    public function actionIndex()
    {
        $searchModel = new AppleSearch();
        $dataProvider = $searchModel->search($this->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionGenerate()
    {
        $transaction = Yii::$app->db->beginTransaction();
        try {
            Yii::$app->db->createCommand()->truncateTable(Apple::tableName())->execute();

            $count = rand(10, 100);
            $created = 0;

            for ($i = 0; $i < $count; $i++) {
                $color = Apple::generateRandomColor();
                $apple = new Apple($color);
                $apple->appeared_at = Apple::generateRandomAppearedAt();
                $apple->status = Apple::generateRandomStatus();
                $apple->eaten_percent = 0;

                if ($apple->status === Apple::STATUS_FELL) {
                    $apple->fell_at = time() - rand(0, 4) * 3600;
                }

                if ($apple->save(false)) {
                    $created++;
                }
            }

            $transaction->commit();
            Yii::$app->session->setFlash('success', "Создано {$created} яблок из {$count}.");
        } catch (Exception $e) {
            $transaction->rollBack();
            Yii::$app->session->setFlash('error', 'Ошибка при создании яблок: ' . $e->getMessage());
        }

        return $this->redirect(['index', 'AppleSearch' => []]);
    }

    /**
     * Deletes an existing Apple model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param int $id ID
     * @return \yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionFall()
    {
        $response = Yii::$app->response;
        $response->format = Response::FORMAT_JSON;
        
        $id = $this->request->post('id');
        if (!$id) {
            $response->data = [
                'success' => false,
                'message' => 'Отсутствует параметр id',
            ];
            return $response;
        }
        
        try {
            $apple = $this->findModel($id);
            $apple->fall();
            
            $response->data = [
                'success' => true,
                'message' => 'Яблоко упало',
                'status' => $apple->status,
                'fell_at' => $apple->fell_at,
            ];
            return $response;
        } catch (Exception $e) {
            $response->data = [
                'success' => false,
                'message' => $e->getMessage(),
            ];
            return $response;
        }
    }

    public function actionEat()
    {
        $response = Yii::$app->response;
        $response->format = Response::FORMAT_JSON;
        
        $id = $this->request->post('id');
        $percent = $this->request->post('percent');
        
        if (!$id || !$percent) {
            $response->data = [
                'success' => false,
                'message' => 'Отсутствуют обязательные параметры: id, percent',
            ];
            return $response;
        }
        
        try {
            $apple = $this->findModel($id);
            $apple->eat((float)$percent);
            
            $response->data = [
                'success' => true,
                'message' => 'Яблоко съедено',
                'eaten_percent' => $apple->eaten_percent,
                'size' => $apple->size,
                'deleted' => $apple->isNewRecord,
            ];
            return $response;
        } catch (Exception $e) {
            $response->data = [
                'success' => false,
                'message' => $e->getMessage(),
            ];
            return $response;
        }
    }

    public function actionRotten()
    {
        $response = Yii::$app->response;
        $response->format = Response::FORMAT_JSON;
        
        $id = $this->request->post('id');
        if (!$id) {
            $response->data = [
                'success' => false,
                'message' => 'Отсутствует параметр id',
            ];
            return $response;
        }
        
        try {
            $apple = $this->findModel($id);
            $apple->makeRotten();
            
            $response->data = [
                'success' => true,
                'message' => 'Яблоко испортилось',
                'status' => $apple->status,
            ];
            return $response;
        } catch (Exception $e) {
            $response->data = [
                'success' => false,
                'message' => $e->getMessage(),
            ];
            return $response;
        }
    }

    public function actionDelete($id)
    {
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }

    /**
     * Finds the Apple model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param int $id ID
     * @return Apple the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Apple::findOne(['id' => $id])) !== null) {
            return $model;
        }

        throw new NotFoundHttpException(Yii::t('app', 'The requested page does not exist.'));
    }
}
