## SMW::FileUpload::BeforeUpdate hook

SMW 2.4

```php
use SMW\DataItemFactory

$GLOBALS['wgHooks']['SMW::FileUpload::BeforeUpdate'][] = function ( $filePage, $semanticData ) {

	$dataItemFactory = new DataItemFactory();

	$property = $dataItemFactory->newDIProperty( '___ext_file_sha1' );

	$semanticData->addPropertyObjectValue(
		$property,
		$dataItemFactory->newDIBlob( $filePage->getFile()->getSha1() )
	);

	$property = $dataItemFactory->newDIProperty( '___ext_file_size' );

	$semanticData->addPropertyObjectValue(
		$property,
		$dataItemFactory->newDIBlob( $filePage->getFile()->getSize() )
	);

	return true;
};
```