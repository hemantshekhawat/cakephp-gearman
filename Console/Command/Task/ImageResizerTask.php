<?php
App::uses('AppShell', 'Console/Command');

class ImageResizerTask extends AppShell {
	public function execute(GearmanJob $job) {
		/**
		 * Performs image resizing on the object $job
		 */
	}
}
