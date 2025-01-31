<?php

namespace itworks24pl\survey\controllers;

use itworks24pl\survey\models\search\SurveySearch;
use itworks24pl\survey\models\search\SurveyStatSearch;
use itworks24pl\survey\models\Survey;
use itworks24pl\survey\models\SurveyAnswer;
use itworks24pl\survey\models\SurveyQuestion;
use itworks24pl\survey\models\SurveyStat;
use Imagine\Image\Box;
use itworks24pl\survey\SurveyInterface;
use Yii;
use yii\base\Model;
use yii\base\UserException;
use yii\data\ActiveDataProvider;
use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\imagine\Image;
use yii\web\BadRequestHttpException;
use yii\web\Controller;
use yii\web\HttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\web\UploadedFile;
use yii\widgets\ActiveForm;

/**
 * Default controller for the `survey` module
 */
class DefaultController extends Controller
{

    /**
     * Renders the index view for the module
     * @return string
     */
    public function actionIndex()
    {
        $searchModel = new SurveySearch();
        $dataProvider = $searchModel->search(\Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionDelete($id)
    {

        Yii::$app->response->format = Response::FORMAT_JSON;
        $survey = $this->findModel($id);
        if ($survey->delete()) {
            $basepath = $this->module->params['uploadsPath'];
            $path = \Yii::getAlias($basepath) . '/' . $id;
            FileHelper::removeDirectory($path);
        }
        return ['forceClose' => true, 'forceReload' => '#survey-index-pjax'];
    }

    public function actionView($id)
    {
        $survey = $this->findModel($id);

        $searchModel = new SurveyStatSearch();
        $dataProvider = $searchModel->search(\Yii::$app->request->queryParams);
        $dataProvider->query->andWhere(['survey_stat_survey_id' => $survey->survey_id])
            ->orderBy(['survey_stat_assigned_at' => SORT_DESC]);

        $dataProvider->pagination->pageSize = 10;

        $restrictedUserDataProvider = new ActiveDataProvider([
        	'query' => $survey->getRestrictedUsers()
        ]);
	    $restrictedUserDataProvider->pagination->pageSize = 10;

        return $this->render('view', [
        	'survey' => $survey,
	        'searchModel' => $searchModel,
	        'dataProvider' => $dataProvider,
	        'restrictedUserDataProvider' => $restrictedUserDataProvider,
	        'withUserSearch' => $this->allowUserSearch()
        ]);
    }

    public function actionCreate()
    {
        $survey = new Survey();
        $survey->survey_name = \Yii::t('survey', 'New Survey');
        $survey->survey_is_closed = true;
        $survey->save(false);
        \Yii::$app->session->set('surveyUploadsSubpath', $survey->survey_id);

        return $this->render('create', [
        	'survey' => $survey,
	        'withUserSearch' => $this->allowUserSearch()
        ]);
    }

    public function actionRespondents($surveyId)
    {
        $searchModel = new SurveyStatSearch();
        $dataProvider = $searchModel->search(\Yii::$app->request->queryParams);
        $dataProvider->query->andWhere(['survey_stat_survey_id' => $surveyId])
            ->orderBy(['survey_stat_assigned_at' => SORT_DESC]);

        $dataProvider->pagination->pageSize = 10;

        if (\Yii::$app->request->isPjax) {
            $dataProvider->pagination->route = Url::toRoute(['default/respondents']);
            return $this->renderAjax('respondents', [
            	'searchModel' => $searchModel,
	            'dataProvider' => $dataProvider,
	            'surveyId' => $surveyId,
	            'withUserSearch' => $this->allowUserSearch()
            ]);
        }

        \Yii::$app->response->format = Response::FORMAT_JSON;

        return [
            'title' => "Assigned respondents",
            'content' => $this->renderAjax('respondents',
                compact('searchModel', 'dataProvider', 'surveyId')
            ),
            'footer' => Html::button('Close', ['class' => 'btn btn-default', 'data-dismiss' => "modal"])
        ];
    }

	public function actionRestrictedUsers($surveyId)
	{
		$survey = $this->findModel($surveyId);
		$restrictedUserDataProvider = new ActiveDataProvider([
			'query' => $survey->getRestrictedUsers()
		]);
		$restrictedUserDataProvider->pagination->pageSize = 10;

		return [
			'title' => "RestrictedUsers",
			'content' => $this->renderAjax('restrictedUsers',
				compact('restrictedUserDataProvider', 'surveyId')
			),
			'footer' => Html::button('Close', ['class' => 'btn btn-default', 'data-dismiss' => "modal"])
		];
	}

    /**
     * Returns user models founded by token
     *
     * @param $token string
     * @param $surveyId
     * @return User
     */
    public function actionGetRespondentsByToken($token, $surveyId)
    {
        $userClass = $this->module->userClass;
        $userList = $userClass::actionGetRespondentsByToken($token);
        $userList = ArrayHelper::map(
        	$userList,
	        'id',
	        function ($user) {
	            return [
	                'id' => $user->id,
	                'text' => $user->fullname,
	                'isAssigned' => false,
	                'isRestricted' => false
		        ];
	        });
        $ids = ArrayHelper::getColumn($userList, 'id');
        $assignedRespondents = SurveyStat::find()->where(['survey_stat_survey_id' => $surveyId])
            ->andWhere(['survey_stat_user_id' => $ids])->asArray()->all();

        foreach ($assignedRespondents as $item) {
            $userList[$item['survey_stat_user_id']]['isAssigned'] = true;
        }

        return json_encode($userList);
    }

   /**
     * Returns user models founded by token
     *
     * @param $token string
     * @param $surveyId
     * @return User
     */
    public function actionSearchRespondentsByToken($token)
    {
        $userClass = $this->module->userClass;
        $userList = $userClass::actionGetRespondentsByToken($token);
        $userList = ArrayHelper::map(
        	$userList,
	        'id',
	        function ($user) {
	            return [
	                'id' => $user->id,
	                'text' => $user->fullname,
	                'isAssigned' => false
		        ];
	        });
        $ids = ArrayHelper::getColumn($userList, 'id');

        return json_encode([
        	'results' => array_values($userList)
        ]);
    }

    /**
     * @param $surveyId
     * @return bool
     */
    public function actionAssignUser($surveyId)
    {
        \Yii::$app->response->format = Response::FORMAT_JSON;
        $userId = \Yii::$app->request->post('userId');

        return SurveyStat::assignUser($userId, $surveyId);
    }

    /**
     * @param $surveyId
     * @return array|string
     */
    public function actionUnassignUser($surveyId)
    {
        $userId = \Yii::$app->request->post('userId');
        SurveyStat::unassignUser($userId, $surveyId);

        return $this->actionRespondents($surveyId);
    }

	/**
	 * @param $surveyId
	 * @return bool
	 */
	public function actionAssignRestrictedUser($surveyId)
	{
		\Yii::$app->response->format = Response::FORMAT_JSON;
		$userId = \Yii::$app->request->post('userId');

		return Survey::assignRestrictedUser($userId, $surveyId);
	}

	/**
	 * @param $surveyId
	 * @return array|string
	 */
	public function actionUnassignRestrictedUser($surveyId)
	{
		$userId = \Yii::$app->request->post('userId');
		Survey::unassignRestrictedUser($userId, $surveyId);

		return $this->actionRestrictedUsers($surveyId);
	}

	public function actionUpdate($id)
    {
        $survey = $this->findModel($id);
        \Yii::$app->session->set('surveyUploadsSubpath', $id);

        if (\Yii::$app->request->isPjax) {
            if ($survey->load(\Yii::$app->request->post()) && $survey->validate()) {
                $survey->save();
	            $survey->unlinkAll('restrictedUsers', true);

	            $post = \Yii::$app->request->post('Survey');
	            if (array_key_exists('restrictedUserIds', $post) && is_array($post['restrictedUserIds']))
	            {
		            $userClass = \Yii::$app->user->identityClass;
		            $restrictedUsers = $userClass::findAll($post['restrictedUserIds']);
		            foreach ($restrictedUsers as $restrictedUser) {
			            $survey->link('restrictedUsers', $restrictedUser);
		            }
	            }
                return $this->renderAjax('update', [
                	'survey' => $survey,
	                'withUserSearch' => $this->allowUserSearch()
                ]);
            }
        }

        return $this->render('update', [
        	'survey' => $survey,
	        'withUserSearch' => $this->allowUserSearch()
        ]);
    }


    public function actionUpdateEditable($property)
    {
        $model = $this->findModel(\Yii::$app->request->post('id'));

        // Check if there is an Editable ajax request
        if (isset($_POST['hasEditable'])) {
            // use Yii's response format to encode output as JSON
            \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

            // read your posted model attributes
            if ($model->load($_POST)) {
                // read or convert your posted information
                if ($model->validate() && $model->save()) {
                    // return JSON encoded output in the below format
                    return ['output' => $model->$property, 'message' => ''];
                }

                return ['output' => '', 'message' => $model->getFirstError($property)];
            } // else if nothing to do always return an empty JSON encoded output
            else {
                return ['output' => '', 'message' => ''];
            }
        }

        throw new BadRequestHttpException();
    }

    public function actionUpdateImage($id)
    {
        $model = $this->findModel($id);
        $model['imageFile'] = UploadedFile::getInstance($model, 'imageFile');

        $validate = ActiveForm::validate($model);
        if (\Yii::$app->request->isAjax && !empty($validate)) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return $validate;
        }

        if (Yii::$app->request->isPost && $model->validate()) {
            $imageFile = ArrayHelper::getValue($model, 'imageFile');
            if (!empty($imageFile)) {
                $subpath = \Yii::$app->session->get('surveyUploadsSubpath', '');
                $name = $subpath . '/' . uniqid() . '.' . pathinfo($imageFile->name, PATHINFO_EXTENSION);
                $cropInfo = json_decode(Yii::$app->request->post('imageFile_data'), true);
                try {
                    $tmpimg = Image::autorotate($imageFile->tempName);
                    if ($cropInfo['x'] < 0) $cropInfo['x'] = 0;
                    if ($cropInfo['y'] < 0) $cropInfo['y'] = 0;
                    $image = Image::crop(
                        $tmpimg,
                        intval($cropInfo['width']),
                        intval($cropInfo['height']),
                        [$cropInfo['x'], $cropInfo['y']]
                    )->resize(
                        new Box(400, 400)
                    );

                } catch (\Exception $e) {
                    Yii::$app->session->setFlash("error", $e->getMessage());
                }

                //upload and save db

                $basepath = $this->module->params['uploadsPath'];
                $path = \Yii::getAlias($basepath) . '/' . $name;
                $path = FileHelper::normalizePath($path);
                FileHelper::createDirectory(\Yii::getAlias($basepath) . '/' . $subpath);
                if (isset($image) && $image->save($path, ['png_compression_level' => 5, 'jpeg_quality' => 90])) {
                    $oldPath = \Yii::getAlias($basepath) . '/' . $model->survey_image;
                    $oldPath = FileHelper::normalizePath($oldPath);
                    if (!empty($model->survey_image) && file_exists($oldPath)) {
                        unlink($oldPath);
                    }
                    $model->survey_image = $name;
                    $model->save();
                } else {
                    Yii::$app->session->setFlash("warning", 'Ошибка загрузки изображения.');
                }
            }
            return $this->renderPartial('update', ['survey' => $model]);

        } else {
            if ($model->hasErrors()) {
                Yii::$app->session->setFlash("error", "Ошибка сохранения " . current($model->getFirstErrors()));
            }
        }

        return true;
    }

	/**
	 * @return bool
	 */
	public function allowUserSearch(): bool {
		return is_subclass_of($this->module->userClass, SurveyInterface::class);
	}

	protected function findModel($id)
    {
        if (($model = Survey::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }


}
