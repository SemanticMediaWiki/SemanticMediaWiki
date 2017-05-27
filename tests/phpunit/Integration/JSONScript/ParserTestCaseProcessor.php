<?php

namespace SMW\Tests\Integration\JSONScript;

use SMW\DIWikiPage;
use SMW\Tests\Utils\UtilityFactory;
use SMW\MediaWiki\MediaWikiNsContentReader;

/**
 * @group semantic-mediawiki
 * @group medium
 *
 * @license GNU GPL v2+
 * @since 2.3
 *
 * @author mwjames
 */
class ParserTestCaseProcessor extends \PHPUnit_Framework_TestCase {

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var SemanticDataValidator
	 */
	private $semanticDataValidator;

	/**
	 * @var IncomingSemanticDataValidator
	 */
	private $incomingSemanticDataValidator;

	/**
	 * @var StringValidator
	 */
	private $stringValidator;

	/**
	 * @var boolean
	 */
	private $debug = false;

	/**
	 * @param Store
	 * @param SemanticDataValidator
	 * @param IncomingSemanticDataValidator
	 * @param StringValidator
	 */
	public function __construct( $store, $semanticDataValidator, $incomingSemanticDataValidator, $stringValidator ) {
		$this->store = $store;
		$this->semanticDataValidator = $semanticDataValidator;
		$this->incomingSemanticDataValidator = $incomingSemanticDataValidator;
		$this->stringValidator = $stringValidator;
	}

	/**
	 * @since  2.2
	 */
	public function setDebugMode( $debugMode ) {
		$this->debug = $debugMode;
	}

	public function process( array $case ) {

		if ( !isset( $case['subject'] ) ) {
			return;
		}

		$this->assertSemanticDataForCase( $case );
		$this->assertParserOutputForCase( $case );
		$this->assertParserMsgForCase( $case );
	}

	private function assertSemanticDataForCase( $case ) {

		// Allows for data to be re-read from the DB instead of being fetched
		// from the store-id-cache
		if ( isset( $case['store']['clear-cache'] ) && $case['store']['clear-cache'] ) {
			$this->store->clear();
		}

		if ( !isset( $case['assert-store'] ) || !isset( $case['assert-store']['semantic-data'] ) ) {
			return;
		}

		$subject = DIWikiPage::newFromText(
			$case['subject'],
			isset( $case['namespace'] ) ? constant( $case['namespace'] ) : NS_MAIN
		);

		$semanticData = $this->store->getSemanticData( $subject );

		if ( $this->debug ) {
			print_r( $semanticData );
		}

		if ( isset( $case['errors'] ) && $case['errors'] !== array() ) {
			$this->assertNotEmpty(
				$semanticData->getErrors()
			);
		}

		$this->semanticDataValidator->assertThatPropertiesAreSet(
			$case['assert-store']['semantic-data'],
			$semanticData,
			$case['about']
		);

		if ( !isset( $case['assert-store']['semantic-data']['incoming'] ) ) {
			return;
		}

		$this->incomingSemanticDataValidator->assertThatIncomingDataAreSet(
			$case['assert-store']['semantic-data']['incoming'],
			$subject,
			$case['about']
		);
	}

	private function assertParserOutputForCase( $case ) {

		if ( !isset( $case['assert-output'] ) ) {
			return;
		}

		$subject = DIWikiPage::newFromText(
			$case['subject'],
			isset( $case['namespace'] ) ? constant( $case['namespace'] ) : NS_MAIN
		);

		$parserOutput = UtilityFactory::getInstance()->newPageReader()->getEditInfo( $subject->getTitle() )->output;

		if ( isset( $case['assert-output']['onOutputPage'] ) && $case['assert-output']['onOutputPage'] ) {
			$context = new \RequestContext();
			$context->setTitle( $subject->getTitle() );
			// Ensures the OutputPageBeforeHTML hook is run
			$context->getOutput()->addParserOutput( $parserOutput );
			$output = $context->getOutput()->getHtml();
		} elseif ( isset( $case['assert-output']['onPageView'] ) ) {
			$parameters = isset( $case['assert-output']['onPageView']['parameters'] ) ? $case['assert-output']['onPageView']['parameters'] : array();
			$context = \RequestContext::newExtraneousContext(
				$subject->getTitle(),
				$parameters
			);
			\Article::newFromTitle( $subject->getTitle(), $context )->view();
			$output = $context->getOutput()->getHtml();
		} else {
			$output = $parserOutput->getText();
		}

		if ( isset( $case['assert-output']['to-contain'] ) ) {
			$this->stringValidator->assertThatStringContains(
				$case['assert-output']['to-contain'],
				$output,
				$case['about']
			);
		}

		if ( isset( $case['assert-output']['not-contain'] ) ) {
			$this->stringValidator->assertThatStringNotContains(
				$case['assert-output']['not-contain'],
				$output,
				$case['about']
			);
		}
	}

	private function assertParserMsgForCase( $case ) {

		if ( !isset( $case['assert-msgoutput'] ) ) {
			return;
		}

		$mediaWikiNsContentReader = new MediaWikiNsContentReader();
		$mediaWikiNsContentReader->skipMessageCache();

		$text = $mediaWikiNsContentReader->read( $case['subject'] );
		$text = wfMessage( 'smw-parse', $text )->parse();

		if ( isset( $case['assert-msgoutput']['to-contain'] ) ) {
			$this->stringValidator->assertThatStringContains(
				$case['assert-msgoutput']['to-contain'],
				$text,
				$case['about']
			);
		}

		if ( isset( $case['assert-msgoutput']['not-contain'] ) ) {
			$this->stringValidator->assertThatStringNotContains(
				$case['assert-msgoutput']['not-contain'],
				$text,
				$case['about']
			);
		}
	}

}
