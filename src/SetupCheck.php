<?php

namespace SMW;

use SMW\Utils\TemplateEngine;
use SMW\Utils\Logo;
use SMW\Localizer\LocalMessageProvider;
use SMW\Exception\FileNotReadableException;
use SMW\Exception\JSONFileParseException;
use RuntimeException;

/**
 * @private
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class SetupCheck {

	/**
	 * Semantic MediaWiki was loaded or accessed but not correctly enabled.
	 */
	const ERROR_EXTENSION_LOAD = 'ERROR_EXTENSION_LOAD';

	/**
	 * Semantic MediaWiki was loaded or accessed but not correctly enabled.
	 */
	const ERROR_EXTENSION_INVALID_ACCESS = 'ERROR_EXTENSION_INVALID_ACCESS';

	/**
	 * A user tried to use `wfLoadExtension( 'SemanticMediaWiki' )` and
	 * `enableSemantics` at the same causing the ExtensionRegistry to throw an
	 * "Uncaught Exception: It was attempted to load SemanticMediaWiki twice ..."
	 */
	const ERROR_EXTENSION_REGISTRY = 'ERROR_EXTENSION_REGISTRY';

	/**
	 * A dependency (extension, MediaWiki) causes an error
	 */
	const ERROR_EXTENSION_DEPENDENCY = 'ERROR_EXTENSION_DEPENDENCY';

	/**
	 * Multiple dependencies (extension, MediaWiki) caused an error
	 */
	const ERROR_EXTENSION_DEPENDENCY_MULTIPLE = 'ERROR_EXTENSION_DEPENDENCY_MULTIPLE';

	/**
	 * Extension doesn't match MediaWiki or the PHP requirement.
	 */
	const ERROR_EXTENSION_INCOMPATIBLE = 'ERROR_EXTENSION_INCOMPATIBLE';

	/**
	 * Extension doesn't match the DB requirement for Semantic MediaWiki.
	 */
	const ERROR_DB_REQUIREMENT_INCOMPATIBLE = 'ERROR_DB_REQUIREMENT_INCOMPATIBLE';

	/**
	 * The upgrade key has change causing the schema to be invalid
	 */
	const ERROR_SCHEMA_INVALID_KEY = 'ERROR_SCHEMA_INVALID_KEY';

	/**
	 * A selected default profile could not be loaded or is unknown.
	 */
	const ERROR_CONFIG_PROFILE_UNKNOWN = 'ERROR_CONFIG_PROFILE_UNKNOWN';

	/**
	 * The system is currently in a maintenance window
	 */
	const MAINTENANCE_MODE = 'MAINTENANCE_MODE';

	/**
	 * @var []
	 */
	private $options = [];

	/**
	 * @var SetupFile
	 */
	private $setupFile;

	/**
	 * @var TemplateEngine
	 */
	private $templateEngine;

	/**
	 * @var LocalMessageProvider
	 */
	private $localMessageProvider;

	/**
	 * @var []
	 */
	private $definitions = [];

	/**
	 * @var string
	 */
	private $languageCode = 'en';

	/**
	 * @var string
	 */
	private $fallbackLanguageCode = 'en';

	/**
	 * @var boolean
	 */
	private $sentHeader = true;

	/**
	 * @var string
	 */
	private $errorType = '';

	/**
	 * @var string
	 */
	private $errorMessage = '';

	/**
	 * @var string
	 */
	private $traceString = '';

	/**
	 * @since 3.1
	 *
	 * @param array $vars
	 * @param SetupFile|null $setupFile
	 */
	public function __construct( array $options, SetupFile $setupFile = null ) {
		$this->options = $options;
		$this->setupFile = $setupFile;
		$this->templateEngine = new TemplateEngine();
		$this->localMessageProvider = new LocalMessageProvider( '/local/setupcheck.i18n.json' );

		if ( $this->setupFile === null ) {
			$this->setupFile = new SetupFile();
		}
	}

	/**
	 * @since 3.2
	 *
	 * @param string $file
	 *
	 * @return array
	 * @throws RuntimeException
	 */
	public static function readFromFile( string $file ) : array {

		if ( !is_readable( $file ) ) {
			throw new FileNotReadableException( $file );
		}

		$contents = json_decode(
			file_get_contents( $file ),
			true
		);

		if ( json_last_error() === JSON_ERROR_NONE ) {
			return $contents;
		}

		throw new JSONFileParseException( $file );
	}

	/**
	 * @since 3.1
	 *
	 * @param SetupFile|null $setupFile
	 *
	 * @return SetupCheck
	 */
	public static function newFromDefaults( SetupFile $setupFile = null ) {

		if ( !defined( 'SMW_VERSION' ) ) {
			$version = self::readFromFile( $GLOBALS['smwgIP'] . 'extension.json' )['version'];
		} else {
			$version = SMW_VERSION;
		}

		$setupCheck = new SetupCheck(
			[
				'SMW_VERSION'    => $version,
				'MW_VERSION'     => $GLOBALS['wgVersion'], // MW_VERSION may not yet be defined!!
				'wgLanguageCode' => $GLOBALS['wgLanguageCode'],
				'smwgUpgradeKey' => $GLOBALS['smwgUpgradeKey']
			],
			$setupFile
		);

		return $setupCheck;
	}

	/**
	 * @since 3.2
	 */
	public function disableHeader() {
		$this->sentHeader = false;
	}

	/**
	 * @since 3.1
	 *
	 * @return boolean
	 */
	public function isCli() {
		return PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg';
	}

	/**
	 * @since 3.1
	 *
	 * @param string $traceString
	 */
	public function setTraceString( $traceString ) {
		$this->traceString = $traceString;
	}

	/**
	 * @since 3.2
	 *
	 * @param string $errorMessage
	 */
	public function setErrorMessage( string $errorMessage ) {
		$this->errorMessage = $errorMessage;
	}

	/**
	 * @since 3.2
	 *
	 * @param string $errorType
	 */
	public function setErrorType( string $errorType ) {
		$this->errorType = $errorType;
	}

	/**
	 * @since 3.2
	 *
	 * @return boolean
	 */
	public function isError( string $error ) : bool {
		return $this->errorType === $error;
	}

	/**
	 * @since 3.1
	 *
	 * @return boolean
	 */
	public function hasError() {

		$this->errorType = '';

		// When it is not a test run or run from the command line we expect that
		// the extension is registered using `enableSemantics`
		if ( !defined( 'SMW_EXTENSION_LOADED' ) && !$this->isCli() ) {
			$this->errorType = self::ERROR_EXTENSION_LOAD;
		} elseif ( $this->setupFile->inMaintenanceMode() ) {
			$this->errorType = self::MAINTENANCE_MODE;
		} elseif ( !$this->isCli() && !$this->setupFile->hasDatabaseMinRequirement() ) {
			$this->errorType = self::ERROR_DB_REQUIREMENT_INCOMPATIBLE;
		} elseif ( $this->setupFile->isGoodSchema() === false ) {
			$this->errorType = self::ERROR_SCHEMA_INVALID_KEY;
		}

		return $this->errorType !== '';
	}

	/**
	 * @note Adding a new error type requires to:
	 *
	 * - Define a constant to clearly identify the type of error
	 * - Extend the `setupcheck.json` to add a definition for the new type and
	 *   specify which information should be displayed
	 * - In case the existing HTML elements aren't sufficient, create a new
	 *   zxy.ms file and define the HTML code
	 *
	 * The `TemplateEngine` will replace arguments defined in the HTML hereby
	 * absolving this class from any direct HTML manipulation.
	 *
	 * @since 3.1
	 *
	 * @param boolean $isCli
	 *
	 * @return string
	 */
	public function getError( $isCli = false ) {

		$error = [
			'title' => '',
			'content' => ''
		];

		$this->languageCode = $_GET['uselang'] ?? $this->options['wgLanguageCode'] ?? 'en';

		// Output forms for different error types are registered with a JSON file.
		$this->definitions = $this->readFromFile(
			$GLOBALS['smwgDir'] . '/data/template/setupcheck/setupcheck.json'
		);

		// Error messages are specified in a special i18n JSON file to avoid relying
		// on the MW message system especially when SMW isn't fully registered
		// and we are unable to access any `smw-...` message keys from the standard
		// i18n files.
		$this->localMessageProvider->setLanguageCode(
			$this->languageCode
		);

		$this->localMessageProvider->loadMessages();

		// HTML specific formatting is contained in the following files where
		// a defined group of targets correspond to types used in the JSON
		$this->templateEngine->bulkLoad(
			[
				'/setupcheck/setupcheck.ms' => 'setupcheck-html',
				'/setupcheck/setupcheck.progress.ms' => 'setupcheck-progress',

				// Target specific elements
				'/setupcheck/setupcheck.section.ms'   => 'section',
				'/setupcheck/setupcheck.version.ms'   => 'version',
				'/setupcheck/setupcheck.paragraph.ms' => 'paragraph',
				'/setupcheck/setupcheck.errorbox.ms'  => 'errorbox',
				'/setupcheck/setupcheck.db.requirement.ms' => 'db-requirement',
			]
		);

		if ( !isset( $this->definitions['error_types'][$this->errorType] ) ) {
			throw new RuntimeException( "The `{$this->errorType}` type is not defined in the `setupcheck.json`!" );
		}

		$error = $this->createErrorContent( $this->errorType );

		if ( $isCli === false ) {
			$content = $this->buildHTML( $error );
			$this->header( 'Content-Type: text/html; charset=UTF-8' );
			$this->header( 'Content-Length: ' . strlen( $content ) );
			$this->header( 'Cache-control: none' );
			$this->header( 'Pragma: no-cache' );
		} else {
			$content = $error['title'] . "\n\n" . $error['content'];
			$content = str_replace(
				[ '<!-- ROW -->', '</h3>', '</h4>', '</p>', '&nbsp;' ],
				[ "\n", "\n\n", "\n\n", "\n\n", ' ' ],
				$content
			);
			$content = "\n" . wordwrap( strip_tags( trim( $content ) ), 73 );
		}

		return $content;
	}

	/**
	 * @since 3.1
	 *
	 * @param boolean $isCli
	 */
	public function showErrorAndAbort( $isCli = false ) {

		echo $this->getError( $isCli );

		if ( ob_get_level() ) {
			ob_flush();
			flush();
			ob_end_clean();
		}

		die();
	}

	private function header( $text ) {
		if ( $this->sentHeader ) {
			header( $text );
		}
	}

	private function createErrorContent( $type ) {

		$indicator_title = 'Error';
		$template = $this->definitions['error_types'][$type];
		$content = '';

		/**
		 * Actual output form
		 */
		foreach ( $template['output_form'] as $value ) {
			$content .= $this->createContent( $value, $type );
		}

		/**
		 * Special handling for the progress output
		 */
		if ( isset( $template['progress'] ) ) {
			foreach ( $template['progress'] as $value ) {
				$text = $this->createCopy( $value['text'] );

				if ( isset( $value['progress_keys'] ) ) {
					$content .= $this->createProgressIndicator( $value );
				}

				$args = [
					'text' => $text,
					'template' => $value['type']
				];

				$this->templateEngine->compile(
					$value['type'],
					$args
				);

				$content .= $this->templateEngine->publish( $value['type'] );
			}
		}

		/**
		 * Special handling for the stack trace output
		 */
		if ( isset( $template['stack_trace'] ) && $this->traceString !== '' ) {
			foreach ( $template['stack_trace'] as $value ) {
				$content .= $this->createContent( $value, $type );
			}
		}

		if ( isset( $template['indicator_title'] ) ) {
			$indicator_title = $this->createCopy( $template['indicator_title'] );
		}

		$error = [
			'title' => 'Semantic MediaWiki',
			'indicator_title' => $indicator_title,
			'content' => $content,
			'borderColor' => $template['indicator_color']
		];

		return $error;
	}

	private function createContent( $value, $type ) {

		if ( $value['text'] === 'ERROR_TEXT' ) {
			$text = str_replace( "\n", '<br>', $this->errorMessage );
		} elseif ( $value['text'] === 'ERROR_TEXT_MULTIPLE' ) {
			$errors = explode( "\n", $this->errorMessage );
			$text = '<ul><li>' . implode( '</li><li>', array_filter( $errors ) ) . '</li></ul>';
		} elseif ( $value['text'] === 'TRACE_STRING' ) {
			$text = $this->traceString;
		} else {
			$text = $this->createCopy( $value['text'] );
		}

		$args = [
			'text' => $text,
			'template' => $value['type']
		];

		if ( $value['type'] === 'version' ) {
			$args['version-title'] = $text;
			$args['smw-title'] = 'Semantic MediaWiki';
			$args['smw-version'] = $this->options['SMW_VERSION'] ?? 'n/a';
			$args['smw-upgradekey'] = $this->options['smwgUpgradeKey'] ?? 'n/a';
			$args['mw-title'] = 'MediaWiki';
			$args['mw-version'] = $this->options['MW_VERSION'] ?? 'n/a';
			$args['code-title'] = $this->createCopy( 'smw-setupcheck-code' );
			$args['code-type'] = $type;
		}

		if ( $value['type'] === 'db-requirement' ) {
			$requirements = $this->setupFile->get( SetupFile::DB_REQUIREMENTS );
			$args['version-title'] = $text;
			$args['db-title'] = $this->createCopy( 'smw-setupcheck-db-title' );
			$args['db-type'] = $requirements['type'] ?? 'N/A';
			$args['db-current-title'] = $this->createCopy( 'smw-setupcheck-db-current-title' );
			$args['db-minimum-title'] = $this->createCopy( 'smw-setupcheck-db-minimum-title' );
			$args['db-current-version'] = $requirements['latest_version'] ?? 'N/A';
			$args['db-minimum-version'] = $requirements['minimum_version'] ?? 'N/A';
		}

		// The type is expected to match a defined target and in an event
		// that those don't match an exception will be raised.
		$this->templateEngine->compile(
			$value['type'],
			$args
		);

		return $this->templateEngine->publish( $value['type'] );
	}

	private function createProgressIndicator( $value ) {

		$maintenanceMode = (array)$this->setupFile->getMaintenanceMode();
		$content = '';

		foreach ( $maintenanceMode as $key => $v ) {

			$args = [
				'label' => $key,
				'value' => $v
			];

			if ( isset( $value['progress_keys'][$key] ) ) {
				$args['label'] = $this->createCopy( $value['progress_keys'][$key] );
			}

			$this->templateEngine->compile(
				'setupcheck-progress',
				$args
			);

			$content .= $this->templateEngine->publish( 'setupcheck-progress' );
		}

		return $content;
	}

	private function createCopy( $value, $default = 'n/a' ) {

		if ( is_string( $value ) && $this->localMessageProvider->has( $value ) ) {
			return $this->localMessageProvider->msg( $value );
		}

		return $default;
	}

	private function buildHTML( array $error ) {

		$args = [
			'logo' => Logo::get( 'small' ),
			'title' => $error['title'] ?? '',
			'indicator' => $error['indicator_title'] ?? '',
			'content' => $error['content'] ?? '',
			'borderColor' => $error['borderColor'] ?? '#fff',
			'refresh' => $error['refresh'] ?? '30',
		];

		$this->templateEngine->compile(
			'setupcheck-html',
			$args
		);

		$html = $this->templateEngine->publish( 'setupcheck-html' );

		// Minify CSS rules, we keep them readable in the template to allow for
		// better adaption
		// @see http://manas.tungare.name/software/css-compression-in-php/
		$html = preg_replace_callback( "/<style\\b[^>]*>(.*?)<\\/style>/s", function( $matches ) {
				// Remove space after colons
				$style = str_replace( ': ', ':', $matches[0] );

				// Remove whitespace
				return str_replace( [ "\r\n", "\r", "\n", "\t", '  ', '    ', '    '], '', $style );
			},
			$html
		);

		return $html;
	}

}
