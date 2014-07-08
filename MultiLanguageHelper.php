<?php
namespace app\webvimark\behaviors\multilanguage;


use Yii;
use yii\web\Cookie;

class MultiLanguageHelper
{

	/**
	 * catchLanguage
	 *
	 * Changing language depending on the $_GET['_language'] parameter
	 *
	 * Used in base Controller in init() function
	 *
	 * @stolen from http://www.yiiframework.com/wiki/294/seo-conform-multilingual-urls-language-selector-widget-i18n/
	 */
	public static function catchLanguage()
	{
		if(isset($_POST['_language_selector']))
		{
			$lang = $_POST['_language_selector'];
			$MultilangReturnUrl = $_POST[$lang];
			Yii::$app->controller->redirect($MultilangReturnUrl);
		}

		if(isset($_GET['_language']))
		{
			Yii::$app->language = $_GET['_language'];
			Yii::$app->session->set('_language', $_GET['_language']);

			$cookie = new Cookie([
				'name' => '_language',
				'value' => $_GET['_language'],
				'expire' => time() + (3600*24*7), // 7 days
			]);

			Yii::$app->response->cookies->add($cookie);
		}
		elseif (Yii::$app->session->has('_language'))
		{
			Yii::$app->language = Yii::$app->session->get('_language');
		}

	}
}