<?php

namespace SMW\Tests\MediaWiki\Hooks;

use SMW\MediaWiki\Hooks\ArticleFromTitle;

/**
 * @covers \SMW\MediaWiki\Hooks\ArticleFromTitle
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class ArticleFromTitleTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$wikiPage = $this->getMockBuilder( '\WikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\MediaWiki\Hooks\ArticleFromTitle',
			new ArticleFromTitle( $title, $wikiPage )
		);
	}

	/**
	 * @dataProvider titleProvider
	 */
	public function testProcess( $namespace, $expected ) {

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->atLeastOnce() )
			->method( 'getNamespace' )
			->will( $this->returnValue( $namespace ) );

		$wikiPage = $this->getMockBuilder( '\WikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new ArticleFromTitle( $title, $wikiPage );
		$instance->process();

		$this->assertInstanceOf(
			$expected,
			$wikiPage
		);
	}

	public function titleProvider() {

		$provider = array(
			array( SMW_NS_PROPERTY, 'SMWPropertyPage' ),
			array( SMW_NS_CONCEPT, 'SMW\ConceptPage' ),
		);

		return $provider;
	}

}
