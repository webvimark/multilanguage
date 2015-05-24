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

Create table for translations (you can find table dumps in folder "data")

In you params.php

```php

return [
	...

	'mlConfig'=>[
		'default_language'=>'ru',
		'languages'=>[
			'ru'=>'Русский',
			'en'=>'English',
		],
	],

	...
];

```

In your model

( You also can change languages for this model (different from te ones in params) by defining them here )

```php

class Page extends ActiveRecord
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
					'db_table'         => 'translations_with_string',
					'attributes'       => ['name'],
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

In your Base Controller

```php

	public function init()
	{
		MultiLanguageHelper::catchLanguage();
		parent::init();
	}

```

In your config file

```php

'urlManager'   => [
		'class'=>MultiLanguageUrlManager::className(),
		'enablePrettyUrl' => true,
		'showScriptName'=>false,
		'rules'=>[

			'<_c:[\w \-]+>/<id:\d+>'=>'<_c>/view',
			'<_c:[\w \-]+>/<_a:[\w \-]+>/<id:\d+>'=>'<_c>/<_a>',
			'<_c:[\w \-]+>/<_a:[\w \-]+>'=>'<_c>/<_a>',

			'<_m:[\w \-]+>/<_c:[\w \-]+>/<_a:[\w \-]+>'=>'<_m>/<_c>/<_a>',
			'<_m:[\w \-]+>/<_c:[\w \-]+>/<_a:[\w \-]+>/<id:\d+>'=>'<_m>/<_c>/<_a>',

		],
	],

```


In your _form.php

```php

<?= $form->field($model, 'name')
		->textInput(['maxlength' => 255])
		->widget(MultiLanguageActiveField::className()) ?>


<?= $form->field($model, 'name')->textarea(['maxlength' => 255, 'rows'=>3])
		->widget(MultiLanguageActiveField::className(), ['inputType'=>'textArea', 'inputOptions'=>[
			'rows'=>3,
			'class'=>'form-control',
		]]) ?>

```
