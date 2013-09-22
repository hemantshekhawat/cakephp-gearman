CakePHP Gearman
===============

An easy way to setup gearman clients and workers.

# Requirements
- pecl-gearman >= 1.0

# Installation
- `composer install`
- `git clone ...`
- HTTP download

Hint: `app/Plugin/CakeGearman`

# Usage

Your client code:
```php
class MyController {
	public $uses = array('Gearman');

	public function someMethod() {
		$this->Gearman->newTaskBackground('image_resize', array('src' => $pathToImage, 'dst' => $pathToNewImage));
	}
}
```

Your worker code (`app/Console/Command/ImageResizeShell`):
```php
class ImageResizeShell extends AppShell {
	public $uses = array('Gearman');
	
	public function startup() {
		parent::startup();
		$this->Gearman->addMethod('image_resize', $this);
	}

	public function main() {
		$this->Gearman->execute();
	}

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