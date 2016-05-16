<?php
//session_start();
//error_reporting(E_ALL);
include "conf.php";

/**
 * @param $api
 * @param $productID - selected product ID in cash register
 */
function apiRequests($api, $productID) {

//get warehouses
    $warehouse = $api->sendRequest("getWarehouses", []);
    $outputW = json_decode($warehouse, true);
    apiErrorCheck($outputW);
    $warehouses = [];
    $products = [];

//all locations
    foreach ($outputW['records'] as $data) {
        $warehouses[] = array("id" => $data['warehouseID'], "name" => $data['name']);
    }

//request to get parent ID
    $params = array("productID" => $productID);
    $product = $api->sendRequest("getProducts", $params);
    $output = json_decode($product, true);
    apiErrorCheck($output);
    if (key_exists('parentProductID', $output['records'][0])) {
        $selectedProduct = $output['records'][0]['parentProductID'];
    } else {
        echo "<div style='font-size:16px;'> No data available! </div>";

        return;
    }

//request to get all variations
    $params = array("productID" => $selectedProduct, "getMatrixVariations" => 1);
    $product = $api->sendRequest("getProducts", $params);
    $output = json_decode($product, true);
    apiErrorCheck($output);
    $productVariationsIDs = $output['records'][0]['productVariations'];
    $idString = implode(",", $productVariationsIDs);
    $variationProducts = $output['records'][0]['variationList'];
//sort all products
    foreach ($variationProducts as $value) {
        foreach ($value['dimensions'] as $v) {
            $products[] = array("id" => $value['productID'], "name" => $v['name'], "value" => $v['value'], "code" => $v['code'], "dimensionID" => $v['dimensionID'], "dimensionValueID" => $v['dimensionValueID']);
        }
    }
    $products = compactProductDimensions($products, $productVariationsIDs);
    $_SESSION['productsDim'] = $products;

//get stock state, bulk
    $parameters = [];

    foreach ($warehouses as $value) {
        $parameters[] = array(
            'requestName'   => 'getProductStock',
            'productIDs'    => $idString,
            'warehouseID'   => $value['id'],
            "recordsOnPage" => 100
        );
    }

    $counter = 100;
    $stock = 0;

    if (count($parameters) > 100) {
        for ($i = 0; $i < $counter; $i++) {
            if ($counter >= 100) {
                $counter += 100;
                $stockTemp = $api->sendRequest('', array('requests' => json_encode($parameters)));
                $parameters = [];
                $stock = array_merge($stock, $stockTemp);
            }
        }
    } else {
        $stock = $api->sendRequest('', array('requests' => json_encode($parameters)));
    }

    $output = json_decode($stock, true);
    apiErrorCheck($output);
    for ($i = 0; $i < count($output['requests']); $i++) {
        $warehouse_product[] = array("warehouseID" => $warehouses[$i]['id'], "name" => $warehouses[$i]['name'], "products" => $output['requests'][$i]['records']);
    }
//join stock and products
    $stock_products = joinStockProduct($warehouse_product, $products);
    $_SESSION['tableData'] = $stock_products;
//separated color, sizes and locations
    $data = separateColorLocationsSize();
    $_SESSION['separatedDimensions'] = $data;
}

//----------------------------------------------END API --------------------------------------------------------

/**
 * @return array of selected table data
 */
function switchReport($switchTable) {

    $reportSelection = array(
        'renderStoreSizeColor',
        'renderSizeColorStore',
        'renderStoreColorSize',
        'renderSizeStoreColor',
        'renderColorStoreSize',
        'renderColorSizeStore'
    );

    if ($switchTable !=null && in_array($switchTable, $reportSelection)) {
        return call_user_func($switchTable);
    }

    return renderSizeStoreColor();
}


/**
 * check api request errors
 *
 * @param $output - api request response
 */
function apiErrorCheck($output) {

    if ($output['status']['errorCode'] != 0) {
        die("Could not get data!! Error code: " . $output['status']['errorCode']);
    }
}


/**
 * make array of product attributes
 * @param $products - all products
 * @param $productVariationsIDs - id's of product variations
 *
 * @return array of product id, size and color. Key is product id
 */
function compactProductDimensions($products, $productVariationsIDs) {

    $productArray = [];
    $size = 0;
    //make array
    foreach ($productVariationsIDs as $value) {
        $productArray[$value] = array("id" => $value, "size" => "", "color" => "");
    }
    //fill array with color and size
    foreach ($products as $value) {
        foreach ($products as $v) {
            if ($value['id'] == $v['id']) {
                if ($value['name'] == "Size") {
                    $size = $value['code'];
                } elseif ($v['name'] == "Size") {
                    $size = $v['code'];
                } elseif ($value['name'] == "Color") {
                    $color = $value['code'];
                } elseif ($v['name'] == "Color") {
                    $color = $v['code'];
                }
                $productArray[$value['id']] = array("id" => $v['id'], "size" => $size, "color" => $color);
            }
        }
    }

    return $productArray;
}


/**
 * join products, stock amount and product values
 * @param $warehouseProduct - array of warehouse name, id and productarray
 * @param $products - array of product id, size and color. Key is product id
 *
 * @return array of products with stock amounts
 */
function joinStockProduct($warehouseProduct, $products) {

    $stockProducts = [];
    foreach ($warehouseProduct as $value) {
        if (empty($value['products'])) {
            $stockProducts[] = array("warehouseID" => $value['warehouseID'], "name" => $value['name'], "productID" => "-", "amountInStock" => 0, "size" => "-", "color" => "-");
        } else {
            foreach ($value['products'] as $v) {
                $amount = $v['amountInStock'];
                if (strlen(substr(strrchr($amount, "."), 1)) == 0) {
                    $amount = number_format($v['amountInStock'], 2);
                } else {
                    $amount = floor($v['amountInStock']);
                }
                $stockProducts[] = array("warehouseID" => $value['warehouseID'], "name" => $value['name'], "productID" => $v['productID'], "amountInStock" => $amount, "size" => $products[$v['productID']]['size'], "color" => $products[$v['productID']]['color']);
            }
        }
    }

    return $stockProducts;
}


/**
 * sorted data, separated colors, locations, sizes
 * @return array of color, location and size arrays
 */
function separateColorLocationsSize() {

    $tableData = $_SESSION['tableData'];
    $productDim = $_SESSION['productsDim'];
    $locations = [];
    $locNames = [];
    $colors = [];
    $sizes = [];
    //locations
    for ($i = 0; $i < count($tableData); $i++) {
        if (!in_array($tableData[$i]['name'], $locNames)) {
            $locNames[] = $tableData[$i]['name'];
            $locations[] = array("name" => $tableData[$i]['name'], "id" => $tableData[$i]['warehouseID']);
        }
    }
    $data['location'] = $locations;
    //sizes,colors
    foreach ($productDim as $value) {
        if (!in_array($value['size'], $sizes)) {
            $sizes[] = $value['size'];
        }
        if (!in_array($value['color'], $colors)) {
            $colors[] = $value['color'];
        }
    }
    $data['size'] = $sizes;
    $data['color'] = $colors;

    return $data;
}


/**
 * find productId by color and size
 * @param $size - variation
 * @param $color - variation
 * @param $productDim - array of product id, size and color. Key is product id
 *
 * @return id of product
 */

function getProductID($size, $color, $productDim) {

    foreach ($productDim as $dim) {
        if ($dim['size'] == $size && $dim['color'] == $color) {
            return $dim['id'];
        }
    }
}


/**
 * calculate totals and add to table data SIZE-STORE-COLOR
 * @param $finalTabelData - data array of size-store-color table
 *
 * @return array  - add totals to array
 */
function addTotalsTableData($finalTabelData) {

    $columns = count($finalTabelData[0]);
    $rows = count($finalTabelData);
    //count row totals!
    for ($j = 1; $j < $rows; $j++) {
        $total = 0;
        for ($i = 2; $i < $columns - 1; $i++) {
            if (key_exists($j, $finalTabelData) && key_exists($i, $finalTabelData[$j])) {
                $total += $finalTabelData[$j][$i];
            }

        }
        $finalTabelData[$j][$i] = $total;
    }
    //count sub columns total
    for ($i = 2; $i < $columns; $i++) {
        $total = 0;
        $subTotals = 0;
        for ($j = 1; $j < $rows; $j++) {
            if ($finalTabelData[$j][1]['name'] == "Total all sizes:") {
                $finalTabelData[$j][$i] = $subTotals;
                break;
            }
            if ((strpos($finalTabelData[$j][1]['name'], 'total') != false)) {
                $finalTabelData[$j][$i] = $total;
                $subTotals += $total;
                $total = 0;
                continue;
            }
            if (key_exists($j, $finalTabelData) && key_exists($i, $finalTabelData[$j])) {
                $total += $finalTabelData[$j][$i];
            }

        }
    }

    return $finalTabelData;
}


/**
 * makes array with table column headers
 * @param $cols - amount of table columns
 * @param $data - header data
 *
 * @return array of table data, added table headers
 */
function makeTableHeaders($cols, $data) {

    $render = [];
    $counter = 0;
    for ($i = 0; $i < $cols; $i++) {
        if ($i == 0) {
            $render[0][0] = array("name" => "");
        } elseif ($i == 1) {
            $render[0][1] = array("name" => " Stock Levels");
        } elseif ($i == $cols - 1) {
            $render[0][$cols - 1] = array("name" => "All");
        } else {
            if (is_array($data[$counter])) {
                $render[0][$i] = array("name" => $data[$counter]['name'], "id" => $data[$counter]['id']);
            } else {
                $render[0][$i] = array("name" => $data[$counter]);
            }
            $counter++;
        }
    }

    return $render;
}


/**
 * gives values to table first column
 * @param $rows - amount of table rows
 * @param $data - first column data
 * @param $render - table data array with header values
 * @param $firstColumnValue - first column value type
 * @param $secondColumnValue - second column value type
 *
 * @return array of table data, added first column values
 */
function makeTableFirstColumn($rows, $data, $render, $firstColumnValue, $secondColumnValue) {

    $colCounter = 1;
    $counter = 0;
    for ($j = 1; $j < $rows; $j++) {
        $render[$j][0] = array("name" => "");
        if ($colCounter == $j) {

            if (key_exists($counter, $data[$firstColumnValue]) && is_array($data[$firstColumnValue][$counter])) {
                $render[$j][0] = array("name" => $data[$firstColumnValue][$counter]['name'],
                                       "id"   => $data[$firstColumnValue][$counter]['id']);
            } else {
                if (key_exists($counter, $data[$firstColumnValue])) {
                    $render[$j][0] = array("name" => $data[$firstColumnValue][$counter]);
                }
            }
            $counter += 1;
            $colCounter += count($data[$secondColumnValue]) + 1;
        }
    }

    return $render;
}


/**
 * gives values to table second column
 * @param $rows - amount of table rows
 * @param $data - first column data
 * @param $render - table data array with header and first column values
 * @param $firstColumnValue - first column value type
 * @param $secondColumnValue -  second column value type
 *
 * @return array of table data, added second column values
 */
function makeTableSecondColumn($rows, $data, $render, $firstColumnValue, $secondColumnValue) {

    $colCounter = count($data[$secondColumnValue]) + 1;
    $counter = 0;
    for ($j = 1; $j < $rows; $j++) {
        $i = $rows - 1;
        if ($j == $i) {
            $render[$j][1] = array("name" => "Total all sizes:");
        } elseif ($counter == count($data[$secondColumnValue])) {
            $render[$j][1] = array("name" => $firstColumnValue . " total:");
            $colCounter += $colCounter;
            $counter = 0;
        } else {
            if (is_array($data[$secondColumnValue][$counter])) {
                $render[$j][1] = array("name" => $data[$secondColumnValue][$counter]['name'], "id" => $data[$secondColumnValue][$counter]['id']);
                $counter++;
            } else {
                $render[$j][1] = array("name" => $data[$secondColumnValue][$counter]);
                $counter++;
            }
        }
    }

    return $render;
}


/**
 * add amounts Size-Color-Store
 * @param $render - table data (header, first and second column values
 * @param $tableData - amount data
 * @param $productDim  - array of product id, size and color. Key is product id
 *
 * @return table data array - added amounts
 */
function addAmountsTableDataSizeColorStore($render, $tableData, $productDim) {

    foreach ($tableData as $value) {
        for ($i = 2; $i < count($render[0]) - 1; $i++) {
            $size = $value['size'];
            $size1 = $render[0][$i]['name'];
            if ($size == $size1) {
                for ($j = 1; $j < count($render); $j++) {
                    if (key_exists(0, $render[$j]) && key_exists('name', $render[$j][0]) && $render[$j][0]['name'] != null) {
                        $color = $render[$j][0]['name'];
                    }

                    $productId = getProductID($size, $color, $productDim);
                    $name = $render[$j][1]['name'];
                    $render[$j][$i] = 0;

                    if ($value['productID'] == $productId && $value['name'] == $name) {
                        $render[$j][$i] = $value['amountInStock'];
                    }
                }
            }
        }
    }

    return $render;
}


/**
 * add amounts Size-Store-Color
 * @param $render - table data (header, first and second column values
 * @param $tableData - amount data
 * @param $productDim  - array of product id, size and color. Key is product id
 *
 * @return table data array - added amounts
 */
function addAmountsTableDataSizeStoreColor($render, $tableData, $productDim) {

    foreach ($tableData as $value) {
        for ($i = 2; $i < count($render[0]) - 1; $i++) {
            $size = $value['size'];
            $size1 = $render[0][$i]['name'];
            if ($size == $size1) {
                for ($j = 1; $j < count($render); $j++) {
                    if (key_exists($j, $render) && key_exists(0, $render[$j]) && key_exists('id', $render[$j][0]) &&
                        $render[$j][0]['id'] != null
                    ) {
                        $location = $render[$j][0]['id'];
                    }
                    $color = $render[$j][1]['name'];
                    $productId = getProductID($size, $color, $productDim);
                    $render[$j][$i] = 0;

                    if ($value['productID'] == $productId && $value['warehouseID'] == $location) {
                        $render[$j][$i] = $value['amountInStock'];
                    }
                }
            }
        }
    }

    return $render;
}


/**
 * add amounts Color-Store-Size
 * add amounts Size-Store-Color
 * @param $render - table data (header, first and second column values
 * @param $tableData - amount data
 * @param $productDim  - array of product id, size and color. Key is product id
 *
 * @return table data array - added amounts
 */
function addAmountsTableDataColorStoreSize($render, $tableData, $productDim) {

    foreach ($tableData as $value) {
        for ($i = 2; $i < count($render[0]) - 1; $i++) {
            $color = $value['color'];
            $color1 = $render[0][$i]['name'];
            if ($color == $color1) {
                for ($j = 1; $j < count($render); $j++) {
                    if (key_exists($j, $render) && key_exists(0, $render[$j]) && key_exists('id', $render[$j][0]) &&
                        $render[$j][0]['id'] != null
                    ) {
                        $location = $render[$j][0]['id'];
                    }
                    $size = $render[$j][1]['name'];
                    $productId = getProductID($size, $color, $productDim);
                    $render[$j][$i] = 0;

                    if ($value['productID'] == $productId && $value['warehouseID'] == $location) {
                        $render[$j][$i] = $value['amountInStock'];
                    }
                }
            }
        }
    }

    return $render;
}


/**
 * add amounts Color-Size-Store
 * @param $render - table data (header, first and second column values
 * @param $tableData - amount data
 * @param $productDim  - array of product id, size and color. Key is product id
 *
 * @return table data array - added amounts
 */
function addAmountsTableDataColorSizeStore($render, $tableData, $productDim) {

    foreach ($tableData as $value) {
        for ($i = 2; $i < count($render[0]) - 1; $i++) {
            $color = $value['color'];
            $color1 = $render[0][$i]['name'];
            if ($color == $color1) {
                for ($j = 1; $j < count($render); $j++) {
                    if (key_exists($j, $render) && key_exists(0, $render[$j]) && key_exists('name', $render[$j][0]) &&
                        $render[$j][0]['name'] != null
                    ) {
                        $size = $render[$j][0]['name'];
                    }

                    if (key_exists($j, $render) && key_exists(1, $render[$j]) && key_exists('id', $render[$j][1])) {
                        $location = $render[$j][1]['id'];
                    }

                    $productId = getProductID($size, $color, $productDim);
                    $render[$j][$i] = 0;
                    if ($value['productID'] == $productId && $value['warehouseID'] == $location) {
                        $render[$j][$i] = $value['amountInStock'];
                    }
                }
            }
        }
    }

    return $render;
}


/**
 * add amounts Store-Size-Color
 * @param $render - table data (header, first and second column values
 * @param $tableData - amount data
 * @param $productDim  - array of product id, size and color. Key is product id
 *
 * @return table data array - added amounts
 */
function addAmountsTableDataStoreSizeColor($render, $tableData, $productDim) {

    foreach ($tableData as $value) {
        for ($i = 2; $i < count($render[0]) - 1; $i++) {
            $location = $value['warehouseID'];
            $location1 = $render[0][$i]['id'];
            if ($location == $location1) {
                for ($j = 1; $j < count($render); $j++) {
                    if (key_exists($j, $render) && key_exists(0, $render[$j]) && key_exists('name', $render[$j][0]) &&
                        $render[$j][0]['name'] != null
                    ) {
                        $size = $render[$j][0]['name'];
                    }
                    $color = $render[$j][1]['name'];
                    $productId = getProductID($size, $color, $productDim);
                    $render[$j][$i] = 0;
                    if ($value['productID'] == $productId) {
                        $render[$j][$i] = $value['amountInStock'];
                    }
                }
            }
        }
    }

    return $render;
}


/**
 * add amounts Store-Color-Size
 * @param $render - table data (header, first and second column values
 * @param $tableData - amount data
 * @param $productDim  - array of product id, size and color. Key is product id
 *
 * @return table data array - added amounts
 */
function addAmountsTableDataStoreColorSize($render, $tableData, $productDim) {

    foreach ($tableData as $value) {
        for ($i = 2; $i < count($render[0]) - 1; $i++) {
            $location = $value['warehouseID'];
            $location1 = $render[0][$i]['id'];
            if ($location == $location1) {
                for ($j = 1; $j < count($render); $j++) {
                    if (key_exists($j, $render) && key_exists(0, $render[$j]) && key_exists('name', $render[$j][0]) &&
                        $render[$j][0]['name'] != null
                    ) {
                        $color = $render[$j][0]['name'];
                    }
                    $size = $render[$j][1]['name'];
                    $productId = getProductID($size, $color, $productDim);
                    $render[$j][$i] = 0;
                    if ($value['productID'] == $productId) {
                        $render[$j][$i] = $value['amountInStock'];
                    }
                }
            }
        }
    }

    return $render;
}
