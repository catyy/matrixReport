<?php
//session_start();
//error_reporting(E_ALL);
include "functions.php";

$table_data = null;
$productDim = null;

//table data render Size-Color-Store
function renderSizeColorStore() {

    $tableData = $_SESSION['tableData'];
    $productDim = $_SESSION['productsDim'];
    $data = $_SESSION['separatedDimensions'];
    $rows = (count($data['location']) + 1) * count($data['color']) + 2;
    $cols = count($data['size']) + 3;
    $render = makeTableHeaders($cols, $data['size']);
    $render = makeTableFirstColumn($rows, $data, $render, "color", "location");
    $render = makeTableSecondColumn($rows, $data, $render, "color", "location");
    $finalTabelData = addAmountsTableDataSizeColorStore($render, $tableData, $productDim);
    $finalTabelData = addTotalsTableData($finalTabelData);
    $finalTabelData['heading'] = "Size-Color-Store";
    $finalTabelData['cols'] = $cols;

    return $finalTabelData;
}

//table data render Size-Store-Color
function renderSizeStoreColor() {

    $tableData = $_SESSION['tableData'];
    $productDim = $_SESSION['productsDim'];
    $data = $_SESSION['separatedDimensions'];
    $rows = (count($data['color']) + 1) * count($data['location']) + 2;
    $cols = count($data['size']) + 3;
    $render = makeTableHeaders($cols, $data['size']);
    $render = makeTableFirstColumn($rows, $data, $render, "location", "color");
    $render = makeTableSecondColumn($rows, $data, $render, "location", "color");
    $finalTabelData = addAmountsTableDataSizeStoreColor($render, $tableData, $productDim);
    $finalTabelData = addTotalsTableData($finalTabelData);
    $finalTabelData['heading'] = "Size-Store-Color";
    $finalTabelData['cols'] = $cols;

    return $finalTabelData;
}

//table data render Color-Store-Size
function renderColorStoreSize() {

    $tableData = $_SESSION['tableData'];
    $productDim = $_SESSION['productsDim'];
    $data = $_SESSION['separatedDimensions'];
    $rows = (count($data['size']) + 1) * count($data['location']) + 2;
    $cols = count($data['color']) + 3;
    $render = makeTableHeaders($cols, $data['color']);
    $render = makeTableFirstColumn($rows, $data, $render, "location", "size");
    $render = makeTableSecondColumn($rows, $data, $render, "location", "size");
    $finalTabelData = addAmountsTableDataColorStoreSize($render, $tableData, $productDim);
    $finalTabelData = addTotalsTableData($finalTabelData);
    $finalTabelData['heading'] = "Color-Store-Size";
    $finalTabelData['cols'] = $cols;

    return $finalTabelData;
}

//table data render Color-Size-Store
function renderColorSizeStore() {

    $tableData = $_SESSION['tableData'];
    $productDim = $_SESSION['productsDim'];
    $data = $_SESSION['separatedDimensions'];
    $rows = (count($data['location']) + 1) * count($data['size']) + 2;
    $cols = count($data['color']) + 3;
    $render = makeTableHeaders($cols, $data['color']);
    $render = makeTableFirstColumn($rows, $data, $render, "size", "location");
    $render = makeTableSecondColumn($rows, $data, $render, "size", "location");
    $finalTabelData = addAmountsTableDataColorSizeStore($render, $tableData, $productDim);
    $finalTabelData = addTotalsTableData($finalTabelData);
    $finalTabelData['heading'] = "Color-Size-Store";
    $finalTabelData['cols'] = $cols;

    return $finalTabelData;
}

//table data render Store-Size-Color
function renderStoreSizeColor() {

    $tableData = $_SESSION['tableData'];
    $productDim = $_SESSION['productsDim'];
    $data = $_SESSION['separatedDimensions'];
    $rows = (count($data['color']) + 1) * count($data['size']) + 2;
    $cols = count($data['location']) + 3;
    $render = makeTableHeaders($cols, $data['location']);
    $render = makeTableFirstColumn($rows, $data, $render, "size", "color");
    $render = makeTableSecondColumn($rows, $data, $render, "size", "color");
    $finalTabelData = addAmountsTableDataStoreSizeColor($render, $tableData, $productDim);
    $finalTabelData = addTotalsTableData($finalTabelData);
    $finalTabelData['heading'] = "Store-Size-Color";
    $finalTabelData['cols'] = $cols;

    return $finalTabelData;
}

//table data render Store-Color-Size
function renderStoreColorSize() {

    $tableData = $_SESSION['tableData'];
    $productDim = $_SESSION['productsDim'];
    $data = $_SESSION['separatedDimensions'];
    $rows = (count($data['size']) + 1) * count($data['color']) + 2;
    $cols = count($data['location']) + 3;
    $render = makeTableHeaders($cols, $data['location']);
    $render = makeTableFirstColumn($rows, $data, $render, "color", "size");
    $render = makeTableSecondColumn($rows, $data, $render, "color", "size");
    $finalTabelData = addAmountsTableDataStoreColorSize($render, $tableData, $productDim);
    $finalTabelData = addTotalsTableData($finalTabelData);
    $finalTabelData['heading'] = "Store-Color-Size";
    $finalTabelData['cols'] = $cols;
//    print "<pre>";
//    print_r($table_data);
//    print "</pre>";
    return $finalTabelData;
}
