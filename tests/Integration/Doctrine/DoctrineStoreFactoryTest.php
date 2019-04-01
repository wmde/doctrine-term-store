<?php

declare( strict_types = 1 );

namespace Wikibase\TermStore\Tests\Integration\Doctrine;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Types\Type;
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
		$this->assertTableExists( 'wbt_property_terms' );
		$this->assertTableExists( 'wbt_term_in_lang' );
		$this->assertTableExists( 'wbt_text_in_lang' );
		$this->assertTableExists( 'wbt_text' );
		$this->assertTableExists( 'wbt_type' );
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

	public function testCreateSchemaCreatesItemTermsColumns() {
		$this->newStoreFactory()->createSchema();

		$columns = $this->connection->getSchemaManager()->listTableColumns( 'wbt_item_terms' );

		$this->assertTrue( $columns['id']->getAutoincrement(), 'id column should have auto increment' );
		$this->assertTrue( $columns['id']->getNotnull(), 'id column should not be nullable' );
		$this->assertContains(
			$columns['id']->getType()->getName(),
			[ Type::BIGINT, Type::INTEGER ],
			'id column should have BIGINT or INTEGER type'
		);
	}

	public function testCreateSchemaCreatesItemTermsIndexes() {
		$this->newStoreFactory()->createSchema();

		$table = $this->connection->getSchemaManager()->listTableDetails( 'wbt_item_terms' );

		$this->assertSame(
			[ 'id' ],
			$table->getPrimaryKey()->getColumns(),
			'primary key should be on id column'
		);

		$this->assertSame(
			[ 'item_id' ],
			$table->getIndex( 'wbt_item_terms_item_id' )->getColumns(),
			'wbt_item_terms_item_id index should exist'
		);

		$this->assertSame(
			[ 'term_in_lang_id' ],
			$table->getIndex( 'wbt_item_terms_term_in_lang_id' )->getColumns(),
			'wbt_item_terms_term_in_lang_id index should exist'
		);
	}

}
