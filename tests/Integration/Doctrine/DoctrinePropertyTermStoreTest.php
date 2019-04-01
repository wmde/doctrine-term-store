<?php

declare( strict_types = 1 );

namespace Wikibase\TermStore\Tests\Integration\Doctrine;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\TermStore\PackagePrivate\Doctrine\DoctrinePropertyTermStore;

/**
 * @covers \Wikibase\TermStore\PackagePrivate\Doctrine\DoctrinePropertyTermStore
 */
class DoctrinePropertyTermStoreTest extends TestCase {

	public function testGetTrue() {
		$this->assertNull( ( new DoctrinePropertyTermStore() )->deleteTerms( new PropertyId( 'P1' ) ) );
	}

}
