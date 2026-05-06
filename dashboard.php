<?php
session_start();
include("db.php");

// SESSION CHECK
if(!isset($_SESSION['user'])){
    header("Location: login.php");
    exit();
}

/* MAIN TABLE QUERY */
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

/* CHART QUERY */
$catQuery = "
SELECT c.category_name, COUNT(p.id) as total
FROM products p
INNER JOIN categories c ON p.category_id = c.id
GROUP BY c.category_name
";

$catResult = $conn->query($catQuery);

$categories = [];
$totals = [];

while($row = $catResult->fetch_assoc()){
    $categories[] = $row['category_name'];
    $totals[] = $row['total'];
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>VaultInventory Dashboard</title>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        body {
            font-family: Arial;
            margin: 0;
            background: #f4f4f4;
        }

        .header {
            background: #2c3e50;
            color: white;
            padding: 15px;
            text-align: center;
            position: relative;
        }

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

        .container {
            width: 90%;
            margin: auto;
            margin-top: 20px;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0px 0px 10px rgba(0,0,0,0.1);
        }

        .add-btn{
            display:inline-block;
            margin-bottom:10px;
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

        /* SEARCH + SORT */
        .tools{
            margin: 10px 0;
        }

        input, select{
            padding:8px;
            margin-right:10px;
        }

        .btn {
            padding: 5px 10px;
            text-decoration: none;
            color: white;
            border-radius: 5px;
            font-size: 12px;
        }

        .edit { background: #3498db; }
        .delete { background: #e74c3c; }

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

        .chart-box{
            margin-bottom: 30px;
            max-width: 500px;
        }
    </style>
</head>

<body>

<div class="header">
    <h2>VaultInventory Dashboard</h2>
    <a class="logout" href="logout.php">Logout</a>
</div>

<div class="container">

    <!-- ADD BUTTON -->
    <a href="addproduct.php" class="add-btn">+ Add Product</a>

    <!-- SEARCH + SORT -->
    <div class="tools">
        <input type="text" id="searchInput" placeholder="Search product...">

        <select id="sortSelect">
            <option value="">Sort By</option>
            <option value="name">Product Name</option>
            <option value="price">Price</option>
            <option value="qty">Quantity</option>
        </select>
    </div>

    <!-- CHART -->
    <div class="chart-box">
        <h3>Products per Category</h3>
        <canvas id="myChart"></canvas>
    </div>

    <!-- TABLE -->
    <table id="productTable">
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

<!-- CHART SCRIPT -->
<script>
const ctx = document.getElementById('myChart');

new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?= json_encode($categories) ?>,
        datasets: [{
            label: 'Number of Products',
            data: <?= json_encode($totals) ?>,
            backgroundColor: '#3498db'
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});
</script>

<!-- SEARCH + SORT SCRIPT -->
<script>
// SEARCH
document.getElementById("searchInput").addEventListener("keyup", function() {
    let value = this.value.toLowerCase();
    let rows = document.querySelectorAll("#productTable tr");

    rows.forEach((row, index) => {
        if(index === 0) return;

        row.style.display = row.innerText.toLowerCase().includes(value) ? "" : "none";
    });
});

// SORT
document.getElementById("sortSelect").addEventListener("change", function() {
    let table = document.getElementById("productTable");
    let rows = Array.from(table.rows).slice(1);
    let value = this.value;

    rows.sort((a, b) => {
        let A, B;

        if(value === "name"){
            A = a.cells[0].innerText.toLowerCase();
            B = b.cells[0].innerText.toLowerCase();
            return A.localeCompare(B);
        }

        if(value === "price"){
            A = parseFloat(a.cells[4].innerText);
            B = parseFloat(b.cells[4].innerText);
            return A - B;
        }

        if(value === "qty"){
            A = parseInt(a.cells[3].innerText);
            B = parseInt(b.cells[3].innerText);
            return A - B;
        }

        return 0;
    });

    rows.forEach(row => table.appendChild(row));
});
</script>

</body>
</html>