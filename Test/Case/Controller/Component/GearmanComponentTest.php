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

	public function testGearmanDefaultConfig() {
		$Gearman = new GearmanComponent(new ComponentCollection());
		$this->assertEquals(array('servers' => array('127.0.0.1')), $Gearman->settings);
	}

	public function testGearmanUserConfig() {
		$config = array('servers' => '255.255.255.255');
		Configure::write('Gearman', $config);

		$Gearman = new GearmanComponent(new ComponentCollection());
		$this->assertEquals($config, $Gearman->settings);
	}

	public function testFormatWorkload() {
		$method = new ReflectionMethod('GearmanComponent', '_formatWorkload');
		$method->setAccessible(true);

		$data = "Hello, World!";
		$this->assertEquals($data, $method->invoke($this->GearmanComponent, $data));
		
		$data = array('name' => 'Zevs');
		$this->assertEquals(json_encode($data), $method->invoke($this->GearmanComponent, $data));
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
