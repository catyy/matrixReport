<?php
//session_start();
include("EAPIclass.php");

$api = new EAPI();

if (isset($_POST['clientCode']) && isset($_POST['sessionKey'])) {
    $api->clientCode = $_POST['clientCode'];
    $_SESSION['clientCode'] = $_POST['clientCode'];
    $_SESSION['apiSessionKey'] = $_POST['sessionKey'];
} else {
    $api->clientCode = $_SESSION['clientCode'];
    $sessionKey = $_SESSION['apiSessionKey'];
}
$api->url = "https://" . $api->clientCode . ".erply.com/api/";



