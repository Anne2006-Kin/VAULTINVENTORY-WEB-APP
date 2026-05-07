<?php
include("db.php");

if(isset($_POST['register'])){
    $username = $_POST['username'];

    // PASSWORD HASHING
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
    $stmt->bind_param("ss", $username, $password);
    $stmt->execute();

    echo "<script>alert('Registered successfully!'); window.location='login.php';</script>";
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Register</title>

<style>
body{
    font-family: Arial;

    /* ✅ BACKGROUND IMAGE */
    background-image: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)),
                      url("Warehouse.jpg"); /* change if needed */

    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;

    height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    margin: 0;
}

/* CARD */
.container{
    background: rgba(255,255,255,0.95);
    padding: 30px;
    width: 320px;
    border-radius: 12px;
    box-shadow: 0px 10px 25px rgba(0,0,0,0.4);
    text-align: center;
    backdrop-filter: blur(5px);
}

/* TITLE */
h2{
    margin-bottom: 20px;
    color: #333;
}

/* INPUTS */
input{
    width: 100%;
    padding: 10px;
    margin: 8px 0;
    border: 1px solid #ccc;
    border-radius: 5px;
}

/* REGISTER BUTTON */
button{
    width: 100%;
    padding: 10px;
    background: #2ecc71;
    border: none;
    color: white;
    border-radius: 5px;
    cursor: pointer;
    font-weight: bold;
}

button:hover{
    background: #27ae60;
}

/* LOGIN LINK */
a{
    display: block;
    margin-top: 15px;
    text-decoration: none;
    color: #3498db;
}

a:hover{
    text-decoration: underline;
}
</style>

</head>

<body>

<div class="container">

<h2>Create Account</h2>

<form method="POST">
    <input type="text" name="username" placeholder="Username" required>
    <input type="password" name="password" placeholder="Password" required>

    <button name="register">Register</button>
</form>

<a href="login.php">← Back to Login</a>

</div>

</body>
</html>