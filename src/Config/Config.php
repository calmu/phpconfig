<?php

namespace Calconfig\Config;

use Calfacade\Traits\Instance;

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
		// 二级配置支持
		$name = explode('.', $name, 2);
		return isset($this->_config[$range][strtolower($name[0])][$name[1]]);
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
		$name = explode('.', $name, 2);
		$name[0] = strtolower($name[0]);
		if( ! isset($this->_config[$range][$name[0]])) {
			// 尝试自动加载自动加载的配置(注:如果是真正的独立插件,不应该把业务场景代入)
			$file = '';
			if(isset($GLOBALS['yi_session']) && is_object($GLOBALS['yi_session'])) { // 数据后台配置才有的
				$module = $GLOBALS['yi_session']->get('pj_right_version');
				$file = APP_DIR . $module . DS . 'Conf' . DS . 'extra' . DS . $name[0] . '.php';
			} elseif(PHP_SAPI == 'cli') {// 盲猜是计划任务
				$file = CONF_PATH . 'extra' . DS . $name[0] . '.php';
			}
			is_file($file) && $this->load($file, $name[0]);
		}
		return $this->_config[$range][$name[0]][$name[1]] ?: NULL;
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