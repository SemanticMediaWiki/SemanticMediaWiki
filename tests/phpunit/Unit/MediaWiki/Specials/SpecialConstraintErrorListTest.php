<?php

namespace SMW\Tests\Unit\MediaWiki\Specials;

use MediaWiki\MediaWikiServices;
use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\Specials\SpecialConstraintErrorList;
use SMW\Settings;
use SMW\Tests\Utils\UtilityFactory;

/**
 * @covers \SMW\MediaWiki\Specials\SpecialConstraintErrorList
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class SpecialConstraintErrorListTest extends TestCase {

	private $settings;
	private $stringValidator;

	protected function setUp(): void {
		parent::setUp();

		$this->settings = $this->createMock( Settings::class );
		$this->stringValidator = UtilityFactory::getInstance()->newValidatorFactory()->newStringValidator();
	}

	public function testCanExecute() {
		$this->settings->expects( $this->once() )
			->method( 'dotGet' )
			->with( 'smwgPagingLimit.errorlist' )
			->willReturn( 20 );

		$instance = new SpecialConstraintErrorList( $this->settings );

		$instance->getContext()->setTitle(
			MediaWikiServices::getInstance()->getTitleFactory()->newFromText( 'SpecialConstraintErrorList' )
		);

		$this->assertTrue(
			$instance->execute( '' )
		);
	}

	public function testFindRedirectURL() {
		$instance = new SpecialConstraintErrorList( $this->settings );

		$instance->getContext()->setTitle(
			MediaWikiServices::getInstance()->getTitleFactory()->newFromText( 'SpecialConstraintErrorList' )
		);

		$expected = [
			'%5D%5D+%5B%5BProcessing+error+type%3A%3Aconstraint%5D%5D',
			'&po=%3FHas+improper+value+for%7C%3FHas+processing+error+text',
			'&p=class%3Dsortable-20smwtable-2Dstriped-20smwtable-2Dclean%2Fsep%3Dul',
			'&eq=no&limit=5',
			'&bTitle=constrainterrorlist',
			'&bHelp=smw-constrainterrorlist-helplink',
			'&bMsg=smw-constrainterrorlist-intro'
		];

		$this->stringValidator->assertThatStringContains(
			$expected,
			$instance->findRedirectURL( 5 )
		);
	}

}
