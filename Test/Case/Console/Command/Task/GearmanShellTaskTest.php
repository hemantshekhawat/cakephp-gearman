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
