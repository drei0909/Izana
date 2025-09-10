<?php
session_start();
include_once __DIR__. "/../classes/config.php";
session_unset();
session_destroy();

header("Location: ".BASE_URL."admin_L.php");

exit();
?>