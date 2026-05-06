<?php

session_start();
include("db.php");

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

if(isset($_POST['add'])){
    $name = $_POST['product_name'];
    $category = $_POST['category_id'];
    $supplier = $_POST['supplier_id'];
    $qty = $_POST['quantity'];
    $price = $_POST['price'];

    $stmt = $conn->prepare("INSERT INTO products 
        (product_name, category_id, supplier_id, quantity, price)
        VALUES (?, ?, ?, ?, ?)");

    $stmt->bind_param("siiid", $name, $category, $supplier, $qty, $price);

    $stmt->execute();

    header("Location: dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Add Product</title>

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

h2{
    text-align:center;
}

input, select{
    width:100%;
    padding:10px;
    margin:8px 0;
    border:1px solid #ccc;
    border-radius:5px;
}

button{
    width:100%;
    padding:10px;
    background:#27ae60;
    border:none;
    color:white;
    font-weight:bold;
    border-radius:5px;
    cursor:pointer;
}

button:hover{
    background:#219150;
}

a{
    display:block;
    text-align:center;
    margin-top:10px;
}
</style>
</head>

<body>

<div class="box">
<h2>Add Product</h2>

<form method="POST">

<input type="text" name="product_name" placeholder="Product Name" required>

<select name="category_id" required>
<?php
$result = $conn->query("SELECT * FROM categories");
while($row = $result->fetch_assoc()){
    echo "<option value='{$row['id']}'>{$row['category_name']}</option>";
}
?>
</select>

<select name="supplier_id" required>
<?php
$result = $conn->query("SELECT * FROM suppliers");
while($row = $result->fetch_assoc()){
    echo "<option value='{$row['id']}'>{$row['supplier_name']}</option>";
}
?>
</select>

<input type="number" name="quantity" placeholder="Quantity" required>
<input type="number" step="0.01" name="price" placeholder="Price" required>

<button name="add">Add Product</button>

</form>

<a href="dashboard.php">← Back</a>
</div>

</body>
</html>
