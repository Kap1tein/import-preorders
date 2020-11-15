<?php
/**
 * Import Products plugin for Craft CMS 3.x
 *
 * Import CSV to create Products
 *
 * @link      http://www.upclose.be
 * @copyright Copyright (c) 2020 Davy Delbeke
 */

namespace upclose\importproducts\assetbundles\importproductscpsection;

use Craft;
use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

/**
 * @author    Davy Delbeke
 * @package   ImportProducts
 * @since     1.0.0
 */
class ImportProductsCPSectionAsset extends AssetBundle
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->sourcePath = "@upclose/importproducts/assetbundles/importproductscpsection/dist";

        $this->depends = [
            CpAsset::class,
        ];

        $this->js = [
            'js/ImportProducts.js',
        ];

        $this->css = [
            'css/ImportProducts.css',
        ];

        parent::init();
    }
}
