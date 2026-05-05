<?php

include("db.php");

if($conn){
    echo "Connected successfully!";
} else {
    echo "Connection failed!";
}
?>