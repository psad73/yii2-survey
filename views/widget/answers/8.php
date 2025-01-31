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
$userAnswer = !empty(current($userAnswers)) ? current($userAnswers) : (new SurveyUserAnswer()) ;

echo $form->field($userAnswer, "[$question->survey_question_id]survey_user_answer_text")->textarea(
    ['placeholder' => \Yii::t('survey', 'Enter your answer here'), 'rows' => 6, 'disabled' => $readonly])->label(\Yii::t('survey', 'Answer'));

