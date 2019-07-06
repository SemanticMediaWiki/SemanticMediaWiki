Services contain object definitions that with the help of a [builder](https://github.com/onoi/callback-container) will handle the object build process and provides instance reuse, if necessary.

[`$smwgServicesFileDir`](https://www.semantic-mediawiki.org/wiki/Help:$smwgServicesFileDir) describes the location of the services directory.

## Services factory

Object instances are generally accessed using the `ServicesFactory` locator and its public methods.

## Service files and containers

### Files

* `importer.php` provides services for the [Importer](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/master/src/Importer)
* `mediawiki.php` isolates MediaWiki specific functions and services
* `events.php` isolates event services

### Containers

* `SharedServicesContainer.php` contains common and shared object definitions used throughout the Semantic MediaWiki code base and are accessible via `ServicesFactory`
* `ServicesContainer` temporary container to be used to inject services into a object instance

### Service specific factories

* `DataValueServiceFactory` provides service and factory functions for
  `DataValue` objects that are specified in `datavalues.php`
* `ImporterServiceFactory`

### Services registration

<pre>
$containerBuilder = new CallbackContainerFactory();
$containerBuilder = $callbackContainerFactory->newCallbackContainerBuilder();

$containerBuilder->registerCallbackContainer( new SharedServicesContainer() );
$containerBuilder->registerFromFile(
	$GLOBALS['smwgServicesFileDir'] . '/' . 'mediawiki.php'
);
</pre>
