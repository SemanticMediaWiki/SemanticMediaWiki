* Since: 3.0
* Description: Hook allows to add additional RDF data for a selected subject (`smwAddToRDFExport` was deprecated with 3.0)
* Reference class: `SMWExportController`

### Signature

```php
use MediaWiki\MediaWikiServices;
use SMW\DIWikiPage;

MediaWikiServices::getInstance()->getHookContainer()->register( 'SMW::Exporter::Controller::AddExpData', function( DIWikiPage $subject, &$expDataList, $hasRecursionDepth, $withBacklinks ) {

	// $expData = new ExpData( ... );
	// $expDataList[] = $expData;

	return true;
} );
```
