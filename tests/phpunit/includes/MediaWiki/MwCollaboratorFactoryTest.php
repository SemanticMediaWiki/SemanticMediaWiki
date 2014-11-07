<?php

namespace SMW\Tests\MediaWiki;

use SMW\MediaWiki\MwCollaboratorFactory;
use SMW\ApplicationFactory;

/**
 * @covers \SMW\MediaWiki\MwCollaboratorFactory
 *
 * @group SMW
 * @group SMWExtension
 *
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class MwCollaboratorFactoryTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$applicationFactory = $this->getMockBuilder( '\SMW\ApplicationFactory' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\MediaWiki\MwCollaboratorFactory',
			new MwCollaboratorFactory( $applicationFactory )
		);
	}

	public function testCanConstructMessageBuilder() {

		$applicationFactory = $this->getMockBuilder( '\SMW\ApplicationFactory' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new MwCollaboratorFactory( $applicationFactory );

		$this->assertInstanceOf(
			'\SMW\MediaWiki\MessageBuilder',
			$instance->newMessageBuilder()
		);

		$language = $this->getMockBuilder( '\Language' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\MediaWiki\MessageBuilder',
			$instance->newMessageBuilder( $language )
		);
	}

	public function testCanConstructMagicWordFinder() {

		$applicationFactory = $this->getMockBuilder( '\SMW\ApplicationFactory' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new MwCollaboratorFactory( $applicationFactory );

		$this->assertInstanceOf(
			'\SMW\MediaWiki\MagicWordFinder',
			$instance->newMagicWordFinder()
		);
	}

	public function testCanConstructRedirectTargetFinder() {

		$applicationFactory = $this->getMockBuilder( '\SMW\ApplicationFactory' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new MwCollaboratorFactory( $applicationFactory );

		$this->assertInstanceOf(
			'\SMW\MediaWiki\RedirectTargetFinder',
			$instance->newRedirectTargetFinder()
		);
	}

	public function testCanConstructDeepRedirectTargetResolver() {

		$pageCreator = $this->getMockBuilder( '\SMW\MediaWiki\PageCreator' )
			->disableOriginalConstructor()
			->getMock();

		$applicationFactory = $this->getMockBuilder( '\SMW\ApplicationFactory' )
			->disableOriginalConstructor()
			->getMock();

		$applicationFactory->expects( $this->atLeastOnce() )
			->method( 'newPageCreator' )
			->will( $this->returnValue( $pageCreator ) );

		$instance = new MwCollaboratorFactory( $applicationFactory );

		$this->assertInstanceOf(
			'\SMW\MediaWiki\DeepRedirectTargetResolver',
			$instance->newDeepRedirectTargetResolver()
		);
	}

	public function testCanConstructHtmlFormBuilder() {

		$instance = new MwCollaboratorFactory( new ApplicationFactory() );

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\MediaWiki\HtmlFormBuilder',
			$instance->newHtmlFormBuilder( $title )
		);

		$language = $this->getMockBuilder( '\Language' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\MediaWiki\HtmlFormBuilder',
			$instance->newHtmlFormBuilder( $title, $language )
		);
	}

	public function testCanConstructHtmlTableBuilder() {

		$instance = new MwCollaboratorFactory( new ApplicationFactory() );

		$this->assertInstanceOf(
			'\SMW\MediaWiki\HtmlTableBuilder',
			$instance->newHtmlTableBuilder()
		);
	}

	public function testCanConstructHtmlColumnListFormatter() {

		$instance = new MwCollaboratorFactory( new ApplicationFactory() );

		$this->assertInstanceOf(
			'\SMW\MediaWiki\HtmlColumnListFormatter',
			$instance->newHtmlColumnListFormatter()
		);
	}

}
