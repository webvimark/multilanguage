<?php
namespace webvimark\behaviors\multilanguage;

trait MultiLanguageTrait
{
	/**
	 * @inheritdoc
	 */
	public function __set($name, $value)
	{
		if ( $this->mlIsAttributeTranslatable($name) )
			$this->$name = $value;
		else
			parent::__set($name, $value);
	}

	/**
	 * @inheritdoc
	 */
	public function attributes()
	{
		$attributes = parent::attributes();

		return array_merge($attributes, $this->mlGetAttributes());
	}

}