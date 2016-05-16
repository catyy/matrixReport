<?php
session_start();
error_reporting(E_ALL);
include "tableViewFunctions.php";

$data = [];

//print_r($_POST);

if (isset( $_POST['name']) && isset( $_POST['productId'])) {
    $productID = (int) $_POST['productId'];   //example 30364;
    $name =  $_POST['name'];
    apiRequests($api, $productID);
} else {
    $name = "No data!";
    return;
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Reports</title>
    <script src="Bootstrap/js/jquery-2.2.1.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js"></script>
    <link href="Bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <script src="Bootstrap/js/bootstrap.min.js"></script>
    <link rel="stylesheet" type="text/css" href="main.css">

</head>
<body>
<div>
    <h4><?php echo htmlentities($name,ENT_QUOTES) ?></h4>
    <form method="post" action="">

        <div class="container1">
            <select name="view" id="view" class="selectpicker" data-style="btn-primary">
                <option>Select report type</option>
                <option value="renderColorSizeStore">Color - Size - Store</option>
                <option value="renderColorStoreSize">Color - Store - Size</option>
                <option value="renderSizeColorStore">Size - Color - Store</option>
                <option value="renderSizeStoreColor">Size - Store - Color</option>
                <option value="renderStoreColorSize">Store - Color - Size</option>
                <option value="renderStoreSizeColor">Store - Size - Color</option>

            </select>
            <input type="submit" onclick="getNewTableData()" value="Submit"/>
        </div>

        <?php
        if (isset($_POST['tableType'])) {
            $data = switchReport($_POST['tableType']);
        } else {
            $data = renderSizeColorStore();
        }
        $cols = $data['cols'];

        echo "<h4>" . htmlentities($data['heading'],ENT_QUOTES) . "</h4>";
        echo "<table class='table table-striped table-bordered'>\n";

        for ($j = 0; $j < count($data) - 2; $j++) {
            if ($j == 0) {
                echo "<tr>\n";
                for ($i = 0; $i < $cols; $i++) {
                    echo "<th><b>" . htmlentities( $data[0][$i]['name'],ENT_QUOTES) . "</b></th>\n";
                }
                echo "</tr>\n";
            } else {
                echo "<tr>\n";
                for ($i = 0; $i < $cols; $i++) {
                    if ($i == 0 || $i == 1) {
                        if (strpos($data[$j][1]['name'], 'total') !== false || strpos($data[$j][1]['name'], 'Total') !== false) {
                            if (key_exists($j, $data) && key_exists($i, $data[$j])) {
                                echo "<td class='grey'>" . htmlentities ($data[$j][$i]['name'],ENT_QUOTES) . "</td>\n";

                            }
                        } else {
                            echo "<td class='bold'>" . htmlentities($data[$j][$i]['name'], ENT_QUOTES) . "</td>\n";
                        }
                    } else {
                        if (strpos($data[$j][1]['name'], 'total') !== false || strpos($data[$j][1]['name'], 'Total') !== false) {
                            echo "<td class='grey'>" . htmlentities($data[$j][$i],ENT_QUOTES) . "</td>\n";
                        } else {
                            if (key_exists($j, $data) && key_exists($i, $data[$j])) {
                                echo "<td class='padding'>" . htmlentities($data[$j][$i],ENT_QUOTES) . "</td>\n";
                            }

                        }
                    }
                }
                echo "</tr>";
            }
        }
        echo "</table>\n"; ?>
        <br>
</div>
</body>
</html>
