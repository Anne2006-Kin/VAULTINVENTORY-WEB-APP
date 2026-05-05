<?php

session_start();
include("db.php");

if(isset($_POST['login'])){
    $username = $_POST['username'];
    $password = $_POST['password'];

    $result = $conn->query("SELECT * FROM users WHERE username='$username'");

    if($result->num_rows > 0){
        $row = $result->fetch_assoc();

        if($password == $row['password']){
            $_SESSION['user'] = $username;
            header("Location: dashboard.php");
            exit();
        } else {
            echo "<script>alert('Wrong password');</script>";
        }
    } else {
        echo "<script>alert('User not found');</script>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Login</title>

<style>
body{
    font-family: Arial;
    background: linear-gradient(120deg, #1f1c2c, #928dab);
    height: 100vh;
    display:flex;
    justify-content:center;
    align-items:center;
}

.box{
    width:320px;
    background:white;
    padding:25px;
    border-radius:10px;
    box-shadow:0px 0px 15px rgba(0,0,0,0.3);
}

h2{
    text-align:center;
    margin-bottom:20px;
}

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
    background:#27ae60;
    color:white;
    border:none;
    border-radius:5px;
    font-weight:bold;
    cursor:pointer;
}

button:hover{
    background:#219150;
}

.register-btn{
    width:100%;
    padding:10px;
    background:#3498db;
    border:none;
    color:white;
    font-weight:bold;
    border-radius:5px;
    cursor:pointer;
    margin-top:10px;
}

.register-btn:hover{
    background:#2d86c3;
}

p{
    text-align:center;
    margin-top:10px;
    font-size:13px;
}
</style>
</head>

<body>

<div class="box">
    <h2>VaultInventory Login</h2>

    <form method="POST">
        <input type="text" name="username" placeholder="Username" required>
        <input type="password" name="password" placeholder="Password" required>

        <button name="login">Login</button>
    </form>

    <!-- REGISTER BUTTON -->
    <a href="register.php">
        <button type="button" class="register-btn">
            Create Account
        </button>
    </a>

    <p>Don't have an account? Click Create Account</p>
</div>

</body>
</html>
