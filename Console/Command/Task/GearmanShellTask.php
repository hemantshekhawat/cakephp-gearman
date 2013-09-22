<?php
/**
 *  Gearman Worker Shell
 *  Used to start workers which will perform jobs. Workers can also be placed on other servers,
 *  written in another language than PHP. This Shell is just used to provide an easy way of setting up
 *  workers within CakePHP.
 */
App::uses('AppShell', 'Console/Command');
App::uses('CakeEvent', 'Event');
App::uses('CakeEventManager', 'Event');

class GearmanShellTask extends AppShell {

	public static $GearmanWorker = null;

	protected $_settings = array();

	protected $_workers = array();

	public function initialize() {
		parent::initialize();
		$this->_settings = Configure::read('Gearman') ?: array('servers' => array('127.0.0.1:4730'));
	}

	public function startup() {
		parent::startup();

		if (! self::$GearmanWorker) {
			self::$GearmanWorker = new GearmanWorker();
			self::$GearmanWorker->addServers(implode(',', $this->_settings['servers']));
			self::$GearmanWorker->addOptions(GEARMAN_WORKER_GRAB_UNIQ);
			self::$GearmanWorker->addOptions(GEARMAN_WORKER_NON_BLOCKING);

			$this->log('Creating instance of GearmanWorker', 'info', 'gearman');
		}
	}

/**
 * Registers a worker method
 * @throws	InvalidArgumentException	if callback is not valid
 * @param	string	$worker		The name of the function
 * @param	Object	$callback	The callback to be called. Can be instance of AppShell, or a valid callback
 */
	public function addMethod($worker, $callback) {
		if (!($callback instanceof Shell) && !is_callable($callback)) {
			throw new InvalidArgumentException('A callback of type Shell or Callable is required');
		}

		if ($callback instanceof Shell) {
			$callback = array($callback, 'execute');
		}

		$this->_workers[$worker] = $callback;
		self::$GearmanWorker->addFunction($worker, array($this, 'work'));

		$this->log('Adding method ' . $worker . ' to list of functions', 'info', 'gearman');
	}

	protected function _work(GearmanJob $job) {
		$json = json_decode($job->workload(), true);

		if (! json_last_error()) {
			$workload = $json;
		}

		$eventManager = new CakeEventManager();
		$eventManager->dispatch(new CakeEvent('Gearman.beforeWork', $this, $workload));

		call_user_func($this->_workers[$job->functionName()], $job, $workload);

		$eventManager->dispatch(new CakeEvent('Gearman.afterWork', $this, $workload));
	}

	public function execute() {
		while(self::$GearmanWorker->work());
	}
}
