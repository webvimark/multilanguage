Mulitilanguage behavior for Yii 2
=====

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist webvimark/multilanguage "*"
```

or add

```
"webvimark/multilanguage": "*"
```

to the require section of your `composer.json` file.

Usage
-----

In your model

```php

class Page extends \webvimark\components\BaseActiveRecord
{
	use MultiLanguageTrait;

	/**
	* @inheritdoc
	*/
	public function behaviors()
	{
		return [
			...

			'mlBehavior'=>[
				'class'    => MultiLanguageBehavior::className(),
				'mlConfig' => [
					'db_table'         => 'translations',
					'attributes'       => ['name'],
					'default_language' => 'ru',
					'languages'        => [
						'ru' => 'Russian',
						'en' => 'English',
						'de' => 'Dutch'
					],
					'admin_routes'     => [
						'content/page/update',
						'content/page/index',
					],
				],
			],

			...
		];
	}


```

