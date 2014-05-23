<?php

namespace SMW\Tests\Util\Mock;

/**
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 *
 * @license GNU GPL v2+
 * @since 1.9.3
 *
 * @author mwjames
 */
class MockTitle {

	public static function getMock( \PHPUnit_Framework_TestCase $testCase, $text = __METHOD__ ) {

		$contentModel = class_exists( 'ContentHandler' ) ? CONTENT_MODEL_WIKITEXT : null;

		$title = $testCase->getMockBuilder( 'Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $testCase->any() )
			->method( 'getDBkey' )
			->will( $testCase->returnValue( str_replace( ' ', '_', $text ) ) );

		$title->expects( $testCase->any() )
			->method( 'getContentModel' )
			->will( $testCase->returnValue( $contentModel ) );

		return $title;
	}

	public static function getMockForMainNamespace( \PHPUnit_Framework_TestCase $testCase, $text = __METHOD__ ) {

		$title = self::getMock( $testCase, $text );

		$title->expects( $testCase->any() )
			->method( 'getNamespace' )
			->will( $testCase->returnValue( NS_MAIN ) );

		$title->expects( $testCase->any() )
			->method( 'getArticleID' )
			->will( $testCase->returnValue( 9001 ) );

		$title->expects( $testCase->any() )
			->method( 'isSpecialPage' )
			->will( $testCase->returnValue( false ) );

		return $title;
	}

}
