<?php

namespace SMW\Tests\Integration\JSONScript;

use RuntimeException;
use SMW\DIWikiPage;
use SMW\MediaWiki\MediaWikiNsContentReader;
use SMW\Tests\Utils\UtilityFactory;

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
	 * @var PageReader
	 */
	private $pageReader;

	/**
	 * @var User
	 */
	private $superUser;

	/**
	 * @var SerializerFactory
	 */
	private $serializerFactory;

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
		$this->pageReader = UtilityFactory::getInstance()->newPageReader();
		$this->superUser = UtilityFactory::getInstance()->newMockSuperUser();
		$this->serializerFactory = \SMW\ApplicationFactory::getInstance()->newSerializerFactory();
	}

	/**
	 * @since 2.2
	 *
	 * @param boolean $debugMode
	 */
	public function setDebugMode( $debugMode ) {
		$this->debug = $debugMode;
	}

	/**
	 * @since 2.2
	 *
	 * @param array $case
	 */
	public function process( array $case ) {

		if ( !isset( $case['subject'] ) ) {
			return;
		}

		$this->assertSemanticDataForCase(
			$case
		);

		$this->assertTextFromParserOutputForCase(
			$case
		);

		$this->assertTextFromParsedMsgForCase(
			$case
		);
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

		$subject = $this->getSubjectFrom( $case, false );
		$semanticData = $this->store->getSemanticData( $subject );

		if ( $this->debug ) {
			print_r(
				$this->serializerFactory->newSemanticDataSerializer()->serialize( $semanticData )
			);
		}

		if ( isset( $case['errors'] ) && $case['errors'] !== [] ) {
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

	private function assertTextFromParserOutputForCase( $case ) {

		if ( !isset( $case['assert-output'] ) ) {
			return;
		}

		$title = $this->getSubjectFrom( $case )->getTitle();

		$parserOutput = $this->pageReader->getParserOutputFromEdit(
			$title
		);

		if ( isset( $case['assert-output']['onOutputPage'] ) && $case['assert-output']['onOutputPage'] ) {
			$context = new \RequestContext();
			$context->setTitle( $title );
			// Ensures the OutputPageBeforeHTML hook is run
			$context->getOutput()->addParserOutput( $parserOutput );
			$output = $context->getOutput()->getHtml();
		} elseif ( isset( $case['assert-output']['onPageView'] ) ) {
			$parameters = isset( $case['assert-output']['onPageView']['parameters'] ) ? $case['assert-output']['onPageView']['parameters'] : [];
			$context = \RequestContext::newExtraneousContext(
				$title,
				$parameters
			);

			// Avoid "... PermissionsError: The action you have requested is
			// limited to users in the group ..."
			$context->setUser( $this->superUser );

			\Article::newFromTitle( $title, $context )->view();
			$output = $context->getOutput()->getHtml();
		} else {
			$output = $parserOutput->getText();
		}

		// Strip HTML comments
		$output = preg_replace('/<!--(.*)-->/Uis', '', $output );

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

	private function assertTextFromParsedMsgForCase( $case ) {

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

	private function getSubjectFrom( $case, $checkExists = true ) {

		$subject = DIWikiPage::newFromText(
			$case['subject'],
			isset( $case['namespace'] ) ? constant( $case['namespace'] ) : NS_MAIN
		);

		$title = $subject->getTitle();

		if ( $title === null ) {
			throw new RuntimeException( 'Could not create Title object for subject page "' . $case['subject'] . '".' );
		}

		if ( $checkExists && !$title->exists() ) {
			throw new RuntimeException( 'Subject page "' . $case['subject'] . '" does not exist.' );
		}

		return $subject;
	}

}
