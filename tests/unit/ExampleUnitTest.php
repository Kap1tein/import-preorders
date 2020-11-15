<?php
/**
 * Import Products plugin for Craft CMS 3.x
 *
 * Import CSV to create Products
 *
 * @link      http://www.upclose.be
 * @copyright Copyright (c) 2020 Davy Delbeke
 */

namespace upclose\importproductstests\unit;

use Codeception\Test\Unit;
use UnitTester;
use Craft;
use upclose\importproducts\ImportProducts;

/**
 * ExampleUnitTest
 *
 *
 * @author    Davy Delbeke
 * @package   ImportProducts
 * @since     1.0.0
 */
class ExampleUnitTest extends Unit
{
    // Properties
    // =========================================================================

    /**
     * @var UnitTester
     */
    protected $tester;

    // Public methods
    // =========================================================================

    // Tests
    // =========================================================================

    /**
     *
     */
    public function testPluginInstance()
    {
        $this->assertInstanceOf(
            ImportProducts::class,
            ImportProducts::$plugin
        );
    }

    /**
     *
     */
    public function testCraftEdition()
    {
        Craft::$app->setEdition(Craft::Pro);

        $this->assertSame(
            Craft::Pro,
            Craft::$app->getEdition()
        );
    }
}
