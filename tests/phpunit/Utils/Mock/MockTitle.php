<?php

namespace SMW\Tests\Utils\Mock;

/**
 *
 * @group SMW
 * @group SMWExtension
 *
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class MockTitle extends \PHPUnit_Framework_TestCase {

	public static function buildMock( $text = __METHOD__ ) {

		$instance = new self();

		$contentModel = defined( 'CONTENT_MODEL_WIKITEXT' ) ? CONTENT_MODEL_WIKITEXT : null;

		$title = $instance->getMockBuilder( 'Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $instance->any() )
			->method( 'getDBkey' )
			->will( $instance->returnValue( str_replace( ' ', '_', $text ) ) );

		$title->expects( $instance->any() )
			->method( 'getPrefixedDBkey' )
			->will( $instance->returnValue( str_replace( ' ', '_', $text ) ) );

		$title->expects( $instance->any() )
			->method( 'getContentModel' )
			->will( $instance->returnValue( $contentModel ) );

		return $title;
	}

	public static function buildMockForMainNamespace( $text = __METHOD__ ) {

		$instance = new self();

		$title = $instance->buildMock( $text );

		$title->expects( $instance->any() )
			->method( 'getNamespace' )
			->will( $instance->returnValue( NS_MAIN ) );

		$title->expects( $instance->any() )
			->method( 'getArticleID' )
			->will( $instance->returnValue( 9001 ) );

		$title->expects( $instance->any() )
			->method( 'isSpecialPage' )
			->will( $instance->returnValue( false ) );

		return $title;
	}

}
