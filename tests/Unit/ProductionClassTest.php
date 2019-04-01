<?php

declare( strict_types = 1 );

namespace Wikibase\TermStore\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Wikibase\TermStore\PropertyTermStore;

/**
 * @covers \Wikibase\TermStore\PropertyTermStore
 */
class ProductionClassTest extends TestCase {

	public function testGetTrue() {
		$this->assertTrue( true );
	}

}
