<?php
namespace webvimark\behaviors\multilanguage;


class Singleton
{
	private static $_instance;

	public $dataArray = array();

	private function __construct() {}

	private function __clone() {}

	/**
	 * getInstance
	 *
	 * @return self
	 */
	public static function getInstance()
	{
		if (null === self::$_instance)
			self::$_instance = new self();

		return self::$_instance;
	}

	/**
	 * setData
	 *
	 * @param string $to
	 * @param mixed $data
	 */
	public static function setData($to, $data)
	{
		$instance = self::getInstance();

		$instance->dataArray[$to] = $data;
	}

	/**
	 * getData
	 *
	 * @param string $from
	 * @return mixed
	 */
	public static function getData($from)
	{
		$instance = self::getInstance();

		return isset($instance->dataArray[$from]) ? $instance->dataArray[$from] : null;
	}

} 