<?php

namespace SMW;

use ExtensionDependencyError;
use SMW\Exception\ConfigPreloadFileNotReadableException;

/**
 * @private
 *
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class UncaughtExceptionHandler {

	/**
	 * @var SetupCheck
	 */
	private $setupCheck;

	/**
	 * @since 3.2
	 *
	 * @param SetupCheck $setupCheck
	 */
	public function __construct( SetupCheck $setupCheck ) {
		$this->setupCheck = $setupCheck;
	}

	/**
	 * @since 3.2
	 *
	 * @param Throwable $e
	 */
	public function registerHandler( $e ) {

		$message = $e->getMessage();

		if ( $e instanceof ConfigPreloadFileNotReadableException ) {
			return $this->reportConfigPreloadError( $e );
		}

		// There is no better way to fetch the specific info other then comparing
		// a string because there is no dedicated exception thrown by the
		// `ExtensionRegistry`.
		if (
			strpos( $message, 'SemanticMediaWiki' ) !== false &&
			strpos( $message, 'extension.json' ) !== false ) {
			return $this->reportExtensionRegistryError( $e );
		}

		// We only care for those extensions that directly relate to Semantic
		// MediaWiki
		if (
			strpos( $message, 'Semantic' ) !== false &&
			$e instanceof ExtensionDependencyError ) {
			return $this->reportExtensionDependencyError( $e );
		}

		throw $e;
	}

	private function reportConfigPreloadError( $e ) {

		$this->setupCheck->setErrorMessage(
			$e->getMessage()
		);

		$this->setupCheck->setErrorType(
			SetupCheck::ERROR_CONFIG_PROFILE_UNKNOWN
		);

		$this->setupCheck->showErrorAndAbort(
			$this->setupCheck->isCli()
		);
	}

	private function reportExtensionRegistryError( $e ) {

		$this->setupCheck->setErrorMessage(
			$e->getMessage()
		);

		$this->setupCheck->setErrorType(
			SetupCheck::ERROR_EXTENSION_REGISTRY
		);

		$this->setupCheck->setTraceString(
			$e->getTraceAsString()
		);

		$this->setupCheck->showErrorAndAbort(
			$this->setupCheck->isCli()
		);
	}

	private function reportExtensionDependencyError( $e ) {

		$this->setupCheck->setErrorMessage(
			$e->getMessage()
		);

		if ( isset( $e->incompatibleCore ) && $e->incompatibleCore ) {
			$errorType = SetupCheck::ERROR_EXTENSION_INCOMPATIBLE;
		} elseif ( isset( $e->incompatiblePhp ) && $e->incompatiblePhp ) {
			$errorType = SetupCheck::ERROR_EXTENSION_INCOMPATIBLE;
		} elseif ( isset( $e->incompatibleExtensions ) && $e->incompatibleExtensions !== [] ) {
			$errorType = SetupCheck::ERROR_EXTENSION_INCOMPATIBLE;
		} elseif ( isset( $e->missingExtensions ) && count( $e->missingExtensions ) > 1 ) {
			$errorType = SetupCheck::ERROR_EXTENSION_DEPENDENCY_MULTIPLE;
		} else {
			$errorType = SetupCheck::ERROR_EXTENSION_DEPENDENCY;
		}

		$this->setupCheck->setErrorType(
			$errorType
		);

		$this->setupCheck->showErrorAndAbort(
			$this->setupCheck->isCli()
		);
	}

}

