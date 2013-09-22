<?php

App::uses('ComponentCollection', 'Controller');
App::uses('Component', 'Controller');
App::uses('GearmanComponent', 'Gearman.Controller/Component');

class GearmanComponentTest extends CakeTestCase {

	public $GearmanComponent;

	public function setUp() {
		parent::setUp();

		$Collection = new ComponentCollection();
		$this->GearmanComponent = new GearmanComponent($Collection);
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
