<?php
/**
 * @var View $this
 * @var string $dropDownClass
 * @var string $wrapperClass
 * @var array $languages
 * @var boolean $forBootstrapNavbar
 * @var boolean $useFullLanguageName
 */

use webvimark\behaviors\multilanguage\MultiLanguageHelper;
use yii\helpers\Html;
use yii\web\View;

?>

<?php if ( $forBootstrapNavbar ): ?>

	<ul class="nav navbar-nav <?= $wrapperClass ?>">
		<li class="dropdown">
			<a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false">
				<?= $useFullLanguageName ? @$languages[Yii::$app->language] : Yii::$app->language ?>
				<span class="caret"></span></a>
			<ul class="dropdown-menu" role="menu">
				<?php foreach ($languages as $langCode => $langName): ?>
					<?php $langName = $useFullLanguageName ? $langName : $langCode ?>
					<?php if ( $langCode != Yii::$app->language ): ?>
						<li><?= Html::a($langName, MultiLanguageHelper::createMultilanguageReturnUrl($langCode)) ?></li>

					<?php endif; ?>

				<?php endforeach ?>
			</ul>
		</li>
	</ul>

<?php else: ?>
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

<?php endif; ?>

