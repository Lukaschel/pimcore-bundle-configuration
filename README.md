# PimcoreConfiguration

With this bundle it is possible to deposit individual configurations for each installed Pimcore bundle.
Individual for each site and language.

## Installation

```json
"require" : {
    "lukaschel/pimcore-bundle-configuration" : "^2.0"
}
```
Enable and install the bundle over the pimcore extension manager or the cli tool.

Just put in your bundle main file (for example: TestBundle.php)
```php
public function getAdminIframePath()
{
    return '/admin/pimcoreconfiguration/bundle' . str_replace(__NAMESPACE__, '', __CLASS__);
}
```

If you like to ensure that this configuration Bundle is enabled as a dependency to your Bundle, then add this to your bundle Main file 
```php
    public static function registerDependentBundles(BundleCollection $collection): void
    {
        $collection->addBundle(new \Lukaschel\PimcoreConfigurationBundle\PimcoreConfigurationBundle);
    }
```

and add to your class definition
```php
implements \Pimcore\HttpKernel\Bundle\DependentBundleInterface
```

## Configuration
Go to the pimcore extension manager you should now get a "Configure" column.

Here you can config the "default" options for the bundle.
This config will be located under your bundle resources folder (TestBundle/Resources/config/bundle/bundle.yaml)

The Yaml files for the root site and the customs sites will be located 
under "/var/bundles/PimcoreConfigurationBundle/Bundles/TestBundle/name_language.yaml".

In this files only the values will be stored witch are different to the default configurations.

## Usage
You can get the configured options by the service:
```php
$service = $this->container->get('lukaschel.bundleconfiguration');
$service->getConfig($key);
```
Now you get the configuration for the current requested site, language and bundle;

When you want to get a specific config you can just put the parameters to the config call
```php
$service->getConfig($key, $siteRootId, $language, $bundleName);
```

In your template you can just use the twig extension by calling:
```twig
{{ bundleconfiguration('key') }}
```


## Changelog
For Changelog details please visit [CHANGELOG.md](CHANGELOG.md)

## Copyright and license
For licensing details please visit [LICENSE.md](LICENSE.md)
