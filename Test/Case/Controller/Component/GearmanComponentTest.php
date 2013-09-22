<?php

App::uses('ComponentCollection', 'Controller');
App::uses('Component', 'Controller');
App::uses('GearmanComponent', 'Gearman.Controller/Component');

class GearmanComponentTest extends CakeTestCase {

	public $GearmanComponent;

	public function setUp() {
		parent::setUp();

		$Collection = new ComponentCollection();

		try {
			$this->GearmanComponent = new GearmanComponent($Collection, array(
				'servers'	=> array(
					'127.0.0.1:4730'
				)
			));
		} catch (GearmanException $e) {
			$this->fail($e->getMessage());
		}
	}

	public function testGearmanClient() {
		$this->assertInstanceOf('GearmanClient', GearmanComponent::$GearmanClient);
	}

	public function testPingServers() {
		$this->assertTrue($this->GearmanComponent->pingServers());
	}

	public function testGetBackgroundStatusBogus() {
		$data = $this->GearmanComponent->getBackgroundStatus(uniqid());
		$this->assertFalse($data[0]);
	}

	public function tearDown() {
		parent::tearDown();
		unset($this->GearmanComponent);
	}
}
