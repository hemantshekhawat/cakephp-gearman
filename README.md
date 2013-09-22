CakePHP Gearman
===============

An easy way to setup Gearman clients and workers in CakePHP.

## Requirements
- pecl-gearman >= 1.0

## Installation
- `composer install`
- `git clone ...`
- HTTP download

Hint: `app/Plugin/Gearman`

## Usage

bootstrap.php:
```php
CakePlugin::load('Gearman', array('bootstrap' => true));
```

Your client code:
```php
class MyController {
	public $components = array('Gearman.Gearman');

	public function someMethod() {
		$this->Gearman->newTaskBackground('image_resize', array(
			'src' => $pathToImage, 
			'dst' => $pathToNewImage
		));
	}
}
```

Your worker code (`app/Console/Command/ImageResizeShell`):
```php
class ImageResizeShell extends AppShell {
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
		// do something useful with $workload
	}
}
```

Then **start your worker**:
```sh
./Console/Cake ImageResizeShell
```

If you want to start your worker process in the background, consider using `nohup`:
```sh
nohup ./Console/Cake ImageResizeShell2>&1 > /dev/null &
```
