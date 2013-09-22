<?php

class AllGearmanTest extends CakeTestSuite {

	public static function suite() {
		$suite = new CakeTestSuite('All Gearman Tests');
		$suite->addTestDirectory(__DIR__ . DS . 'Config');
		$suite->addTestDirectory(__DIR__ . DS . 'Console' . DS . 'Command' . DS . 'Task');
		$suite->addTestDirectory(__DIR__ . DS . 'Controller' . DS . 'Component');
		return $suite;
	}
}
