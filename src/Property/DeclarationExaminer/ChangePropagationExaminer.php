<?php

namespace SMW\Property\DeclarationExaminer;

use SMW\DIProperty;
use SMW\MediaWiki\Jobs\ChangePropagationDispatchJob;
use SMW\Property\DeclarationExaminer as IDeclarationExaminer;
use SMW\SemanticData;
use SMW\Store;

/**
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class ChangePropagationExaminer extends DeclarationExaminer {

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var SemanticData
	 */
	private $semanticData;

	/**
	 * @var bool
	 */
	private $isLocked = false;

	/**
	 * @var bool
	 */
	private $changePropagationProtection = true;

	/**
	 * @since 3.1
	 *
	 * @param DeclarationExaminer $declarationExaminer
	 * @param Store $store
	 * @param SemanticData|null $semanticData
	 */
	public function __construct( IDeclarationExaminer $declarationExaminer, Store $store, ?SemanticData $semanticData = null ) {
		$this->declarationExaminer = $declarationExaminer;
		$this->store = $store;
		$this->semanticData = $semanticData;
	}

	/**
	 * @since 3.1
	 *
	 * @param bool $changePropagationProtection
	 */
	public function setChangePropagationProtection( $changePropagationProtection ) {
		$this->changePropagationProtection = (bool)$changePropagationProtection;
	}

	/**
	 * @see DeclarationExaminer::getSemanticData
	 *
	 * {@inheritDoc}
	 */
	public function getSemanticData() {
		return $this->semanticData;
	}

	/**
	 * @see DeclarationExaminer::isLocked
	 *
	 * {@inheritDoc}
	 */
	public function isLocked() {
		return $this->isLocked;
	}

	/**
	 * @see ExaminerDecorator::validate
	 *
	 * {@inheritDoc}
	 */
	protected function validate( DIProperty $property ) {
		$subject = $property->getCanonicalDiWikiPage();
		$semanticData = $this->store->getSemanticData( $subject );

		if ( $this->semanticData === null ) {
			$this->semanticData = $semanticData;
		}

		if ( $semanticData->hasProperty( new DIProperty( DIProperty::TYPE_CHANGE_PROP ) ) ) {
			$this->isChangePropagation( $property );
		} else {
			$this->checkForPendingChangePropagationDispatchJob( $property );
		}
	}

	private function isChangePropagation( $property ) {
		$severity = 'warning';
		$this->isLocked = true;

		if ( $this->changePropagationProtection ) {
			$severity = 'error';
		}

		$this->messages[] = [
			$severity,
			'smw-property-req-violation-change-propagation-locked-' . $severity,
			$property->getLabel()
		];
	}

	private function checkForPendingChangePropagationDispatchJob( $property ) {
		$subject = $property->getCanonicalDiWikiPage();

		if ( !ChangePropagationDispatchJob::hasPendingJobs( $subject ) ) {
			return;
		}

		$this->messages[] = [
			'warning',
			'smw-property-req-violation-change-propagation-pending',
			ChangePropagationDispatchJob::getPendingJobsCount( $subject )
		];
	}

}
