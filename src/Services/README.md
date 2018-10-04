Services contain object definitions that with the help of a [ContainerBuilder](https://github.com/onoi/callback-container)
will manage the object build process and provides instance reuse if necessary. Object instances are normally accessed using
dedicated factory methods.

## Service files and specification

* `DataValueServiceFactory` provides service and factory functions for
  `DataValue` objects that are specified in `DataValueServices.php`
* `ImporterServices.php` provides services for the [Importer](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/master/src/Importer)
* `MediaWikiServices.php` isolates MediaWiki specific functions and services
* `SharedServicesContainer.php` contains common and shared object definitions used
  throughout the Semantic MediaWiki code base and are accessible via `ApplicationFactory`

## ContainerBuilder

<pre>
$containerBuilder = new CallbackContainerFactory();
$containerBuilder = $callbackContainerFactory->newCallbackContainerBuilder();

$containerBuilder->registerCallbackContainer( new SharedServicesContainer() );
$containerBuilder->registerFromFile(
	$GLOBALS['smwgServicesFileDir'] . '/' . 'MediaWikiServices.php'
);
</pre>

[`$smwgServicesFileDir`](https://www.semantic-mediawiki.org/wiki/Help:$smwgServicesFileDir) describes the location of the 
service directory.
