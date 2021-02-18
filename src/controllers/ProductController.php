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

function getCategoryID($handle) {
    $productTypes = new ProductTypes;
    $category = $productTypes->getProductTypeByHandle($handle);
    if(!is_null($category)) {
        return $category->id;
    } else {
        return null;
    }
}

function validateDate($date, $format = 'Y-m-d')
{
    $d = \DateTime::createFromFormat($format, $date);
    // The Y ( 4 digits year ) returns TRUE for any integer with any number of digits so changing the comparison from == to === fixes the issue.
    return $d && $d->format($format) === $date;
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
        $isSafe = true;

        //Check if file is CSV
        $mimes = array('text/csv','text/tsv', 'application/csv' , 'text/x-csv' , 'application/vnd.ms-excel', 'text/plain');
        if(in_array($_FILES['CSVInput']['type'],$mimes)){

            //Get Columns
            $columns = fgetcsv($importFile, 50000, ";");
            $columns = array_map("utf8_encode", $columns);

            while($csvLine = fgetcsv ($importFile, 50000, ";"))
            {
                $csvLine = array_map("utf8_encode", $csvLine);
                $product = array();
                $headerStrings = array();

                if( ($csvLine != null) && (count($columns) == count($csvLine))) {
                    for($i = 0; $i < count($columns); ++$i) {
                        //Remove \ufeff from string
                        $header = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $columns[$i]);

                        //Convert to Title Case
                        $headerString = mb_convert_case($header, MB_CASE_TITLE, "UTF-8");

                        //Remove Space
                        $column = str_replace(' ', '', $headerString);
                        $product[$column] = $csvLine[$i];
                        array_push($headerStrings, $headerString);
                    }
                } else {
                    $isSafe = false;
                    array_push($errors, "Er is een probleem, ergens in de file is er te weinig of te veel data ingegeven.");
                }

                array_push($importData, $product);
            }

            if($isSafe) {
                for($p = 0; $p < count($importData); ++$p) {
                    $importProduct = $importData[$p];
                    $requiredColumns = ['FullTitle', 'MainDescription', 'Summary(Html)', 'ShippingDate', 'Deadline', 'Publisher', 'OrderCode', 'RetailPrice', 'Quantity', 'RetailDiscount', 'Category', 'ExpiryDate'];
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
                        if($importProduct['Category'] == null || $importProduct['Category'] == '') {
                            $product->typeId = getCategoryID('games');
                        } else {
                            $category = mb_convert_case($importProduct['Category'], MB_CASE_LOWER, "UTF-8");
                            $catID = getCategoryID($category);

                            if(is_null($catID)) {
                                $errorLine = 'Error at line ' . ($p + 1) . ': Category not found (must be comics, games or rpg)';
                                array_push($arrError, $errorLine);
                            } else {
                                $product->typeId = $catID;
                            }
                        }
                        $product->enabled = false;

                        //Title -> Title
                        $product->title = $importProduct['FullTitle'];

                        //Short Summary -> Main Description Truncated to 100 characters...
                        $summary = strip_tags($importProduct['Summary(Html)']);
                        $product->projectShortSummary = truncate($summary, 100);

                        //Description -> Main Description
                        $product->projectDescription = $importProduct['MainDescription'];

                        //Crowdfunding Or Pre-Order -> Pre-Order
                        $product->crowdfundingOrPreOrder = 'preOrderProject';

                        //Estimated Delivery -> Shipping Date
                        if(validateDate($importProduct['ShippingDate'], 'd/m/Y')) {
                            $product->estimatedDelivery = \DateTime::createFromFormat('d/m/Y', $importProduct['ShippingDate']);
                        } else if(validateDate($importProduct['ShippingDate'], 'd/m/y')) {
                            $product->estimatedDelivery = \DateTime::createFromFormat('d/m/y', $importProduct['ShippingDate']);
                        }

                        //Pledge Deadline -> Deadline
                        if(validateDate($importProduct['Deadline'], 'd/m/Y')) {
                            $product->pledgeDeadline = \DateTime::createFromFormat('d/m/Y', $importProduct['Deadline']);
                        } else if(validateDate($importProduct['Deadline'], 'd/m/y')) {
                            $product->pledgeDeadline = \DateTime::createFromFormat('d/m/y', $importProduct['Deadline']);
                        }

                        //Expiry Date -> ExpiryDate
                        if(validateDate($importProduct['ExpiryDate'], 'd/m/Y')) {
                            $product->expiryDate = \DateTime::createFromFormat('d/m/Y', $importProduct['ExpiryDate']);
                        } else if(validateDate($importProduct['ExpiryDate'], 'd/m/y')) {
                            $product->expiryDate = \DateTime::createFromFormat('d/m/y', $importProduct['ExpiryDate']);
                        }

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

                        if(count($arrError) == 0) {
                            //Set Variant as defaultVariant;
                            $product->setVariants([$variant]);
                            //            $product->defaultVariant
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
            }
        } else {
            array_push($errors, "Filetype: " . $_FILES['CSVInput']['type'] . ". Alleen CSV Bestanden uploaden");
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
