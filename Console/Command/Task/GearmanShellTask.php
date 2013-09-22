<?php
/**
 *  Gearman Worker Shell
 *  Used to start workers which will perform jobs. Workers can also be placed on other servers,
 *  written in another language than PHP. This Shell is just used to provide an easy way of setting up
 *  workers within CakePHP.
 *
 *  [UserShell] -> GearmanShellTask::addMethod
 *  Gearman -> GearmanShellTask::execute -> GearmanShellTask::work -> [UserShell]->[callback]
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
		$this->_settings = Configure::read('Gearman') ?: array('servers' => array('127.0.0.1'));

		if (! self::$GearmanWorker) {
			$serversList = implode(',', $this->_settings['servers']);
			$this->log('Creating instance of GearmanWorker with servers: ' . $serversList, 'info', 'gearman');

			self::$GearmanWorker = new GearmanWorker();
			self::$GearmanWorker->addServers($serversList);
			self::$GearmanWorker->addOptions(GEARMAN_WORKER_GRAB_UNIQ);
			self::$GearmanWorker->addOptions(GEARMAN_WORKER_NON_BLOCKING);
		}
	}

/**
 * Registers a worker method
 * @throws	InvalidArgumentException	if callback is not valid
 * @param	string	$worker		The name of the function
 * @param	Object	$callback	The callback to be called. Can be instance of AppShell, or a valid callback
 */
	public function addMethod($worker, $callback) {
		if (is_object($callback)) {
			$callback = array($callback, 'execute');
		}

		if (!is_callable($callback)) {
			throw new InvalidArgumentException('A valid callback is required.');
		}

		$this->_workers[$worker] = $callback;
		self::$GearmanWorker->addFunction($worker, array($this, 'work'));

		$this->log('Adding method ' . $worker . ' to list of functions', 'info', 'gearman');
	}

	public function work(GearmanJob $job) {
		$workload = $job->workload();
		$json = json_decode($workload, true);

		if (! json_last_error()) {
			$workload = $json;
		}

		$eventManager = new CakeEventManager();
		$eventManager->dispatch(new CakeEvent('Gearman.beforeWork', $this, $workload));

		$data = call_user_func($this->_workers[$job->functionName()], $job, $workload);

		$eventManager->dispatch(new CakeEvent('Gearman.afterWork', $this, $workload));

		return $data;
	}

/**
 * Executes the jobs that have been added to the worker.
 * @throws RuntimeException if the worker cannot listen for jobs
 */
	public function execute() {
		$worker = self::$GearmanWorker;
		// @codingStandardsIgnoreStart
		while (@$worker->work() || $worker->returnCode() == GEARMAN_IO_WAIT ||
			$worker->returnCode() == GEARMAN_NO_JOBS) {
			// @codingStandardsIgnoreEnd

			if ($worker->returnCode() == GEARMAN_SUCCESS) {
				continue;
			}

			// waiting for next job
			// @codingStandardsIgnoreStart
			if (!@$worker->wait()) {
				// @codingStandardsIgnoreEnd
				if ($worker->returnCode() == GEARMAN_NO_ACTIVE_FDS) {
					// not connected to any servers
					sleep(5);
					continue;
				}

				break;
			}
		}

		throw new RuntimeException('Worker Error: ' . $worker->error());
	}
}
