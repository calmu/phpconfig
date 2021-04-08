<?php

namespace Calconfig\Config\driver;

use Calconfig\Config\DriverInterface;

class Ini implements DriverInterface
{
	public function parse($config)
	{
		return is_file($config) ? parse_ini_file($config, true) : parse_ini_string($config, true);
	}
}