<?php

namespace SMW\Tests\MediaWiki;

use SMW\MediaWiki\PageUpdater;
use SMW\Tests\Utils\Mock\MockTitle;

/**
 * @covers \SMW\MediaWiki\PageUpdater
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since   2.1
 *
 * @author mwjames
 */
class PageUpdaterTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\MediaWiki\PageUpdater',
			 new PageUpdater()
		);
	}

	public function testCanUpdate() {

		$instance = new PageUpdater();

		$this->assertInternalType(
			'boolean',
			 $instance->canUpdate()
		);
	}

	public function testPurgeParserCache() {

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->once() )
			->method( 'getPrefixedDBKey' )
			->will( $this->returnValue( 'Foo' ) );

		$title->expects( $this->once() )
			->method( 'invalidateCache' );

		$instance = new PageUpdater();
		$instance->addPage( $title );

		$instance->doPurgeParserCache();
	}

	public function testPurgeHtmlCache() {

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->once() )
			->method( 'getPrefixedDBKey' )
			->will( $this->returnValue( 'Foo' ) );

		$title->expects( $this->once() )
			->method( 'touchLinks' );

		$instance = new PageUpdater();
		$instance->addPage( $title );

		$instance->doPurgeHtmlCache();
	}

	public function testPurgeWebCache() {

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->once() )
			->method( 'getPrefixedDBKey' )
			->will( $this->returnValue( 'Foo' ) );

		$title->expects( $this->once() )
			->method( 'purgeSquid' );

		$instance = new PageUpdater();
		$instance->addPage( $title );

		$instance->doPurgeWebCache();
	}

	public function testFilterDuplicatePages() {

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->exactly( 2 ) )
			->method( 'getPrefixedDBKey' )
			->will( $this->returnValue( 'Foo' ) );

		$title->expects( $this->once() )
			->method( 'invalidateCache' );

		$instance = new PageUpdater();
		$instance->addPage( $title );
		$instance->addPage( $title );

		$instance->doPurgeParserCache();
	}

}
