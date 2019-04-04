<?php

declare( strict_types = 1 );

namespace Wikibase\TermStore\Tests\Integration\Doctrine;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Types\Type;
use PHPUnit\Framework\TestCase;
use Wikibase\TermStore\DoctrineStoreFactory;
use Wikibase\TermStore\PackagePrivate\Doctrine\Tables;

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

	public function testInstallCreatesTables() {
		$this->newStoreFactory()->install();

		$this->assertTableExists( Tables::ITEM_TERMS );
		$this->assertTableExists( Tables::PROPERTY_TERMS );
		$this->assertTableExists( Tables::TERM_IN_LANGUAGE );
		$this->assertTableExists( Tables::TEXT_IN_LANGUAGE );
		$this->assertTableExists( Tables::TEXT );
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

	public function testInstallCreatesItemTermsColumns() {
		$this->newStoreFactory()->install();

		$columns = $this->connection->getSchemaManager()->listTableColumns( Tables::ITEM_TERMS );

		$this->assertTrue( $columns['id']->getAutoincrement(), 'id column should have auto increment' );
		$this->assertTrue( $columns['id']->getNotnull(), 'id column should not be nullable' );
		$this->assertContains(
			$columns['id']->getType()->getName(),
			[ Type::BIGINT, Type::INTEGER ],
			'id column should have BIGINT or INTEGER type'
		);
	}

	public function testInstallCreatesItemTermsIndexes() {
		$this->newStoreFactory()->install();

		$table = $this->connection->getSchemaManager()->listTableDetails( Tables::ITEM_TERMS );

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

	public function testUninstallDropsTables() {
		$this->newStoreFactory()->install();
		$this->newStoreFactory()->uninstall();

		$this->assertTableDoesNotExist( Tables::ITEM_TERMS );
		$this->assertTableDoesNotExist( Tables::PROPERTY_TERMS );
		$this->assertTableDoesNotExist( Tables::TERM_IN_LANGUAGE );
		$this->assertTableDoesNotExist( Tables::TEXT_IN_LANGUAGE );
		$this->assertTableDoesNotExist( Tables::TEXT );
	}

	private function assertTableDoesNotExist( string $tableName ) {
		$this->assertFalse(
			$this->connection->getSchemaManager()->tablesExist( $tableName ),
			'Table "' . $tableName . '" should NOT exist'
		);
	}

}
