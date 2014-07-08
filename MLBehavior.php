<?php
/**
 * Created by PhpStorm.
 * User: mark
 * Date: 7/5/14
 * Time: 1:15 PM
 */

namespace app\webvimark\behaviors\multilanguage;


use yii\base\Behavior;
use yii\base\Event;
use yii\db\ActiveRecord;
use Yii;
use yii\db\Query;
use yii\validators\Validator;

class MLBehavior extends Behavior
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

	/**
	 * If you want ta work with virtual attributes not only in admin routes
	 * user this method. It will initialize them and fill with corresponding translations
	 */
	public function loadModelTranslations()
	{

	}

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
				$this->owner->createValidators()
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

		foreach ($this->mlGetTranslations() as $translate)
		{
			if ( $this->owner->id == $translate['model_id'] )
			{
				$this->owner->{$translate['attribute']} = $translate['value'];

				if ( $this->_replaceOriginalAttributes )
				{
					$this->mlReplaceOriginalAttribute($translate);
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
				$this->mlSaveTranslatedAttribute($attribute, $event->sender->$attribute);
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
			->delete($this->mlGetTranslationTable(), [
				'table_name' => $this->owner->tableName(),
				'model_id'   => $this->owner->id,
			])
			->execute();
	}

	// ================= Helpers =================

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
		$translatedAttribute = $translate['attribute'];

		$tmp = explode('_', $translatedAttribute);
		$lang = end($tmp);

		if ( $lang == Yii::$app->language )
		{
			$originalAttribute = substr($translatedAttribute, 0, -(strlen($lang) + 1));

			$this->owner->$originalAttribute = $translate['value'];
		}
	}

	/**
	 * @return array
	 */
	private function mlGetTranslations()
	{
		$values = Singleton::getData('_ml_' . $this->owner->tableName());

		if ( !$values )
		{
			$values = (new Query())
				->select(['model_id', 'attribute', 'value'])
				->from($this->mlGetTranslationTable())
				->where([
					'table_name' => $this->owner->tableName(),
//					'model_id'   => $this->owner->id,
				])
				->all();

			Singleton::setData('_ml_' . $this->owner->tableName(), $values);
		}

		return $values;
	}

	/**
	 * @param string $name
	 * @param string $value
	 */
	private function mlSaveTranslatedAttribute($name, $value)
	{
		if ( $this->owner->isNewRecord )
		{
			$this->mlInsertTranslatedAttribute($name, $value);
		}
		else
		{
			$oldValue = $this->_mlQuery
				->select('value')
				->from($this->mlGetTranslationTable())
				->where([
					'table_name' => $this->owner->tableName(),
					'model_id'   => $this->owner->id,
					'attribute'  => $name,
				])
				->limit(1)
				->scalar();

			if ( $oldValue === false )
			{
				$this->mlInsertTranslatedAttribute($name, $value);
			}
			elseif ( $oldValue != $value )
			{
				$this->mlUpdateTranslatedAttribute($name, $value);
			}
		}

	}

	/**
	 * @param string $name
	 * @param string $value
	 */
	private function mlInsertTranslatedAttribute($name, $value)
	{
		$this->_mlQuery->createCommand()
			->insert($this->mlGetTranslationTable(), [
				'table_name' => $this->owner->tableName(),
				'attribute'  => $name,
				'model_id'   => $this->owner->id,
				'value'      => $value,
			])
			->execute();
	}

	/**
	 * @param string $name
	 * @param string $value
	 */
	private function mlUpdateTranslatedAttribute($name, $value)
	{
		$this->_mlQuery->createCommand()
			->update($this->mlGetTranslationTable(), ['value'=>$value], [
				'table_name' => $this->owner->tableName(),
				'attribute'  => $name,
				'model_id'   => $this->owner->id,
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

	/**
	 * @return string
	 */
	private function mlGetTranslationTable()
	{
		return $this->mlConfig['db_table'];
	}
} 