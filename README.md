# FlexPress plugin boilerplate framework

Boilerplate framework for creating plugins.

## Install
Download using composer
```
composer create-project flexpress/plugin-framework <pluginname>
```
Once it has finished installing, run the installer script and answer the questions it asks:
```
./install.sh
```
Let the installer run and it should be all setup.

If you are not using composer to include this you will want to dump the autoloader again:
```
composer dump-autoload
```
and then you will also want to include the autoloader in the <pluginname>.php file:
```
require_once 'vendor/autoload.php';
```