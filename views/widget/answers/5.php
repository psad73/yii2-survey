<?php
/**
 * Created by PhpStorm.
 * User: kozhevnikov
 * Date: 10/10/2017
 * Time: 13:59
 */

use itworks24pl\survey\models\SurveyUserAnswer;
use kartik\slider\Slider;
use vova07\imperavi\Widget;
use yii\helpers\Html;
use yii\helpers\Url;

/** @var $question \itworks24pl\survey\models\SurveyQuestion */
/** @var $form \yii\widgets\ActiveForm */
/** @var $readonly boolean */

$userAnswers = $question->userAnswers;
$userAnswer = !empty(current($userAnswers)) ? current($userAnswers) : (new SurveyUserAnswer());
$min = isset($question->answers[0]->survey_answer_name) ? intval($question->answers[0]->survey_answer_name) : 0;
$max = isset($question->answers[1]->survey_answer_name) ? intval($question->answers[1]->survey_answer_name) : 100;

$userAnswer->survey_user_answer_value = intval($userAnswer->survey_user_answer_value);

echo $form->field($userAnswer, "[$question->survey_question_id]survey_user_answer_value",
    ['template' => '<div class="survey-questions-form-field"><div class="badges"><b class="badge pull-left">' . $min . '</b> <b class="badge pull-right">' . $max . '</b></div>' . "{label}{input}\n{error}</div>"])->widget(Slider::classname(), [
    'id' => 'slider',
    'sliderColor' => Slider::TYPE_GREY,
    'pluginOptions' => [
        'min' => $min,
        'max' => $max,
        'now' => 0,
        'step' => 1,
        'range' => false,
    ],
	'options' => [
	    'disabled' => $readonly,
    ],
])->label(false);

