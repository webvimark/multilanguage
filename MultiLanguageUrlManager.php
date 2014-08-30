<?php

namespace webvimark\behaviors\multilanguage;

use yii\web\UrlManager;
use Yii;

class MultiLanguageUrlManager extends UrlManager
{
	/**
	 * init
	 *
	 * Adding language prefixes to url rules
	 */
	public function init()
	{
		$languages = array_keys(Yii::$app->params['mlConfig']['languages']);

		if (count($languages) > 0)
		{
			$langPrefix = '<_language:('.implode('|', $languages).')>/';

			$finalRurles[$langPrefix] = '/';

			foreach ($this->rules as $rule => $path)
			{
				$finalRurles[$langPrefix . ltrim($rule, '/')] = $path;
			}

			$this->rules = array_merge($finalRurles, $this->rules);
		}

		parent::init();
	}

	/**
	 * createUrl
	 * Adding language parameter to links
	 *
	 * @param array $params
	 *
	 * @return string
	 */
	public function createUrl($params)
	{
		if (!isset($params['_language']))
		{
			if (Yii::$app->session->has('_language'))
			{
				Yii::$app->language = Yii::$app->session->get('_language');
			}
			elseif (Yii::$app->request->cookies->has('_language'))
			{
				Yii::$app->language = Yii::$app->request->cookies->get('_language');
			}

			$params['_language'] = Yii::$app->language;
		}

		return parent::createUrl($params);
	}

} 