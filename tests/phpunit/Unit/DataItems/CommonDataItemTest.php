<?php

namespace SMW\Tests\Unit\DataItems;

use PHPUnit\Framework\TestCase;
use SMW\DataItems\DataItem;
use SMW\DataItems\Uri;
use SMW\DataItems\WikiPage;

/**
 * @covers \SMW\DataItems\DataItem
 *
 * @group SMW
 * @group SMWExtension
 *
 * @license GPL-2.0-or-later
 * @since 2.1
 *
 * @author mwjames
 */
class CommonDataItemTest extends TestCase {

	public function testSerializationToFilterSameStringRepresentation() {
		$items = [];

		foreach ( [ 'Foo', 'Bar', 'Foo' ] as  $value ) {

			$dataItem = $this->getMockBuilder( DataItem::class )
				->disableOriginalConstructor()
				->getMockForAbstractClass();

			$dataItem->expects( $this->any() )
				->method( 'getSerialization' )
				->willReturn( $value );

			$items[] = $dataItem;
		}

		$this->assertCount(
			2,
			array_unique( $items )
		);
	}

	/**
	 * Cache blobs written before the data item classes moved into the
	 * SMW\DataItems namespace (#6453) encode the inherited private
	 * DataItem::$options under the old declaring-class mangle. Unserializing
	 * such a blob must not create a dynamic `options` property, which PHP 8.2
	 * reports as a deprecation (#6965).
	 *
	 * @dataProvider legacyCacheBlobProvider
	 */
	public function testUnserializeLegacyCacheBlobDoesNotCreateDynamicProperty( DataItem $dataItem, string $legacyClass ) {
		$legacyBlob = $this->rewriteToLegacyNamespace( serialize( $dataItem ), get_class( $dataItem ), $legacyClass );

		$deprecations = [];
		set_error_handler(
			static function ( $errno, $errstr ) use ( &$deprecations ) {
				if ( str_contains( $errstr, 'dynamic property' ) ) {
					$deprecations[] = $errstr;
				}
				return true;
			},
			E_ALL
		);

		try {
			$restored = unserialize( $legacyBlob );
		} finally {
			restore_error_handler();
		}

		$this->assertInstanceOf( get_class( $dataItem ), $restored );
		$this->assertSame( [], $deprecations );
	}

	public function legacyCacheBlobProvider() {
		return [
			'Uri written as SMWDIUri' => [ new Uri( 'http', 'example.org', '', '' ), 'SMWDIUri' ],
			'WikiPage written as SMW\DIWikiPage' => [ new WikiPage( 'Example', NS_MAIN ), 'SMW\DIWikiPage' ],
		];
	}

	/**
	 * Rewrites a current data item serialization into the byte form a pre-#6453
	 * blob would have: the outer class name and the inherited private
	 * DataItem::$options mangle both keyed to the old global SMWDataItem name.
	 */
	private function rewriteToLegacyNamespace( string $blob, string $currentClass, string $legacyClass ): string {
		$blob = str_replace(
			'O:' . strlen( $currentClass ) . ':"' . $currentClass . '"',
			'O:' . strlen( $legacyClass ) . ':"' . $legacyClass . '"',
			$blob
		);

		$current = "\0" . DataItem::class . "\0options";
		$legacy = "\0" . 'SMWDataItem' . "\0options";

		return str_replace(
			's:' . strlen( $current ) . ':"' . $current . '"',
			's:' . strlen( $legacy ) . ':"' . $legacy . '"',
			$blob
		);
	}

}
