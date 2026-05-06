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
body{
    margin:0;
    font-family:Arial;
    background:#f4f4f4;
}

/* SIDEBAR */
.sidebar{
    width:200px;
    height:100vh;
    background:#2c3e50;
    position:fixed;
    top:0;
    left:0;
    color:white;
    padding-top:20px;
    text-align:center;
}

/* LOGO */
.logo-box img{
    width:70px;
    height:70px;
    object-fit:cover;
    border-radius:10px;
    background:white;
    padding:5px;
}

.logo-box h3{
    margin:10px 0;
    font-size:16px;
}

.sidebar a{
    display:block;
    color:white;
    padding:12px;
    text-decoration:none;
    text-align:left;
    padding-left:20px;
}

.sidebar a:hover{
    background:#34495e;
}

/* MAIN */
.main{
    margin-left:200px;
    padding:20px;
}

/* HEADER */
.header{
    background:white;
    padding:15px;
    border-radius:10px;
    margin-bottom:20px;
    display:flex;
    justify-content:space-between;
    align-items:center;
}

/* CARDS */
.cards{
    display:flex;
    gap:10px;
    margin-bottom:20px;
}

.card{
    flex:1;
    background:white;
    padding:15px;
    border-radius:10px;
    text-align:center;
}

/* BUTTON */
.add-btn{
    display:inline-block;
    margin-bottom:10px;
    padding:10px 15px;
    background:#2ecc71;
    color:white;
    text-decoration:none;
    border-radius:5px;
}

/* TABLE */
table{
    width:100%;
    border-collapse:collapse;
    background:white;
    border-radius:10px;
    overflow:hidden;
}

th{
    background:#34495e;
    color:white;
    padding:10px;
}

td{
    padding:10px;
    text-align:center;
}

tr:nth-child(even){
    background:#f2f2f2;
}

/* TOOLS */
.tools input, .tools select{
    padding:8px;
    margin:5px;
}

/* BUTTONS */
.btn{
    padding:5px 10px;
    color:white;
    text-decoration:none;
    border-radius:5px;
    font-size:12px;
}

.edit{background:#3498db;}
.delete{background:#e74c3c;}

.chart-box{
    background:white;
    padding:15px;
    border-radius:10px;
    margin-bottom:20px;
    max-width:500px;
}
</style>
</head>

<body>

<!-- SIDEBAR -->
<div class="sidebar">

    <!-- LOGO -->
    <div class="logo-box">
        <img src="logo.png" alt="Logo">
        
    </div>

    <a href="dashboard.php">Dashboard</a>
    <a href="addproduct.php">Add Product</a>
    <a href="logout.php">Logout</a>
</div>

<!-- MAIN -->
<div class="main">

    <!-- HEADER -->
    <div class="header">
        <h2>Warehouse & Supplier Tracker</h2>
    </div>

    <!-- CARDS -->
    <div class="cards">
        <div class="card">
            <h3><?= $result->num_rows ?></h3>
            <p>Total Products</p>
        </div>

        <div class="card">
            <h3><?= count($categories) ?></h3>
            <p>Categories</p>
        </div>

        <div class="card">
            <h3>Active</h3>
            <p>Status</p>
        </div>
    </div>

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

<!-- CHART -->
<script>
const ctx = document.getElementById('myChart');

new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?= json_encode($categories) ?>,
        datasets: [{
            label: 'Products per Category',
            data: <?= json_encode($totals) ?>,
            backgroundColor: '#3498db'
        }]
    }
});
</script>

<!-- SEARCH -->
<script>
document.getElementById("searchInput").addEventListener("keyup", function() {
    let value = this.value.toLowerCase();
    let rows = document.querySelectorAll("#productTable tr");

    rows.forEach((row, index) => {
        if(index === 0) return;
        row.style.display = row.innerText.toLowerCase().includes(value) ? "" : "none";
    });
});
</script>

<!-- SORT -->
<script>
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
            return parseFloat(a.cells[4].innerText) - parseFloat(b.cells[4].innerText);
        }

        if(value === "qty"){
            return parseInt(a.cells[3].innerText) - parseInt(b.cells[3].innerText);
        }

        return 0;
    });

    rows.forEach(row => table.appendChild(row));
});
</script>

</body>
</html>