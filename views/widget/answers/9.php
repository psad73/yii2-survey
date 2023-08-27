<?php
/**
 * Created by PhpStorm.
 * User: kozhevnikov
 * Date: 10/10/2017
 * Time: 13:59
 */

use itworks24\survey\models\SurveyUserAnswer;
use kartik\date\DatePicker;
use vova07\imperavi\Widget;
use yii\helpers\Html;
use yii\helpers\Url;

/** @var $question \itworks24\survey\models\SurveyQuestion */
/** @var $form \yii\widgets\ActiveForm */
/** @var $readonly boolean */

$userAnswers = $question->userAnswers;

foreach ($question->answers as $i => $answer) {
    $userAnswer = $userAnswers[$answer->survey_answer_id] ?? (new SurveyUserAnswer());

    echo $form->field($userAnswer, "[$question->survey_question_id][$answer->survey_answer_id]survey_user_answer_value",
        [
            'template' => "<div class='survey-questions-form-field date-form-field'>{input}{label}\n{hint}\n{error}</div>",
            'labelOptions' => ['class' => 'css-label answer-text'],
        ]
    )->widget(DatePicker::classname(), [
        'options' => ['placeholder' => 'Enter event time ...'],
        'removeButton' => false,
	    'disabled' => $readonly,
        'pluginOptions' => [
            'format' => 'dd-MM-yyyy',
            'autoclose' => true
        ]
    ])->label($answer->survey_answer_name);
    // echo Html::label($answer->survey_answer_name);

    echo Html::tag('div', '', ['class' => 'clearfix']);
}