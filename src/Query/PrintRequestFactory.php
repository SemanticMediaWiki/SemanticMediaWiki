<?php

namespace SMW\Query;

use SMW\DIProperty;
use SMW\DataValueFactory;
use SMWPropertyValue as PropertyValue;
use Title;

/**
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class PrintRequestFactory {

	/**
	 * @since 2.1
	 *
	 * @param DIProperty $property
	 *
	 * @return PrintRequest
	 */
	public function newPrintRequestByProperty( DIProperty $property ) {

		$propertyValue = DataValueFactory::getInstance()->newDataValueByType( PropertyValue::TYPE_ID );
		$propertyValue->setDataItem( $property );

		$instance = new PrintRequest(
			PrintRequest::PRINT_PROP,
			$propertyValue->getWikiValue(),
			$propertyValue
		);

		return $instance;
	}

	/**
	 * @see PrintRequest::newFromText
	 *
	 * @since 2.4
	 *
	 * @param string $text
	 * @param $showMode = false
	 *
	 * @return PrintRequest|null
	 */
	public function newPrintRequestFromText( $text, $showMode = false ) {
		return PrintRequest::newFromText( $text, $showMode );
	}

}
