<?php

namespace SMW;

use MediaWiki\Registration\ExtensionDependencyError;
use SMW\Exception\ConfigPreloadFileNotReadableException;
use Throwable;

/**
 * @private
 *
 * @license GPL-2.0-or-later
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
	 * @throws Throwable
	 */
	public function registerHandler( Throwable $e ): void {
		$message = $e->getMessage();

		if ( $e instanceof ConfigPreloadFileNotReadableException ) {
			$this->reportConfigPreloadError( $e );
			return;
		}

		// There is no better way to fetch the specific info other then comparing
		// a string because there is no dedicated exception thrown by the
		// `ExtensionRegistry`.
		if (
			strpos( $message, 'SemanticMediaWiki' ) !== false &&
			strpos( $message, 'extension.json' ) !== false ) {
			$this->reportExtensionRegistryError( $e );
			return;
		}

		// We only care for those extensions that directly relate to Semantic
		// MediaWiki
		if (
			strpos( $message, 'Semantic' ) !== false &&
			$e instanceof ExtensionDependencyError ) {
				$this->reportExtensionDependencyError( $e );
			return;
		}

		throw $e;
	}

	/**
	 * @param ConfigPreloadFileNotReadableException $e
	 */
	private function reportConfigPreloadError( ConfigPreloadFileNotReadableException $e ): void {
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

	/**
	 * @param Throwable $e
	 */
	private function reportExtensionRegistryError( Throwable $e ): void {
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

	/**
	 * @param ExtensionDependencyError $e
	 */
	private function reportExtensionDependencyError( ExtensionDependencyError $e ): void {
		$this->setupCheck->setErrorMessage(
			$e->getMessage()
		);

		if ( $e->incompatibleCore ) {
			$errorType = SetupCheck::ERROR_EXTENSION_INCOMPATIBLE;
		} elseif ( $e->incompatiblePhp ) {
			$errorType = SetupCheck::ERROR_EXTENSION_INCOMPATIBLE;
		} elseif ( $e->incompatibleExtensions !== [] ) {
			$errorType = SetupCheck::ERROR_EXTENSION_INCOMPATIBLE;
		} elseif ( $e->missingExtensions && count( $e->missingExtensions ) > 1 ) {
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
