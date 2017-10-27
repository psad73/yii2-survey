<?php
/**
 * Created by PhpStorm.
 * User: kozhevnikov
 * Date: 10/10/2017
 * Time: 13:59
 */

use common\modules\survey\models\SurveyUserAnswer;
use yii\helpers\Html;


/** @var $question \common\modules\survey\models\SurveyQuestion */
/** @var $form \yii\widgets\ActiveForm */

$userAnswers = $question->userAnswers;
$userAnswer = !empty(current($userAnswers)) ? current($userAnswers) : (new SurveyUserAnswer()) ;
$radioList = [];

foreach ($question->answers as $i => $answer) {

    $radioList[$answer->survey_answer_id] = '<div class="text"><div class="name">' . $answer->survey_answer_name . '</div>';
    if ($answer->survey_answer_show_descr){
         $radioList[$answer->survey_answer_id] .= '<br>' .$answer->survey_answer_descr;
    }
    $radioList[$answer->survey_answer_id] .= '</div>';
}

echo $form->field($userAnswer, "[$question->survey_question_id]survey_user_answer_value", ['template' => "{label}\n<div class='survey-questions-form-field radio-group'>{input}</div>\n{hint}\n{error}"])->radioList($radioList, ['encode' => false])->label(false);
echo Html::tag('br', '');