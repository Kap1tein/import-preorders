<?php

namespace upclose\importproducts\controllers;

use Craft;
use craft\web\View;
use craft\commerce\elements\Product;
use craft\commerce\elements\Variant;
use craft\commerce\services\Products;
use craft\commerce\services\ProductTypes;
use craft\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

function truncate($text, $chars = 25) {
    if (strlen($text) <= $chars) {
        return $text;
    }
    $text = $text." ";
    $text = substr($text,0,$chars);
    $text = substr($text,0,strrpos($text,' '));
    $text = $text."...";
    return $text;
}

function checkifUnique($orderCode) {
    $variant = Variant::find()->sku($orderCode)->one();
    return is_null($variant);
}

function getGamesID() {
    $productTypes = new ProductTypes;
    $games = $productTypes->getProductTypeByHandle('games');
    return $games->id;
}

class ProductController extends Controller
{
    public function actionView($id)
    {
        die('Not yet supported!');
    }

    public function actionCreate()
    {
        $importFile = fopen($_FILES['CSVInput']['tmp_name'], 'r');
        $importData = [];
        $errors = [];

        //Check if file is CSV
        $mimes = array('application/vnd.ms-excel','text/plain','text/csv','text/tsv');
        if(in_array($_FILES['CSVInput']['type'],$mimes)){

            //Get Columns
            $columns = fgetcsv($importFile, 10000, ";");
            while(!feof($importFile))
            {
                $csvLine = fgetcsv($importFile, 1000, ';');
                $product = array();

                if( ($csvLine != null) && (count($columns) == count($csvLine))) {
                    for($i = 0; $i < count($columns); ++$i) {
                        //Remove \ufeff from string
                        $header = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $columns[$i]);

                        //Convert to Title Case
                        $headerString = mb_convert_case($header, MB_CASE_TITLE, "UTF-8");

                        //Remove Space
                        $column = str_replace(' ', '', $headerString);
                        $product[$column] = $csvLine[$i];
                    }
                } else {
                    array_push($errors, "Er is een probleem, ergens in de file is er te weinig of te veel data ingegeven.");
                }

                array_push($importData, $product);
            }

            for($p = 0; $p < count($importData); ++$p) {
                $importProduct = $importData[$p];
                $requiredColumns = ['FullTitle', 'MainDescription', 'ShippingDate', 'Deadline', 'Publisher', 'OrderCode', 'RetailPrice', 'Quantity', 'RetailDiscount'];
                $arrError = [];

                for($r = 0; $r < count($requiredColumns); ++$r) {
                    $required = $requiredColumns[$r];

                    if($importProduct[$required] == '') {
                        $errorLine = 'Error at line ' . ($p + 1) . ': "' . $required . '" is required!';
                        array_push($arrError, $errorLine);
                    }
                }

                if(checkifUnique($importProduct['OrderCode'])) {
                    //Create Product
                    $product = new Product();
                    $product->typeId = getGamesID();
                    $product->enabled = false;

                    //Title -> Title
                    $product->title = $importProduct['FullTitle'];

                    //Short Summary -> Main Description Truncated to 100 characters...
                    $product->projectShortSummary = truncate($importProduct['MainDescription'], 100);

                    //Description -> Main Description
                    $product->projectDescription = $importProduct['MainDescription'];

                    //Crowdfunding Or Pre-Order -> Pre-Order
                    $product->crowdfundingOrPreOrder = 'preOrderProduct';

                    //Estimated Delivery -> Shipping Date
                    $product->estimatedDelivery = \DateTime::createFromFormat('d/m/Y', $importProduct['ShippingDate']);

                    //Pledge Deadline -> ??
                    $product->pledgeDeadline = \DateTime::createFromFormat('d/m/Y', $importProduct['Deadline']);

                    //Creator -> Publisher
                    $product->creator = $importProduct['Publisher'];

                    //Create Variant
                    $variant = new Variant();

                    //Variant SKU -> Order Code
                    $variant->SKU = $importProduct['OrderCode'];

                    //Title -> Title
                    $variant->title = $importProduct['FullTitle'];

                    //Old Price -> Retail Price
                    $oldPrice = (float)str_replace(',', '.',$importProduct['RetailPrice']);
                    $variant->oldPrice = $oldPrice;

                    //Quantity
                    $variant->stock = $importProduct['Quantity'];

                    //Price -> Retail Price - Discount
                    $discount = (int)str_replace(',', '.',$importProduct['RetailDiscount']);
                    $price = $oldPrice - (floatval($oldPrice * floatval("0." . $discount)));
                    $variant->price = $price;

                    //Set Variant as defaultVariant;
                    $product->setVariants([$variant]);
                    //            $product->defaultVariant

                    if(count($arrError) == 0) {
                        if (\Craft::$app->elements->saveElement($product)) {
                            $importProduct["Added"] = "true";
                            $importData[$p] = $importProduct;
                        } else {
                            $error = $product->getErrors();

                            $importProduct["Added"] = "false";
                            $importProduct["Errors"] = json_encode($error);
                            $importData[$p] = $importProduct;
                        }
                    } else {
                        $importProduct["Added"] = "false";
                        $importData[$p] = $importProduct;
                    }
                } else {
                    $importProduct["Added"] = "false";
                    $importData[$p] = $importProduct;

                    $errorLine = 'Error at line ' . ($p + 1) . ': "' . $importProduct['OrderCode'] . '" already exists!';
                    array_push($arrError, $errorLine);
                }

                $errors = array_merge($errors, $arrError);
            }
        } else {
            array_push($errors, "Alleen CSV Bestanden uploaden");
        }

        $import = array(
            'data' => $importData,
            'errors' => $errors,
        );


        Craft::$app->getUrlManager()->setRouteParams([
            'import' => $import
        ]);

        return null;
    }
}
