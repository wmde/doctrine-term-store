<?php

declare( strict_types = 1 );

namespace Wikibase\TermStore\PackagePrivate\Doctrine;

use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;

class DoctrineSchemaCreator {

	private $schemaManager;
	private $tableNames;

	public function __construct( AbstractSchemaManager $schemaManager, TableNames $tableNames ) {
		$this->schemaManager = $schemaManager;
		$this->tableNames = $tableNames;
	}

	public function createSchema() {
		$this->schemaManager->createTable( $this->newItemTermsTable() );
		$this->schemaManager->createTable( $this->newPropertyTermsTable() );
		$this->schemaManager->createTable( $this->newTermInLangTable() );
		$this->schemaManager->createTable( $this->newTextInLangTable() );
		$this->schemaManager->createTable( $this->newTextTable() );
	}

	private function newItemTermsTable(): Table {
		$table = new Table( $this->tableNames->itemTerms() );

		$table->addColumn( 'wbit_id', Type::BIGINT, [ 'autoincrement' => true, 'unsigned' => true ] );
		$table->addColumn( 'wbit_item_id', Type::INTEGER, [ 'unsigned' => true ] );
		$table->addColumn( 'wbit_term_in_lang_id', Type::INTEGER, [ 'unsigned' => true ] );

		$table->setPrimaryKey( [ 'wbit_id' ] );
		$table->addIndex( [ 'wbit_item_id' ], $this->tableNames->prefix( 'wbt_item_terms_item_id' ) );
		$table->addIndex( [ 'wbit_term_in_lang_id' ], $this->tableNames->prefix( 'wbt_item_terms_term_in_lang_id' ) );

		return $table;
	}

	private function newPropertyTermsTable(): Table {
		$table = new Table( $this->tableNames->propertyTerms() );

		$table->addColumn( 'wbpt_id', Type::INTEGER, [ 'autoincrement' => true, 'unsigned' => true ] );
		$table->addColumn( 'wbpt_property_id', Type::INTEGER, [ 'unsigned' => true ] );
		$table->addColumn( 'wbpt_term_in_lang_id', Type::INTEGER, [ 'unsigned' => true ] );

		$table->setPrimaryKey( [ 'wbpt_id' ] );
		$table->addIndex( [ 'wbpt_property_id' ], $this->tableNames->prefix( 'wbt_property_terms_property_id' ) );
		$table->addIndex( [ 'wbpt_term_in_lang_id' ], $this->tableNames->prefix( 'wbt_property_terms_term_in_lang_id' ) );

		return $table;
	}

	private function newTermInLangTable(): Table {
		$table = new Table( $this->tableNames->termInLanguage() );

		$table->addColumn( 'wbtl_id', Type::INTEGER, [ 'autoincrement' => true, 'unsigned' => true ] );
		$table->addColumn( 'wbtl_type_id', Type::INTEGER, [ 'unsigned' => true ] );
		$table->addColumn( 'wbtl_text_in_lang_id', Type::INTEGER, [ 'unsigned' => true ] );

		$table->setPrimaryKey( [ 'wbtl_id' ] );
		$table->addIndex( [ 'wbtl_type_id' ], $this->tableNames->prefix( 'wbt_term_in_lang_type_id' ) );
		$table->addIndex( [ 'wbtl_text_in_lang_id' ], $this->tableNames->prefix( 'wbt_term_in_lang_text_in_lang_id' ) );

		return $table;
	}

	private function newTextInLangTable(): Table {
		$table = new Table( $this->tableNames->textInLanguage() );

		$table->addColumn( 'wbxl_id', Type::INTEGER, [ 'autoincrement' => true, 'unsigned' => true ] );
		$table->addColumn( 'wbxl_language', Type::BINARY, [ 'length' => 10 ] );
		$table->addColumn( 'wbxl_text_id', Type::INTEGER, [ 'unsigned' => true ] );

		$table->setPrimaryKey( [ 'wbxl_id' ] );
		$table->addIndex( [ 'wbxl_language' ], $this->tableNames->prefix( 'wbt_text_in_lang_language' ) );
		$table->addIndex( [ 'wbxl_text_id' ], $this->tableNames->prefix( 'wbt_text_in_lang_text_id' ) );

		return $table;
	}

	private function newTextTable(): Table {
		$table = new Table( $this->tableNames->text() );

		$table->addColumn( 'wbx_id', Type::INTEGER, [ 'autoincrement' => true, 'unsigned' => true ] );
		$table->addColumn( 'wbx_text', Type::BINARY, [ 'length' => 255 ] );

		$table->setPrimaryKey( [ 'wbx_id' ] );
		$table->addUniqueIndex( [ 'wbx_text' ], $this->tableNames->prefix( 'wbt_text_text' ) );

		return $table;
	}

}
