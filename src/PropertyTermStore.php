<?php

declare( strict_types = 1 );

namespace Wikibase\TermStore;

use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Term\Fingerprint;

interface PropertyTermStore {

	/**
	 * @throws TermStoreException
	 */
	public function storeTerms( PropertyId $propertyId, Fingerprint $terms );

	/**
	 * @throws TermStoreException
	 */
	public function deleteTerms( PropertyId $propertyId );

	/**
	 * @throws TermStoreException
	 */
	public function getTerms( PropertyId $propertyId ): Fingerprint;

}