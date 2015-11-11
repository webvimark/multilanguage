<?php
namespace webvimark\behaviors\multilanguage;

use Yii;
use yii\helpers\Url;
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
		if ( php_sapi_name() == 'cli' )
		{
			return;
		}

		if ( isset($_POST['_language_selector']) )
		{
			$lang = $_POST['_language_selector'];
			$MultilangReturnUrl = $_POST[$lang];
			Yii::$app->controller->redirect($MultilangReturnUrl);
			Yii::$app->end();
		}

		$availableLanguages = array_keys(Yii::$app->params['mlConfig']['languages']);

		if ( isset($_GET['_language']) && in_array($_GET['_language'], $availableLanguages) ) // From URL
		{
			Yii::$app->language = $_GET['_language'];

			static::saveLanguage(Yii::$app->language);

		}
		elseif ( Yii::$app->session->has('_language') ) // From session
		{
			Yii::$app->language = Yii::$app->session->get('_language');

			static::saveLanguage(Yii::$app->language);

		}
		elseif ( Yii::$app->response->cookies->has('_language') ) // From cookies
		{
			Yii::$app->language = Yii::$app->response->cookies->get('_language')->value;

			static::saveLanguage(Yii::$app->language);

		}
		else // From browser settings
		{
			Yii::$app->language = Yii::$app->request->getPreferredLanguage($availableLanguages);

			static::saveLanguage(Yii::$app->language);
		}

	}

	/**
	 * Save language in session and cookie if already stored value differs from provided language
	 *
	 * @param string $language
	 */
	protected static function saveLanguage($language)
	{
		if ( Yii::$app->session->get('_language') != $language )
		{
			Yii::$app->session->set('_language', $language);
		}

		if ( !Yii::$app->response->cookies->get('_language') || Yii::$app->response->cookies->get('_language')->value != $language )
		{
			$cookie = new Cookie([
				'name' => '_language',
				'value' => $language,
				'expire' => time() + (3600*24*30), // 30 days
			]);

			Yii::$app->response->cookies->add($cookie);
		}
	}


	/**
	 * createMultilanguageReturnUrl
	 *
	 * @param string $lang
	 * @return string
	 *
	 * @stolen from http://www.yiiframework.com/wiki/294/seo-conform-multilingual-urls-language-selector-widget-i18n/
	 */
	public static function createMultilanguageReturnUrl($lang)
	{
		if (count($_GET) > 0)
		{
			$arr = $_GET;
			$arr['_language']= $lang;
		}
		else
			$arr = array('_language'=>$lang);

		if (Yii::$app->requestedRoute != Yii::$app->errorHandler->errorAction)
		{
			$arr[0] = '';
			return Url::to($arr);
		}
		else
		{
			if ( isset( $_SERVER['REQUEST_URI'], $_GET['_language'] ) )
			{
				$url = ltrim($_SERVER['REQUEST_URI'], '/'.$_GET['_language']);
				return '/' . $lang .'/'. $url;
			}
			else
				return Yii::$app->homeUrl;
		}
	}

}