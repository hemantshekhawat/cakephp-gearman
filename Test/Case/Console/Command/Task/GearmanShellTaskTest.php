<?php
App::uses('ConsoleOutput', 'Console');
App::uses('ConsoleInput', 'Console');
App::uses('GearmanShellTask', 'Gearman.Console/Command/Task');

class DummyClass {

	public function execute(GearmanJob $job) {
	}

	public static function upload(GearmanJob $job) {
	}

}

class GearmanShellTaskTest extends CakeTestCase {

	public $GearmanTask;

	public function setUp() {
		parent::setUp();

		$stdOut = $this->getMock('ConsoleOutput', array(), array(), '', false);
		$stdIn = $this->getMock('ConsoleInput', array(), array(), '', false);

		GearmanShellTask::$GearmanWorker = null;
		$this->GearmanTask = new GearmanShellTask($stdOut, $stdOut, $stdIn);
		$this->GearmanTask->initialize();
	}

	public function testGearmanWorker() {
		$this->assertNotNull(GearmanShellTask::$GearmanWorker);
		$this->assertInstanceOf('GearmanWorker', GearmanShellTask::$GearmanWorker);
	}

	public function testGearmanMethodInvalidCallback() {
		$this->setExpectedException('InvalidArgumentException');
		$this->GearmanTask->addMethod('image_resizer', false);
	}

	public function testGearmanMethod() {
		$dummyClass = new DummyClass;

		$this->GearmanTask->addMethod('image_resizer', $dummyClass);
		$workers = $this->_getProperty('_workers');

		$this->assertArrayHasKey('image_resizer', $workers);
		$this->assertEquals(array($dummyClass, 'execute'), $workers['image_resizer']);
	}

	public function testGearmanMethodOtherClass() {
		$this->GearmanTask->addMethod('file_uploader', array('DummyClass', 'upload'));
		$workers = $this->_getProperty('_workers');

		$this->assertArrayHasKey('file_uploader', $workers);
		$this->assertEquals(array('DummyClass', 'upload'), $workers['file_uploader']);
	}

	public function testWork() {
		$data = 'Hello, World';
		$function = 'reverse';

		$mock = $this->getMock('GearmanJob', array('workload', 'functionName'));
		$mock->expects($this->once())->method('workload')
			->will($this->returnValue($data));
		$mock->expects($this->once())->method('functionName')
			->will($this->returnValue($function));

		$this->GearmanTask->addMethod($function, array($this, 'workCallback'));

		$this->assertEquals(strrev($data), $this->GearmanTask->work($mock));
	}

	public function testWorkWithArray() {
		$data = array('name' => 'Andromeda');
		$function = 'greet';

		$mock = $this->getMock('GearmanJob', array('workload', 'functionName'));
		$mock->expects($this->once())->method('workload')
			->will($this->returnValue(json_encode($data)));
		$mock->expects($this->once())->method('functionName')
			->will($this->returnValue($function));

		$this->GearmanTask->addMethod($function, array($this, 'greetCallback'));

		$this->assertEquals("Hello, " . $data['name'], $this->GearmanTask->work($mock));
	}

	public function workCallback(GearmanJob $job, $workload)
	{
		return strrev($workload);
	}

	public function greetCallback(GearmanJob $job, $workload)
	{
		return "Hello, " . $workload['name'];
	}

	public function testExecute() {
		$mock = $this->getMock('GearmanWorker', array('work', 'returnCode', 'wait', 'error'));

		$mock->expects($this->any())
			->method('work')
			->will($this->onConsecutiveCalls(false, false, true, true, false));

		$mock->expects($this->any())
			->method('returnCode')
			->will($this->onConsecutiveCalls(
				GEARMAN_IO_WAIT, GEARMAN_NO_JOBS, GEARMAN_NO_ACTIVE_FDS, GEARMAN_SUCCESS
			));

		$mock->expects($this->any())->method('wait')->will($this->onConsecutiveCalls(false, true, false));

		$this->setExpectedException('RuntimeException', 'Worker Error: ');
		GearmanShellTask::$GearmanWorker = $mock;
		$this->GearmanTask->execute();
	}

	public function tearDown() {
		parent::tearDown();
		unset($this->GearmanTask);
	}

	protected function _getProperty($property) {
		$class = new ReflectionClass('GearmanShellTask');
		$property = $class->getProperty($property);
		$property->setAccessible(true);
		return $property->getValue($this->GearmanTask);
	}
}
