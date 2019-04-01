<?php

declare( strict_types = 1 );

namespace Wikibase\TermStore\Tests\Integration\Doctrine;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use PHPUnit\Framework\TestCase;
use Wikibase\TermStore\DoctrineStoreFactory;

/**
 * @covers \Wikibase\TermStore\DoctrineStoreFactory
 * @covers \Wikibase\TermStore\PackagePrivate\Doctrine\DoctrineSchemaCreator
 */
class DoctrineStoreFactoryTest extends TestCase {

	/**
	 * @var Connection
	 */
	private $connection;

	public function setUp() {
		$this->connection = DriverManager::getConnection( [
			'driver' => 'pdo_sqlite',
			'memory' => true,
		] );
	}

	public function testCreateSchemaCreatesTables() {
		$this->newStoreFactory()->createSchema();

		$this->assertTableExists( 'wbt_item_terms' );
	}

	private function newStoreFactory(): DoctrineStoreFactory {
		return new DoctrineStoreFactory( $this->connection );
	}

	private function assertTableExists( string $tableName ) {
		$this->assertTrue(
			$this->connection->getSchemaManager()->tablesExist( $tableName ),
			'Table "' . $tableName . '" should exist'
		);
	}

}
