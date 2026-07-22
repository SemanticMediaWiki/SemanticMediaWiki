<?php

namespace SMW\Property\DeclarationExaminer;

use SMW\DataItems\Property;
use SMW\DataModel\SemanticData;
use SMW\MediaWiki\Jobs\ChangePropagationDispatchJob;
use SMW\Property\DeclarationExaminer as IDeclarationExaminer;
use SMW\Store;

/**
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class ChangePropagationExaminer extends DeclarationExaminer {

	private bool $isLocked = false;

	private bool $changePropagationProtection = true;

	/**
	 * @since 3.1
	 */
	public function __construct(
		IDeclarationExaminer $declarationExaminer,
		private readonly Store $store,
		private ?SemanticData $semanticData = null,
	) {
		$this->declarationExaminer = $declarationExaminer;
	}

	/**
	 * @since 3.1
	 *
	 * @param bool $changePropagationProtection
	 */
	public function setChangePropagationProtection( $changePropagationProtection ): void {
		$this->changePropagationProtection = (bool)$changePropagationProtection;
	}

	/**
	 * @see DeclarationExaminer::getSemanticData
	 *
	 * {@inheritDoc}
	 */
	public function getSemanticData(): ?SemanticData {
		return $this->semanticData;
	}

	/**
	 * @see DeclarationExaminer::isLocked
	 *
	 * {@inheritDoc}
	 */
	public function isLocked(): bool {
		return $this->isLocked;
	}

	/**
	 * @see ExaminerDecorator::validate
	 *
	 * {@inheritDoc}
	 */
	protected function validate( Property $property ): void {
		$subject = $property->getCanonicalDiWikiPage();
		if ( $subject === null ) {
			return;
		}

		$semanticData = $this->store->getSemanticData( $subject );

		if ( $this->semanticData === null ) {
			$this->semanticData = $semanticData;
		}

		// The `_CHGPRO` marker only reflects an active lock while a change
		// propagation is genuinely pending. A dispatch job that failed or was
		// lost can leave the marker behind with no pending job, which used to
		// lock the page indefinitely (#4344), so cross-check the job queue.
		if (
			$semanticData->hasProperty( new Property( Property::TYPE_CHANGE_PROP ) ) &&
			ChangePropagationDispatchJob::hasPendingJobs( $subject )
		) {
			$this->isChangePropagation( $property );
		} else {
			$this->checkForPendingChangePropagationDispatchJob( $property );
		}
	}

	private function isChangePropagation( Property $property ): void {
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

	private function checkForPendingChangePropagationDispatchJob( Property $property ): void {
		$subject = $property->getCanonicalDiWikiPage();

		if ( $subject === null || !ChangePropagationDispatchJob::hasPendingJobs( $subject ) ) {
			return;
		}

		$this->messages[] = [
			'warning',
			'smw-property-req-violation-change-propagation-pending',
			ChangePropagationDispatchJob::getPendingJobsCount( $subject )
		];
	}

}
