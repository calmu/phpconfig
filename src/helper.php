<?php

use Calconfig\Config;

if( ! function_exists('config')) {
	/**
	 * 新版自动配置文件,有一定自动加载功能
	 * @param string $name
	 * @param null $value
	 * @param string $rang
	 * @return mixed
	 */
	function config($name = '', $value = NULL, $rang = '')
	{
		if(is_null($value) && is_string($name)) {
			return strpos($name, '?') === 0 ? Config::has(substr($name, 1), $rang) : Config::get($name, $rang);
		} else {
			return Config::set($name, $value, $rang);
		}
	}
}