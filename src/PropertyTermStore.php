<?php

declare( strict_types = 1 );

namespace Wikibase\TermStore;

use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;

interface PropertyTermStore {

	public function storeTerms( Property $property );

	public function deleteTerms( PropertyId $propertyId );

}