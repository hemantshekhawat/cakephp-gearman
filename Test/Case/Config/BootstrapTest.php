<?php

class BootstrapTest extends CakeTestCase {

	public function testBootstrapSettings() {
		$settings = Configure::read('Gearman');

		$this->assertNotNull($settings);
		$this->assertArrayHasKey('servers', $settings);
	}
}
