<?php
/**
 * Created by PhpStorm.
 * User: kozhevnikov
 * Date: 05/10/2017
 * Time: 14:24
 */

use kartik\editable\Editable;
use kartik\helpers\Html;
use wbraganca\dynamicform\DynamicFormWidget;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $survey \itworks24pl\survey\models\Survey */
/* @var $withUserSearch boolean */

$this->title = Yii::t('survey', 'Update survey');


echo $this->render('_form', [
	'survey' => $survey,
	'withUserSearch' => $withUserSearch
]);