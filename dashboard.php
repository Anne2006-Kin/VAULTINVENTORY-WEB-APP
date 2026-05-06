<?php
session_start();
include("db.php");

// FIXED session check (make sure this matches login.php)
if(!isset($_SESSION['user'])){
    header("Location: login.php");
    exit();
}

$query = "
SELECT p.id, p.product_name, c.category_name, s.supplier_name, p.quantity, p.price
FROM products p
INNER JOIN categories c ON p.category_id = c.id
INNER JOIN suppliers s ON p.supplier_id = s.id
";

$result = $conn->query($query);

if(!$result){
    die("SQL Error: " . $conn->error);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>VaultInventory Dashboard</title>

    <style>
        body {
            font-family: Arial;
            margin: 0;
            background: #f4f4f4;
        }

        /* HEADER */
        .header {
            background: #2c3e50;
            color: white;
            padding: 15px;
            text-align: center;
            position: relative;
        }

        /* LOGOUT BUTTON */
        .logout {
            position: absolute;
            right: 20px;
            top: 15px;
            background: red;
            padding: 8px 12px;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }

        /* CONTAINER */
        .container {
            width: 90%;
            margin: auto;
            margin-top: 20px;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0px 0px 10px rgba(0,0,0,0.1);
        }

        /* ADD BUTTON */
        .add-btn{
            display:inline-block;
            margin-bottom:15px;
            padding:10px 15px;
            background:#2ecc71;
            color:white;
            text-decoration:none;
            border-radius:5px;
            font-weight:bold;
        }

        .add-btn:hover{
            background:#27ae60;
        }

        /* EDIT & DELETE BUTTONS */
        .btn {
            padding: 5px 10px;
            text-decoration: none;
            color: white;
            border-radius: 5px;
            font-size: 12px;
        }

        .edit {
            background: #3498db;
        }

        .delete {
            background: #e74c3c;
        }

        /* TABLE */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th {
            background: #34495e;
            color: white;
            padding: 10px;
        }

        td {
            padding: 10px;
            text-align: center;
        }

        tr:nth-child(even) {
            background: #f2f2f2;
        }

    </style>
</head>

<body>

<div class="header">
    <h2>VaultInventory Dashboard</h2>
    <a class="logout" href="logout.php">Logout</a>
</div>

<div class="container">

    <!-- ADD PRODUCT BUTTON -->
    <a href="addproduct.php" class="add-btn">+ Add Product</a>

    <table>
        <tr>
            <th>Product</th>
            <th>Category</th>
            <th>Supplier</th>
            <th>Quantity</th>
            <th>Price</th>
            <th>Action</th>
        </tr>

        <?php while($row = $result->fetch_assoc()){ ?>
        <tr>
            <td><?= $row['product_name'] ?></td>
            <td><?= $row['category_name'] ?></td>
            <td><?= $row['supplier_name'] ?></td>
            <td><?= $row['quantity'] ?></td>
            <td><?= $row['price'] ?></td>
            <td>
                <a class="btn edit" href="editproduct.php?id=<?= $row['id'] ?>">Edit</a>
                <a class="btn delete" href="deleteproduct.php?id=<?= $row['id'] ?>"
                   onclick="return confirm('Are you sure?')">Delete</a>
            </td>
        </tr>
        <?php } ?>
    </table>

</div>

</body>
</html>