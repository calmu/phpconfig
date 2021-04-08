<?php

namespace Calconfig\Config\driver;

use Calconfig\Config\DriverInterface;

class Xml implements DriverInterface
{
	public function parse($config)
	{
		$content = is_file($config) ? simplexml_load_file($config) : simplexml_load_string($config);
		$result = (array) $content;
		foreach ($result as $key => $val) {
			if (is_object($val)) {
				$result[$key] = (array) $val;
			}
		}
		return $result;
	}
}