<?php

namespace Application;

use Zend\Db\TableGateway\TableGateway;
use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Select;
use Zend\Db\Adapter\Adapter;

class OpenSelect extends Select {
	public function processExpressionCallable($expression, $platform, $driver = null, $parameterContainer = null, $namedParameterPrefix = null)
	{
		return $this->processExpression($expression, $platform, $driver, $parameterContainer, $namedParameterPrefix);
	}
}