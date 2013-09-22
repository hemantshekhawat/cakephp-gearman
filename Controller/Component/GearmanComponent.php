<?php
App::uses('Component', 'Controller');
App::uses('Hash', 'Utility');

class GearmanComponent extends Component {
	protected $GearmanClient;
	protected $_defaults = array(
		'server' => '127.0.0.1',
		'port' => 4730
	);

	public function __construct(ComponentCollection $collection, $settings = array()) {
		parent::__construct($collection, $settings);
		$settings = Hash::merge($settings, $this->_defaults);

		$this->GearmanClient = new GearmanClient();
		$this->GearmanClient->addServer($settings['server'], $settings['port']);
	}

	protected function formatWorkload($workload) {
		return is_array($workload) ? json_encode($workload) : $workload;
	}

	protected function handleResponse($response) {
		if ($this->GearmanClient->returnCode() == GEARMAN_FAIL) {
			throw new Exception('Gearman job did not execute successfully');
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
		return $this->handleResponse($this->GearmanClient->doNormal(
			$task, $this->formatWorkload($workload), $taskId));
	}

	/**
	 * Performs a Gearman job in the background
	 * @throws	Exception	if the job responds with an error
	 * @param	string	$task		the taks name
	 * @param	string|array	$workload	the workload to send to the job
	 * @param	string	$taskId		a unique id for this task
	 * @return	mixed				the job handle for the submitted task
	 */
	public function newBackgroundTask($task, $workload, $taskId) {
		return $this->handleResponse($this->GearmanClient->doBackground(
			$task, $this->formatWorkload($workload), $taskId));
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
	public function getBackgroundStatus($job_handle) {
		return $this->GearmanClient->jobStatus($job_handle);
	}

	/**
	 * Sends some arbitrary data for all job servers to see if they echo it back.
	 * @return	boolean		Returns TRUE on success or FALSE on failure
	 */
	public function pingServers() {
		return $this->GearmanClient->ping(md5(uniqid()));
	}
}
