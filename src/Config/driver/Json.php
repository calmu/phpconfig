<?php

namespace Calconfig\Config\driver;

use Calconfig\Config\DriverInterface;

class Json implements DriverInterface
{
	public function parse($config)
	{
		if (is_file($config)) {
			$config = file_get_contents($config);
		}
		return json_decode($config, true);
	}
}