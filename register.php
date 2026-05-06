<?php
include("db.php");

if(isset($_POST['register'])){
    $username = $_POST['username'];

    //   PASSWORD 
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
    background: #f4f4f4;
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
}

/* CARD */
.container{
    background: white;
    padding: 30px;
    width: 300px;
    border-radius: 10px;
    box-shadow: 0px 0px 10px rgba(0,0,0,0.2);
    text-align: center;
}

h2{
    margin-bottom: 20px;
}

/* INPUTS */
input{
    width: 90%;
    padding: 10px;
    margin: 8px 0;
    border: 1px solid #ccc;
    border-radius: 5px;
}

/* BUTTON */
button{
    width: 100%;
    padding: 10px;
    background: #2ecc71;
    border: none;
    color: white;
    border-radius: 5px;
    cursor: pointer;
}

button:hover{
    background: #27ae60;
}

/* LOGIN LINK BUTTON */
a{
    display: block;
    margin-top: 15px;
    text-decoration: none;
    color: #3498db;
}
</style>

</head>

<body>

<div class="container">

<h2>Register</h2>

<form method="POST">
    <input type="text" name="username" placeholder="Username" required>
    <input type="password" name="password" placeholder="Password" required>

    <button name="register">Register</button>
</form>

<a href="login.php">← Back to Login</a>

</div>

</body>

</html>
