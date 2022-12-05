<?php

namespace SMW\Tests\MediaWiki\Template;

use SMW\MediaWiki\Template\TemplateSet;
use SMW\MediaWiki\Template\Template;

/**
 * @covers \SMW\MediaWiki\Template\TemplateSet
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since   3.1
 *
 * @author mwjames
 */
class TemplateSetTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			TemplateSet::class,
			 new TemplateSet()
		);
	}

	public function testAddTemplate() {

		$instance = new TemplateSet( [ 'foo' ] );
		$instance->addTemplate( new Template( 'Bar' ) );

		$this->assertSame(
			'foo{{Bar}}',
			$instance->text()
		);
	}

}
