<?php

namespace SMW\Tests\Unit\Query\Processor;

use PHPUnit\Framework\TestCase;
use SMW\Query\Processor\DefaultParamDefinition;
use SMW\Query\QueryContext;

/**
 * @covers \SMW\Query\Processor\DefaultParamDefinition
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class DefaultParamDefinitionTest extends TestCase {

	public function testCanConstruct() {
		$this->assertInstanceOf(
			DefaultParamDefinition::class,
			new DefaultParamDefinition()
		);
	}

	public function testBuildParamDefinitions() {
		$vars = [
			'smwgResultFormats' => [],
			'smwgResultAliases' => [],
			'smwgQuerySources' => [],
			'smwgQDefaultLimit' => 42,
			'smwgQUpperbound' => 100
		];

		$this->assertIsArray(

			DefaultParamDefinition::buildParamDefinitions( $vars )
		);
	}

	public function testGetParamDefinitions() {
		$this->assertIsArray(

			DefaultParamDefinition::getParamDefinitions()
		);
	}

	public function testSearchlabelDefaultIsMarkerForInlineQuery() {
		$params = DefaultParamDefinition::getParamDefinitions( QueryContext::INLINE_QUERY );
		$this->assertStringContainsString( 'smw-localized-message', $params['searchlabel']->getDefault() );
	}

	public function testSearchlabelDefaultIsTextForSpecialPage() {
		$params = DefaultParamDefinition::getParamDefinitions( QueryContext::SPECIAL_PAGE );
		$this->assertStringNotContainsString( 'smw-localized-message', $params['searchlabel']->getDefault() );
	}

	public function testSearchlabelDefaultIsTextForDefaultContext() {
		$params = DefaultParamDefinition::getParamDefinitions();
		$this->assertStringNotContainsString( 'smw-localized-message', $params['searchlabel']->getDefault() );
	}

}
