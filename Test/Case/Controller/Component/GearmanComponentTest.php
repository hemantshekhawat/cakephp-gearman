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
			GearmanComponent::$GearmanClient = null;
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
		Configure::write('Gearman', array());
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

	public function testHandleResponse() {
		$method = new ReflectionMethod('GearmanComponent', '_handleResponse');
		$method->setAccessible(true);

		$mock = $this->getMock('GearmanClient', array('returnCode'));
		$mock->expects($this->once())->method('returnCode')->will($this->returnValue(GEARMAN_SUCCESS));

		GearmanComponent::$GearmanClient = $mock;

		$data = "Hello, World!";
		$this->assertEquals($data, $method->invoke($this->GearmanComponent, $data));
	}

	public function testHandleInvalidResponse() {
		$method = new ReflectionMethod('GearmanComponent', '_handleResponse');
		$method->setAccessible(true);

		$mock = $this->getMock('GearmanClient', array('returnCode'));
		$mock->expects($this->once())->method('returnCode')->will($this->returnValue(GEARMAN_WORK_FAIL));
		$mock->expects($this->once())->method('error')->will($this->returnValue(''));

		GearmanComponent::$GearmanClient = $mock;

		$this->setExpectedException('Exception', 'Gearman job did not execute successfully: ');
		$method->invoke($this->GearmanComponent, "Hello, World!");
	}

	public function testNewTask() {
		$mock = $this->getMock('GearmanClient', array('do', 'doNormal', 'returnCode'));
		$mock->expects($this->any())->method('returnCode')
			->will($this->returnValue(GEARMAN_SUCCESS));

		$taskName = 'reverse';
		$data = array('data');
		$return = "Hello, World!";

		$mock->expects($this->any())->method('do')
			->with($taskName, json_encode($data), null)
			->will($this->returnValue($return));
		$mock->expects($this->any())->method('doNormal')
			->with($taskName, json_encode($data), null)
			->will($this->returnValue($return));

		GearmanComponent::$GearmanClient = $mock;
		$this->assertEquals($return, $this->GearmanComponent->newTask($taskName, $data));
	}

	public function testNewBackgroundTask() {
		$mock = $this->getMock('GearmanClient', array('doBackground', 'returnCode'));
		$mock->expects($this->any())->method('returnCode')
			->will($this->returnValue(GEARMAN_SUCCESS));

		$taskName = 'reverse';
		$data = "foobar";

		$mock->expects($this->any())->method('doBackground')
			->with($taskName, $data, null)
			->will($this->returnValue(''));

		GearmanComponent::$GearmanClient = $mock;
		$this->GearmanComponent->newBackgroundTask($taskName, $data);
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
