<?php

namespace Calconfig\Config;

use Calfacade\Traits\Instance;
use Calphelper\Arr;

/**
 * config object 
 * 
 */
class Config
{
	use Instance;
	private $_config = [];// 配置项
	private $_range = '_sys_'; //配置作用域
	/**
	 * 设定配置参数的作用域
	 * @param $range
	 */
	public function range($range)
	{
		$this->_range = $range;
		isset($this->_config[$this->_range]) || $this->_config[$this->_range] = [];
		//dump(static::$instance, $this);
	}
	/**
	 * 解析文件或者内容
	 * @param $config 配置文件 OR 配置内容
	 * @param string $type 解析的配置类型
	 * @param string $name 配置名[不为空代表二级配置]
	 * @param string $range
	 * @return array|mixed|null
	 */
	public function parse($config, $type = '', $name = '', $range = '')
	{
		$range = $range ?: $this->_range;

		empty($type) && $type = pathinfo($config, PATHINFO_EXTENSION);
		$class = strpos($type, '\\') ? $type : '\\Calconfig\\Config\\driver\\' . ucwords($type);

		return $this->set((new $class())->parse($config), $name, $range);
	}
	/**
	 * 加载配置文件(php|yaml格式)
	 * @param string $file 配置文件名
	 * @param string $name 配置名[不为空代表为二级配置]
	 * @param string $range
	 * @return array|mixed|null
	 */
	public function load($file, $name = '', $range = '')
	{
		$range = $range ?: $this->_range;

		if( ! isset($this->_config[$range])) $this->_config[$range] = [];
		if(is_file($file)) {
			$name = strtolower($name);
			$type = pathinfo($file, PATHINFO_EXTENSION);

			if('php' == $type) {
				return $this->set(include $file, $name, $range);
			}

			if('yaml' == $type && function_exists('yaml_parse_file')) {
				return $this->set(yaml_parse_file($file), $name, $range);
			}

			return $this->parse($file, $type, $name, $range);
		}
		return $this->_config[$range];
	}
	/**
	 * 判断配置是否存在
	 * @param string $name 配置项名称[支持二级配置]
	 * @param string $range
	 * @return bool
	 */
	public function has($name, $range = '')
	{
		$range = $range ?: $this->_range;

		if(strpos($name, '.') === FALSE) {
			return isset($this->_config[$range][$name]);
		}
		// 无限级配置支持
		return Arr::has($this->_config[$range], $name);
	}
	/**
	 * 获得配置项
	 * @param null|string $name 配置项名称[支持二级配置]
	 * @param string $range
	 * @return mixed|null
	 */
	public function get($name = NULL, $range = '')
	{
		$range = $range ?: $this->_range;
		// 为空返回所有配置
		if(empty($name) && isset($this->_config[$range])) {
			return $this->_config[$range];
		}
		// 非二级直接返回
		if(strpos($name, '.') === FALSE) {
			$name = strtolower($name);
			return $this->_config[$range][$name] ?: NULL;
		}
		// 二级配置支持
		$nameArr = explode('.', $name, 2);
		$nameArr[0] = strtolower($nameArr[0]);
		if( ! isset($this->_config[$range][$nameArr[0]])) {
			// 尝试自动加载自动加载的配置(注:如果没有先行导入配置，这里默认依赖calmu/phpenv 插件的env()函数配置[不强制依赖，需要自己引入])
			if ($this->get('config_path')) {
				$file = $this->get('config_path');
			} else if (function_exists('env')) {
				$file = env('CONFIG_PATH');
			}
			$file .= "extra/{$nameArr[0]}.php";
			is_file($file) && $this->load($file, $nameArr[0]);
		}
		// 无限级配置支持
		return Arr::get($this->_config[$range], $name);
	}
	/**
	 * 设置配置项
	 * @param string|array $name 配置参数名 [支持 . 分割的二级配置]
	 * @param null $value
	 * @param string $range 作用域
	 * @return array|mixed|null
	 */
	public function set($name, $value = NULL, $range = '')
	{
		$range = $range ?: $this->_range;
		isset($this->_config[$range]) || $this->_config[$range] = [];

		//字符串单个配置
		if(is_string($name)) {
			if(strpos($name, '.') !== FALSE) {
				$name = explode('.', $name, 2);
				$this->_config[$range][strtolower($name[0])][strtolower($name[1])] = $value;
			} else {
				$this->_config[$range][strtolower($name)] = $value;
			}
			return $value;
		}
		// 数组批量设置
		if(is_array($name)) {
			if( ! empty($value)) { // 反向调用...
				$this->_config[$range][$value] = isset($this->_config[$range][$value]) ? array_merge($this->_config[$range], $name) : $name;
				return $this->_config[$range][$value];
			}
			return $this->_config[$range] = array_merge($this->_config[$range], array_change_key_case($name));
		}
		//为空则返回全部配置
		return $this->_config[$range];
	}
	/**
	 * 重置配置文件
	 * @param string $range
	 */
	public function reset($range = '')
	{
		$range = $range ?: $this->_range;

		if(TRUE == $range) {
			$this->_config = [];
		} else {
			$this->_config[$range] = [];
		}
	}
}