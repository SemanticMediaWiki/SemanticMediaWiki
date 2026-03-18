<?php

namespace SMW\Tests\DataItems;

use SMW\DataItems\Blob;

/**
 * @covers \SMW\DataItems\Blob
 * @covers \SMW\DataItems\DataItem
 *
 * @since 1.8
 *
 * @group SMW
 * @group SMWExtension
 * @group DataItems
 * @group Database
 *
 * @author Nischay Nahata
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class BlobTest extends AbstractDataItem {

	/**
	 * @see AbstractDataItem::getClass
	 *
	 * @since 1.8
	 *
	 * @return string
	 */
	public function getClass() {
		return Blob::class;
	}

	/**
	 * @see AbstractDataItem::constructorProvider
	 *
	 * @since 1.8
	 *
	 * @return array
	 */
	public function constructorProvider() {
		return [
			[ 'I love Semantic MediaWiki' ],
			[ 'It is open source' ],
		];
	}

}
