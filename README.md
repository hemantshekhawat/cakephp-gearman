CakePHP Gearman
===============

An easy way to setup Gearman clients and workers in CakePHP. Gearman is a worker server that makes you able to perform lots of heavy logic in the background, in other programs.

## Requirements
- gearmand
- pecl-gearman >= 0.5
- PHP >= 5.3
- CakePHP >= 2.2.0

## Installation
- `composer install`
- `git clone ...`
- HTTP download

Hint: `app/Plugin/Gearman`

## Examples

Load the plugin:
```php
CakePlugin::load('Gearman', array('bootstrap' => true));
```

Add the component to your controller:
```php
class MyController {
	public $components = array('Gearman.Gearman');

	public function reverseName() {
		$this->autoRender = false;
		echo 'Norway = ', $this->Gearman->newTask('reverse', 'Norway');
	}

	public function backgroundJob() {
		$handle = $this->Gearman->newBackgroundTask('long_running');
		$this->redirect(array (
			'action' => 'jobStatus',
			$handle
		));
	}

	public function jobStatus($handle) {
		$data = $this->Gearman->backgroundStatus ($handle);
		
		$this->autoRender = false;

		if (!$data[0]) {
			echo 'Gearman does not know about this job!';
			exit;
		}

		if ($data[1]) {
			echo 'The job is still running<br />', PHP_EOL;
		}

		echo 'Progress: ';
		if ($data[3] > 0) {
			printf ('%.2f %%', ($data[2] / $data[3]) * 100);
		} else {
			echo 'N/A';
		}

		echo '<br />', PHP_EOL;
		echo 'Refresh this page to see the progress increase';
	}
}
```

Your worker code (`app/Console/Command/<nameOfShell>`):
```php
class NameOfShell extends AppShell {
	public $tasks = array('Gearman.GearmanShell');

	public function startup() {
		parent::startup();
		$this->GearmanShell->addMethod('reverse', $this);
		$this->GearmanShell->addMethod('long_running', array($this, 'longRunning'));
	}

	/**
	 * To support running ./Console/cake ImageResize as an alternative
	 * to ./Console/cake ImageResize GearmanShell
	 */
	public function main() {
		$this->GearmanShell->execute();
	}

    /**
	 * $workload will be a JSON decoded array, if your client
	 * sends an array as workload. If not, it will be a string
	 */
	public function execute(GearmanJob $job, $workload) {
		return strrev($workload);
	}

	public function longRunning(GearmanJob $job, $workload) {
		$m = 60;
		for ($i = 1; $i <= $m; $i++) {
			echo "Sleeping, ", ($m - $i), " seconds left\n";
			// update progress
			$job->sendStatus($i, $m);
			sleep(1);
		}
	}
}
```

Then **start your worker**:
```sh
./Console/Cake NameOfShell
```

If you want to start your worker process in the background, consider using `nohup`:
```sh
nohup ./Console/Cake NameOfShell 2>&1 > /dev/null &
```

## Other
The worker can be written in whatever language supports Gearman. This means that your worker registers at the Gearman server, and your client requests the specific method.

## Problems
Please report them in the Issues page. 