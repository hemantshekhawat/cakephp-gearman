CakePHP Gearman
===============

An easy way to setup Gearman clients and workers in CakePHP. Gearman is a worker server that makes you able to perform lots of heavy logic in the background, in other programs.

## Requirements
- pecl-gearman >= 1.0

## Installation
- `composer install`
- `git clone ...`
- HTTP download

Hint: `app/Plugin/Gearman`

## Usage

Load the plugin:
```php
CakePlugin::load('Gearman', array('bootstrap' => true));
```

Add the component to your controller:
```php
class MyController {
	public $components = array('Gearman.Gearman');

	public function someMethod() {
		// use Gearman->newTask() to get the response right away
		$this->Gearman->newTaskBackground('image_resize', array(
			'src' => $pathToImage, 
			'dst' => $pathToNewImage
		));
	}
}
```

Your worker code (`app/Console/Command/<nameOfShell>`):
```php
class NameOfShell extends AppShell {
	public $tasks = array('Gearman.GearmanShell');

	public function startup() {
		parent::startup();
		$this->Gearman->addMethod('image_resize', $this);
	}

	/**
	 * To support running ./Console/cake ImageResize as an alternative
	 * to ./Console/cake ImageResize GearmanShell
	 */
	public function main() {
		$this->Gearman->execute();
	}

    /**
	 * $workload will be a JSON decoded array, if your client
	 * sends an array as workload. If not, it will be a string
	 */
	public function execute(GearmanJob $job, $workload) {
		// Do something useful with $workload.
		// If using regular tasks (not background), return
		// the data here
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