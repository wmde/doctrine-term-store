<?php

declare( strict_types = 1 );

namespace Wikibase\TermStore;

use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Term\Fingerprint;

interface PropertyTermStore {

	public function storeTerms( PropertyId $propertyId, Fingerprint $terms );

	public function deleteTerms( PropertyId $propertyId );

	public function getTerms( PropertyId $propertyId ): Fingerprint;

}