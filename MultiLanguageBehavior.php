<?php

namespace webvimark\behaviors\multilanguage;


use webvimark\helpers\Singleton;
use yii\base\Behavior;
use yii\base\Event;
use yii\db\ActiveRecord;
use Yii;
use yii\db\Query;
use yii\validators\Validator;

class MultiLanguageBehavior extends Behavior
{
	public $mlConfig;

	/**
	 * @var array
	 */
	private $_mlAttributes = [];

	/**
	 * @var Query
	 */
	private $_mlQuery;

	/**
	 * @var bool
	 */
	private $_replaceOriginalAttributes = true;


	public function events()
	{
		return [
			ActiveRecord::EVENT_AFTER_FIND => 'mlAfterFind',
			ActiveRecord::EVENT_AFTER_INSERT => 'mlAfterSave',
			ActiveRecord::EVENT_AFTER_UPDATE => 'mlAfterSave',
			ActiveRecord::EVENT_AFTER_DELETE => 'mlAfterDelete',
		];
	}

	/**
	 * Get languages and default language from params if they are not set explicitly
	 */
	public function init()
	{
		parent::init();

		$this->mlConfig['default_language'] = Yii::$app->params['mlConfig']['default_language'];

		if ( !isset($this->mlConfig['languages']) )
		{
			$this->mlConfig['languages'] = Yii::$app->params['mlConfig']['languages'];
		}
	}

	/**
	 * If you want to work with virtual attributes not only in admin routes
	 * use this method. It will initialize them and fill with corresponding translations
	 */
	public function loadTranslations()
	{
		foreach ($this->mlGetModelTranslations() as $translate)
		{
			$attribute = $translate['attribute'] . '_' . $translate['lang'];

			$this->owner->$attribute = $translate['value'];
		}
	}

	/**
	 * Add safe validators for virtual attributes if there are not rules for them
	 *
	 * @param \yii\base\Component $owner
	 */
	public function attach($owner)
	{
		parent::attach($owner);

		foreach ($this->mlGetAttributes() as $attribute)
		{
			$validators = $this->owner->getActiveValidators($attribute);

			if ( empty($validators) )
			{
				$this->owner->getValidators()
					->append(Validator::createValidator('string', $this->owner, $attribute));
			}
		}

	}

	/**
	 * Initialize virtual attributes and fill them with translated values
	 *
	 * If requested route is not an admin route and active language != default language
	 * then original value will be replaced with active language value
	 *
	 * @param Event $event
	 */
	public function mlAfterFind($event)
	{
		$this->mlInitializeAttributes();

		if ( Yii::$app->language == $this->mlConfig['default_language'] OR in_array(Yii::$app->requestedRoute, $this->mlConfig['admin_routes']) )
		{
			$this->_replaceOriginalAttributes = false;
		}

		// Remove unnecessary "/" from admin routes
		array_walk($this->mlConfig['admin_routes'], function(&$val, $key) {
			$val = trim($val, '/');
		});

		if ( in_array(Yii::$app->requestedRoute, $this->mlConfig['admin_routes']) )
		{
			$translations = $this->mlGetTranslations();
		}
		else
		{
			$translations = $this->mlGetLanguageSpecificTranslations();
		}

		foreach ($translations as $translate)
		{
			if ( $this->owner->id == $translate['model_id'] )
			{
				if ( $this->_replaceOriginalAttributes )
				{
					$this->mlReplaceOriginalAttribute($translate);
				}
				elseif ( isset($this->mlConfig['languages'][$translate['lang']]) )
				{
					$attribute = $translate['attribute'] . '_' . $translate['lang'];

					$this->owner->$attribute = $translate['value'];
				}
			}
		}

	}

	/**
	 * Handle insert and update events
	 *
	 * @param Event $event
	 */
	public function mlAfterSave($event)
	{
		$this->_mlQuery = new Query;

		foreach ($this->mlGetAttributes() as $attribute)
		{
			if ( isset($event->sender->$attribute) AND $event->sender->$attribute !== null)
			{
				$tmp = explode('_', $attribute);

				$language = array_pop($tmp);

				$originalAttribute = implode('_', $tmp);

				if ( $event->name == 'afterUpdate' )
				{
					$this->mlSaveTranslatedAttribute($originalAttribute, $event->sender->$attribute, $language);
				}
				// If afterInsert - insert only non empty values
				elseif ( $event->sender->$attribute !== '' )
				{
					$this->mlInsertTranslatedAttribute($originalAttribute, $event->sender->$attribute, $language);
				}
			}
		}
	}

	/**
	 * Delete related records from translations table
	 *
	 * @param Event $event
	 */
	public function mlAfterDelete($event)
	{
		(new Query())->createCommand()
			->delete($this->mlConfig['db_table'], [
				'table_name' => $this->owner->getTableSchema()->name,
				'model_id'   => $this->owner->id,
			])
			->execute();
	}

	// ================================== Helpers ==================================

	/**
	 * Initialize virtual attributes like name_en, name_de and fill them with null's
	 */
	private function mlInitializeAttributes()
	{
		foreach ($this->mlGetAttributes() as $attribute)
		{
			$this->owner->$attribute = null;
		}

	}

	/**
	 * @param array $translate
	 */
	private function mlReplaceOriginalAttribute($translate)
	{
		if ( $translate['lang'] == Yii::$app->language )
		{
			$this->owner->{$translate['attribute']} = $translate['value'];
		}
	}

	/**
	 * @return array
	 */
	private function mlGetTranslations()
	{
		$values = Singleton::getData('_ml_' . $this->owner->getTableSchema()->name);

		if ( !$values )
		{
			$values = (new Query())
				->select(['model_id', 'attribute', 'value', 'lang'])
				->from($this->mlConfig['db_table'])
				->where([
					'table_name' => $this->owner->getTableSchema()->name,
				])
				->all();

			Singleton::setData('_ml_' . $this->owner->getTableSchema()->name, $values);
		}

		return $values;
	}

	/**
	 * @return array
	 */
	private function mlGetModelTranslations()
	{
		$values = Singleton::getData('_ml_' . $this->owner->getTableSchema()->name);

		if ( !$values )
		{
			$values = (new Query())
				->select(['attribute', 'value', 'lang'])
				->from($this->mlConfig['db_table'])
				->where([
					'table_name' => $this->owner->getTableSchema()->name,
					'model_id'   => $this->owner->id,
				])
				->all();

			Singleton::setData('_ml_' . $this->owner->getTableSchema()->name, $values);
		}

		return $values;
	}

	/**
	 * @param null|string $language
	 *
	 * @return array
	 */
	private function mlGetLanguageSpecificTranslations($language = null)
	{
		if ( !$language )
			$language = Yii::$app->language;

		$values = Singleton::getData('_ml_' . $this->owner->getTableSchema()->name . '_' . $language);

		if ( !$values )
		{
			$values = (new Query())
				->select(['model_id', 'attribute', 'value', 'lang'])
				->from($this->mlConfig['db_table'])
				->where([
					'table_name' => $this->owner->getTableSchema()->name,
					'lang'       => $language,
				])
				->all();

			Singleton::setData('_ml_' . $this->owner->getTableSchema()->name . '_' . $language, $values);
		}

		return $values;
	}

	/**
	 * @param string $name
	 * @param string $value
	 * @param string $language
	 */
	private function mlSaveTranslatedAttribute($name, $value, $language)
	{
		$oldValue = $this->_mlQuery
			->select('value')
			->from($this->mlConfig['db_table'])
			->where([
				'table_name' => $this->owner->getTableSchema()->name,
				'model_id'   => $this->owner->id,
				'attribute'  => $name,
				'lang'       => $language,
			])
			->limit(1)
			->scalar();

		if ( $oldValue === false AND $value !== '' )
		{
			$this->mlInsertTranslatedAttribute($name, $value, $language);
		}
		elseif ( $oldValue != $value )
		{
			$this->mlUpdateTranslatedAttribute($name, $value, $language);
		}
	}

	/**
	 * @param string $name
	 * @param string $value
	 * @param        $language
	 */
	private function mlInsertTranslatedAttribute($name, $value, $language)
	{
		$this->_mlQuery->createCommand()
			->insert($this->mlConfig['db_table'], [
				'table_name' => $this->owner->getTableSchema()->name,
				'attribute'  => $name,
				'model_id'   => $this->owner->id,
				'lang'       => $language,
				'value'      => $value,
			])
			->execute();
	}

	/**
	 * @param string $name
	 * @param string $value
	 * @param        $language
	 */
	private function mlUpdateTranslatedAttribute($name, $value, $language)
	{
		$this->_mlQuery->createCommand()
			->update($this->mlConfig['db_table'], ['value'=>$value], [
				'table_name' => $this->owner->getTableSchema()->name,
				'attribute'  => $name,
				'model_id'   => $this->owner->id,
				'lang'       => $language,
			])
			->execute();
	}

	/**
	 * @param string $name
	 *
	 * @return bool
	 */
	public function mlIsAttributeTranslatable($name)
	{
		return in_array($name, $this->mlGetAttributes());
	}

	/**
	 * @return array
	 */
	public function mlGetAttributes()
	{
		if ( empty($this->_mlAttributes) )
		{
			$mlAttributes = [];

			$languages = $this->mlConfig['languages'];
			unset($languages[$this->mlConfig['default_language']]);

			foreach ($languages as $languageCode => $languageName)
			{
				foreach ($this->mlConfig['attributes'] as $attribute)
				{
					$mlAttributes[] = $attribute . '_' . $languageCode;
				}
			}

			$this->_mlAttributes = $mlAttributes;
		}

		return $this->_mlAttributes;
	}

} 