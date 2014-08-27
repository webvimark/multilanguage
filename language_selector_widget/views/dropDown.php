<?php
/**
 * @var View $this
 * @var string $dropDownClass
 * @var string $wrapperClass
 * @var array $languages
 */
use webvimark\behaviors\multilanguage\MultiLanguageHelper;
use yii\helpers\Html;
use yii\web\View;

?>

<div class='<?= $wrapperClass; ?>'>

	<?= Html::beginForm() ?>

	<?php foreach($languages as $lang => $langName): ?>
		<?= Html::hiddenInput($lang, MultiLanguageHelper::createMultilanguageReturnUrl($lang)) ?>
	<?php endforeach ?>

	<?= Html::dropDownList(
		'_language_selector',
		Yii::$app->language,
		$languages,
		[
			'class'=>$dropDownClass,
			'onchange'=>'submit({d:"aa"})',
		]
	) ?>

	<?= Html::endForm() ?>
</div>
