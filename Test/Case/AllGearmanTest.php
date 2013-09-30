<?php

class AllGearmanTest extends CakeTestSuite {

	public $coverageSetup;

	public static function suite() {
		$suite = new self('All Gearman Tests');
		$suite->addTestDirectory(__DIR__ . DS . 'Config');
		$suite->addTestDirectory(__DIR__ . DS . 'Console' . DS . 'Command' . DS . 'Task');
		$suite->addTestDirectory(__DIR__ . DS . 'Controller' . DS . 'Component');
		return $suite;
	}

	public function run(PHPUnit_Framework_TestResult $result = null, $filter = false, array $groups = array(), array $excludeGroups = array(), $processIsolation = false) {
		if ($result === null) {
			$result = $this->createResult();
		}

		if (!$this->coverageSetup) {
			$coverage = $result->getCodeCoverage();
			if ($coverage) { // If the CodeCoverage is not installed or disabled
				$coverage->setProcessUncoveredFilesFromWhitelist(true);

				$coverageFilter = $coverage->filter();
				$coverageFilter->addDirectoryToBlacklist(APP . DS . 'Test');
				$coverageFilter->addDirectoryToBlacklist(CORE_PATH);
				$coverageFilter->addDirectoryToBlacklist(APP . DS . 'Plugin/Gearman/Test');
				$coverageFilter->addDirectoryToBlacklist(APP . DS . 'Plugin/Gearman/vendor');
			}

			$this->coverageSetup = true;
		}

		return parent::run($result, $filter, $groups, $excludeGroups, $processIsolation);
	}
}
