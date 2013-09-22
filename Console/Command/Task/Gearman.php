<?php
/**
 *  Gearman Worker Shell
 *  Used to start workers which will perform jobs. Workers can also be placed on other servers,
 *  written in another language than PHP. This Shell is just used to provide an easy way of setting up
 *  workers within CakePHP.
 */
App::uses('AppShell', 'Console/Command');

class GearmanTask extends AppShell {
	protected static $GearmanWorker;
	protected $_settings = array();
	protected $_workers = array();

	public function initialize() {
		parent::initialize();
		$this->_settings = Configure::read('Gearman');
	}

	public function startup() {
		parent::startup();

		if (! self::$GearmanWorker) {
			self::$GearmanWorker = new GearmanWorker();
			self::$GearmanWorker->addServer($this->_address, $this->_port);
			self::$GearmanWorker->addOptions(GEARMAN_WORKER_GRAB_UNIQ);
			self::$GearmanWorker->addOptions(GEARMAN_WORKER_NON_BLOCKING);
		}
	}

	public function addMethod($worker, $callback) {
		if (!($callback instanceof Shell) && !is_callable($callback)) {
			throw new InvalidArgumentException('A callback of type Shell or Callable is required');
		}

		if ($callback instanceof Shell) {
			$callback = array($callback, 'execute');
		}

		$this->_workers[$worker] = $callback;
		self::$GearmanWorker->addFunction($worker, array($this, 'work'));
	}

	protected function _work(GearmanJob $job) {
		$workload = json_decode($job->workload(), true);
		call_user_func($this->_workers[$job->functionName()], $job, $workload);
	}

	public function execute() {
		while(self::$GearmanWorker->work());
	}
}
