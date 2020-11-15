<?php
/**
 * Import Products plugin for Craft CMS 3.x
 *
 * Import CSV to create Products
 *
 * @link      http://www.upclose.be
 * @copyright Copyright (c) 2020 Davy Delbeke
 */

namespace upclose\importproducts;


use Craft;
use craft\base\Plugin;
use craft\services\Plugins;
use craft\events\PluginEvent;

use yii\base\Event;

/**
 * Class ImportProducts
 *
 * @author    Davy Delbeke
 * @package   ImportProducts
 * @since     1.0.0
 *
 */
class ImportProducts extends Plugin
{
    // Static Properties
    // =========================================================================

    /**
     * @var ImportProducts
     */
    public static $plugin;

    // Public Properties
    // =========================================================================

    /**
     * @var string
     */
    public $schemaVersion = '1.0.0';

    /**
     * @var bool
     */
    public $hasCpSettings = false;

    /**
     * @var bool
     */
    public $hasCpSection = true;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        self::$plugin = $this;

        Event::on(
            Plugins::class,
            Plugins::EVENT_AFTER_INSTALL_PLUGIN,
            function (PluginEvent $event) {
                if ($event->plugin === $this) {
                }
            }
        );

        Craft::info(
            Craft::t(
                'import-products',
                '{name} plugin loaded',
                ['name' => $this->name]
            ),
            __METHOD__
        );
    }

    // Protected Methods
    // =========================================================================

}
