<?php

declare( strict_types = 1 );

namespace Wikibase\TermStore\Tests\Integration\Doctrine;

use Doctrine\DBAL\DriverManager;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\TermStore\DoctrineStoreFactory;
use Wikibase\TermStore\PackagePrivate\Doctrine\DoctrinePropertyTermStore;
use Wikibase\TermStore\PropertyTermStore;

/**
 * @covers \Wikibase\TermStore\PackagePrivate\Doctrine\DoctrinePropertyTermStore
 */
class DoctrinePropertyTermStoreTest extends TestCase {

	const UNKNOWN_PROPERTY_ID = 'P404';

	/**
	 * @var PropertyTermStore
	 */
	private $store;

	public function setUp() {
		$factory = new DoctrineStoreFactory(
			DriverManager::getConnection( [
				'driver' => 'pdo_sqlite',
				'memory' => true,
			] )
		);

		$factory->createSchema();

		$this->store = $factory->newPropertyTermStore();
	}

	public function testWhenPropertyIsNotStored_getTermsReturnsEmptyFingerprint() {
		$this->assertEquals(
			new Fingerprint(),
			$this->store->getTerms( new PropertyId( self::UNKNOWN_PROPERTY_ID ) )
		);
	}

}
