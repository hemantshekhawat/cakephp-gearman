<?php
App::uses('Component', 'Controller');
App::uses('Hash', 'Utility');

class GearmanComponent extends Component {

	public static $GearmanClient;

/**
 * Constructs the Gearman Client component. Settings are fetched from array, Configure
 * or defaults to localhost. Throws Exception if the Gearmand (the server) is not responding
 * @throws GearmanException	if the gearman client can't connect to the gearman server, an exception is thrown
 * @param	ComponentCollection	$collection
 * @param	array				$settings	Settings to pass to the gearman client
 */
	public function __construct(ComponentCollection $collection, $settings = array()) {
		parent::__construct($collection, $settings);

		if (! self::$GearmanClient) {
			self::$GearmanClient = new GearmanClient();

			if (empty($settings['servers'])) {
				$settings = Configure::read('Gearman');
			}

			if (! $settings) {
				$settings = array(
					'servers'	=> array('127.0.0.1')
				);
			}

			self::$GearmanClient->addServers(implode(',', $settings['servers']));
		}
	}

	protected function _formatWorkload($workload) {
		return is_array($workload) ? json_encode($workload) : $workload;
	}

/**
 * Handles the response of a Gearman work
 * @throws	Exception	if the job responds with an error
 * @param	string	response		 the response of the job
 * @return	mixed				the response of the job, or the job handle
 */
	protected function _handleResponse($response) {
		if (self::$GearmanClient->returnCode() == GEARMAN_WORK_FAIL) {
			throw new Exception('Gearman job did not execute successfully: ' . self::$GearmanClient->error());
		}

		return $response;
	}

/**
 * Performs a Gearman job with immediate return
 * @throws	Exception	if the job responds with an error
 * @param	string	$task		the taks name
 * @param	string|array	$workload	the workload to send to the job
 * @param	string	$taskId		a unique id for this task
 * @return	mixed				the response of the job
 */
	public function newTask($task, $workload = null, $taskId = null) {
		if (method_exists(self::$GearmanClient, 'doNormal')) {
			return $this->_handleResponse(self::$GearmanClient->doNormal(
				$task, $this->_formatWorkload($workload), $taskId));
		}

		return $this->_handleResponse(self::$GearmanClient->do(
			$task, $this->_formatWorkload($workload), $taskId));
	}

/**
 * Performs a Gearman job in the background
 * @throws	Exception	if the job responds with an error
 * @param	string	$task		the taks name
 * @param	string|array	$workload	the workload to send to the job
 * @param	string	$taskId		a unique id for this task
 * @return	mixed				the job handle for the submitted task
 */
	public function newBackgroundTask($task, $workload = null, $taskId = null) {
		return $this->_handleResponse(self::$GearmanClient->doBackground(
			$task, $this->_formatWorkload($workload), $taskId));
	}

/**
 * Gets the status of a background job
 * @param	string	$handle	the job handle, as returned by newBackgroundTask()
 * @return	array	An array containing status information for the job corresponding
 * to the supplied job handle. The first array element is a boolean indicating whether
 * the job is even known, the second is a boolean indicating whether the job is
 * still running, and the third and fourth elements correspond to the
 * numerator and denominator of the fractional completion percentage, respectively.
 */
	public function getBackgroundStatus($jobHandle) {
		return self::$GearmanClient->jobStatus($jobHandle);
	}

/**
 * Sends some arbitrary data for all job servers to see if they echo it back.
 * @return	boolean		Returns TRUE on success or FALSE on failure
 */
	public function pingServers() {
		$data = md5(uniqid());

		if (method_exists(self::$GearmanClient, 'ping')) {
			return self::$GearmanClient->ping($data);
		}

		return self::$GearmanClient->echo($data);
	}
}
