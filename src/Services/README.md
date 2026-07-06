Services contain object definitions that handle the object build process and provide instance reuse, if necessary.

[`$smwgServicesFileDir`](https://www.semantic-mediawiki.org/wiki/Help:$smwgServicesFileDir) describes the location of the services directory.

## Services factory

Object instances are generally accessed using the `ServicesFactory` locator and its public methods.

`ServicesFactory` owns a private `Wikimedia\Services\ServiceContainer` populated from the `ServiceWiring.php` wiring file. That container holds the no-argument, stateless services (Bucket A). Services that take runtime arguments or are constructed fresh per use (Bucket B and C) are exposed as factory methods on `ServicesFactory` instead.

## Service files and containers

### Files

* `ServiceWiring.php` wiring file for the private `ServiceContainer`; defines the Bucket-A services
* `importer.php` provides services for the [Importer](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/master/src/Importer), consumed by `ImporterServiceFactory`
* `datavalues.php` provides services for `DataValue` objects, consumed by `DataValueServiceFactory`

### Containers

* `ServicesContainer` lightweight container used to inject services into the `DataValueServiceFactory` and `ImporterServiceFactory` domains

### Service specific factories

* `DataValueServiceFactory` provides service and factory functions for
  `DataValue` objects that are specified in `datavalues.php`
* `ImporterServiceFactory` provides service and factory functions for
  `Importer` objects that are specified in `importer.php`
