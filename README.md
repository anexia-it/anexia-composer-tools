# Anexia Composer Tools

A composer package used to fetch version numbers and license information from all installed composer packages (included in composer.json) and
their latest version number registered on packagist. 

## Installation and configuration

Install the module via composer, therefore adapt the ``require`` part of your ``composer.json``:
```
"require": {
    "anexia/composer-tools": "1.1.0"
}
```

Now run
```
composer update [-o]
```
to add the packages source code to your ``/vendor`` directory.


## Usage

The package adds the ComposerPackagistTrait to your application. It can be added to a class (e.g. a controller) as any
trait and then its methods can be accessed directly.

The trait provides the two methods
* getLatestFrameworkVersion($package)
returns the latest registered packagist version of a given package
* getComposerPackageData()
returns an array of all installed composer packages (found via composer.json) including their currently installed
version and their latest registered packagist version

A VersionMonitoringController for a Laravel application might look like this example:

```
// inside VersionMonitoringController.php

use Anexia\ComposerTools\Traits\ComposerPackagistTrait

class VersionMonitoringController
{
    use ComposerPackagistTrait;
    
    public function index()
    {
        $runtime = [
            'platform' => 'php',
            'platform_version' => phpversion(),
            'framework' => 'laravel',
            'framework_installed_version' => $this->getCurrentFrameworkVersion(),
            'framework_newest_version' => $this->getLatestFrameworkVersion('laravel/framework')
        ];
        
        $modules = $this->getComposerPackageData();
        
        $response = response()->json([
            'runtime' => $runtime,
            'modules' => $modules
        ]);
    }
    
    /**
     * Get version number of the currently installed framework package
     *
     * @return string
     */    
    public function getCurrentFrameworkVersion()
    {
        // do something to return the currently used framework version,
        // depending on the framwork used within the application
    }
}
```

A possible response of the example controller action might look like this:
```
200 OK

{
   "runtime":{
      "platform":"php",
      "platform_version":"7.0.19",
      "framework":"laravel",
      "framework_installed_version":"5.4.28",
      "framework_newest_version":"5.4.28"
   },
   "modules":[
      {
         "name":"package-1",
         "installed_version":"3.1.10",
         "newest_version":"3.3.2"
      },
      {
         "name":"package-2",
         "installed_version":"1.4",
         "newest_version":"1.4"
      },
      ...
   ]
}
```