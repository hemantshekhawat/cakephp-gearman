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
class ImageResizeShell extends GearmanWorkerShell {
	public function main() {
		$this->registerWorker('image_resize', $this);
		$this->doWork();
	}

	public function execute(GearmanJob $job) {
		$data = json_decode($job->workload());

		$im = new Imagick();
		$im->addImage($data->src);
		// do something
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