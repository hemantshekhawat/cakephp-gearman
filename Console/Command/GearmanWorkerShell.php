<?php
/**
 *  Gearman Worker Shell
 *  Used to start workers which will perform jobs. Workers can also be placed on other servers,
 *  written in another language than PHP. This Shell is just used to provide an easy way of setting up
 *  workers within CakePHP.
 */
App::uses('AppShell', 'Console/Command');

class GearmanWorkerShell extends AppShell {
	const SERVER_ADDRESS = '127.0.0.1';
	const SERVER_PORT = 4730;

	protected $GearmanWorker;
	protected $_address;
	protected $_port;

	public function initialize() {
		parent::initialize();

		$this->_address = self::SERVER_ADDRESS;
		$this->_port = self::SERVER_PORT;
	}

	public function startup() {
		parent::startup();

		$this->GearmanWorker = new GearmanWorker();
		$this->GearmanWorker->addServer($this->_address, $this->_port);
		$this->GearmanWorker->addOptions(GEARMAN_WORKER_GRAB_UNIQ);
	}

	public function doWork() {
		while($this->GearmanWorker->work());
	}

	public function registerWorker($worker, $callback) {
		if (!($callback instanceof Shell) && !is_callable($callback)) {
			throw new InvalidArgumentException('A callback of type Shell or Callable is required');
		}

		if ($callback instanceof Shell) {
			$callback = array($callback, 'execute');
		}

		$this->GearmanWorker->addFunction($worker, $callback);
	}
}
