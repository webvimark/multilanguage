<?php

namespace webvimark\behaviors\multilanguage\language_selector_widget;


use yii\base\Widget;
use Yii;

class LanguageSelector extends Widget
{
	const DROPDOWN = 'dropDown';
	const INLINE = 'inline';

	/**
	 * 'dropDown' or 'inline'
	 *
	 * @var string
	 */
	public $viewFile = self::DROPDOWN;

	/**
	 * @var string
	 */
	public $wrapperClass;

	/**
	 * @var string
	 */
	public $dropDownClass = 'form-control';

	/**
	 * @return string|void
	 */
	public function run()
	{
		$languages = Yii::$app->params['mlConfig']['languages'];

		if (count($languages) > 1)
		{
			return $this->render($this->viewFile, [
				'languages'=>$languages,
				'dropDownClass'=>$this->dropDownClass,
				'wrapperClass'=>$this->wrapperClass,
			]);
		}
	}
}