<?php
include("db.php");

$id = $_GET['id'];

$result = $conn->query("SELECT * FROM products WHERE id=$id");
$row = $result->fetch_assoc();

if(isset($_POST['update'])){
    $name = $_POST['product_name'];
    $qty = $_POST['quantity'];
    $price = $_POST['price'];

    $conn->query("UPDATE products SET 
        product_name='$name',
        quantity='$qty',
        price='$price'
        WHERE id=$id");

    header("Location: dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Edit Product</title>

<style>
body{
    font-family: Arial;
    background:#f4f4f4;
}

.box{
    width:400px;
    margin:50px auto;
    background:white;
    padding:20px;
    border-radius:10px;
    box-shadow:0px 0px 10px rgba(0,0,0,0.1);
}

h2{text-align:center;}

input{
    width:100%;
    padding:10px;
    margin:8px 0;
    border:1px solid #ccc;
    border-radius:5px;
}

button{
    width:100%;
    padding:10px;
    background:#2980b9;
    color:white;
    border:none;
    border-radius:5px;
    cursor:pointer;
}

button:hover{
    background:#1f6690;
}
</style>
</head>

<body>

<div class="box">
<h2>Edit Product</h2>

<form method="POST">
<input type="text" name="product_name" value="<?= $row['product_name'] ?>" required>
<input type="number" name="quantity" value="<?= $row['quantity'] ?>" required>
<input type="number" name="price" value="<?= $row['price'] ?>" required>

<button name="update">Update</button>
</form>

</div>

</body>

</html>
