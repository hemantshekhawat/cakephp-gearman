<?php
/**
 *  Gearman Worker Shell
 *  Used to start workers which will perform jobs. Workers can also be placed on other servers,
 *  written in another language than PHP. This Shell is just used to provide an easy way of setting up
 *  workers within CakePHP.
 */
App::uses('AppShell', 'Console/Command');

class GearmanWorkerShell extends AppShell {
	protected static $GearmanWorker;
	protected $_settings = array();

	public function initialize() {
		parent::initialize();

		$this->_settings = Configure::read('Gearman');
	}

	public function startup() {
		parent::startup();

		if (! self::$GearmanWorker) {
			self::$GearmanWorker = new GearmanWorker();
			self::$GearmanWorker->addServers(implode(',', $this->_settings['servers']));
			self::$GearmanWorker->addOptions(GEARMAN_WORKER_GRAB_UNIQ);
			self::$GearmanWorker->addOptions(GEARMAN_WORKER_NON_BLOCKING);
		}
	}

	public function doWork() {
		while(self::$GearmanWorker->work());
	}

	public function registerWorker($worker, $callback) {
		if (!($callback instanceof Shell) && !is_callable($callback)) {
			throw new InvalidArgumentException('A callback of type Shell or Callable is required');
		}

		if ($callback instanceof Shell) {
			$callback = array($callback, 'execute');
		}

		self::$GearmanWorker->addFunction($worker, $callback);
	}
}
