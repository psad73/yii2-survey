<?php
/**
 * Created by PhpStorm.
 * User: kozhevnikov
 * Date: 10/10/2017
 * Time: 13:59
 */

use itworks24pl\survey\models\SurveyUserAnswer;
use vova07\imperavi\Widget;
use yii\bootstrap\Progress;
use yii\helpers\Html;
use yii\helpers\Url;

/** @var $question \itworks24pl\survey\models\SurveyQuestion */
/** @var $form \yii\widgets\ActiveForm */

$totalVotesCount = $question->getTotalUserAnswersCount();

echo Html::beginTag('div', ['class' => 'answers-stat']);
foreach ($question->answers as $i => $answer) {
    $count = $answer->getTotalUserAnswersCount();
    try {
        $percent = ($count / $totalVotesCount) * 100;
    }catch (\Exception $e){
        $percent = 0;
    }
    echo $answer->survey_answer_name;
    echo Progress::widget([
        'id' => 'progress-' . $answer->survey_answer_id,
        'percent' => $percent,
        'label' => $count,
        'barOptions' => ['class' => 'progress-bar-info init']
    ]);
}
echo Html::endTag('div');
