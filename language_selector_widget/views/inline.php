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

	<?php foreach($languages as $lang => $langName): ?>
		<?php if($lang == Yii::$app->language): ?>

			<span class='language-selector-active'><?= $langName; ?></span>

		<?php else: ?>

			<span class='language-selector-notactive'>
                                <?= Html::a(
					$langName,
					MultiLanguageHelper::createMultilanguageReturnUrl($lang),
					array('class'=>'')
				); ?>
                        </span>

		<?php endif ?>
	<?php endforeach ?>

</div>
