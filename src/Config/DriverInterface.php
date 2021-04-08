<?php

namespace Calconfig\Config;

interface DriverInterface
{
	/**
	 * 解析文件或者内容
	 * @param $config 配置文件 OR 配置内容
	 * @return array|mixed|null
	 */
	public function parse($config){}
}