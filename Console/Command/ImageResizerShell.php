<?php
App::uses('GearmanWorkerShell', 'Console/Command');

class ImageResizerShell extends GearmanWorkerShell {
	public $uses = array('ImageResizer');

	public function main() {
		/**
		 * Registers a worker with the name "image_resize" to the task ImageResizer
		 */
		$this->registerWorker('image_resize', $this->ImageResizer);

		/**
		 * Registers a worker with the name "dump_hex" to the method "execute()"
		 */
		$this->registerWorker('dump_hex', $this);

		/**
		 * Registers a worker with the name "upload" to the method "upload"
		 */
		$this->registerWorker('upload', array($this, 'upload'));

		$this->doWork();
	}

	public function execute(GearmanJob $job) {
		/**
		 * "dump_hex" worker
		 */
		$data = json_decode($job->workload());
		$hex = bin2hex($data->binary_data);
	}

	public function upload(GearmanJob $job) {
		/**
		 * "upload" worker
		 */
		$this->out('Received job: ' . $job->handle());

		$unique = $job->unique();
		$this->out('Job ID: ' . $unique);

		$workload = json_decode($job->workload());
		$job->sendData('Starting to perform upload');

		$data = $workload['file_body'];
		$fp = tmpfile();
		fwrite($fp, $data);
		fclose($fp);

		// sends information to the client that the job is 50 % completed
		$job->sendStatus(50, 100);

		// do some more stuff here
		$job->setReturn(GEARMAN_SUCCESS);
	}
}
