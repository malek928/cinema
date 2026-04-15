<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="container">
    <div class="card">

        <h2>Login</h2>

        <form method="POST" action="login.php">
            <input name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Login</button>
        </form>

        <p><?php if(isset($error)) echo $error; ?></p>

    </div>
</div>

</body>
</html>