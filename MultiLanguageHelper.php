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

		if ( isset($_GET['_language']) && in_array($_GET['_language'], $availableLanguages) )
		{
			Yii::$app->language = $_GET['_language'];

			if ( Yii::$app->session->get('_language') != $_GET['_language'] )
			{
				Yii::$app->session->set('_language', $_GET['_language']);
			}

			if ( Yii::$app->response->cookies->get('_language') != $_GET['_language'] )
			{
				$cookie = new Cookie([
					'name' => '_language',
					'value' => $_GET['_language'],
					'expire' => time() + (3600*24*30), // 30 days
				]);
	
				Yii::$app->response->cookies->add($cookie);	
			}
			
		}
		elseif (Yii::$app->session->has('_language'))
		{
			Yii::$app->language = Yii::$app->session->get('_language');
		}
		else
		{
			Yii::$app->language = Yii::$app->request->getPreferredLanguage($availableLanguages);
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
