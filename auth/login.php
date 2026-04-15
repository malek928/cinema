<?php
// On inclut la configuration de la base de données
require_once __DIR__ . "/../config/db.php";
session_start();

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST["email"];
    $password = $_POST["password"];

    // On cherche l'utilisateur par son email
    $stmt = $pdo->prepare("SELECT * FROM user WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    // Vérification du mot de passe haché
    if ($user && password_verify($password, $user["password"])) {
        // Initialisation des variables de session
        $_SESSION["user_id"] = $user["id"];
        $_SESSION["prenom"] = $user["prenom"]; // On stocke le prénom pour le message de bienvenue

        // Redirection vers la page catalogue
        header("Location: ../films/liste.php");
        exit;
    } else {
        // Message d'erreur en anglais comme tu l'as demandé
        $error = "Invalid email or password";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Cinema</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #141414;
            color: white;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .login-container {
            background: #262626;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.5);
            width: 300px;
        }

        h2 {
            margin-top: 0;
            text-align: center;
        }

        input {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: none;
            border-radius: 4px;
            box-sizing: border-box;
        }

        button {
            width: 100%;
            padding: 10px;
            background: #e50914;
            color: white;
            border: none;
            border-radius: 4px;
            font-weight: bold;
            cursor: pointer;
        }

        button:hover {
            background: #b20710;
        }

        .error {
            color: #e50914;
            font-size: 0.9em;
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="login-container">
        <h2>Login</h2>

        <?php if (!empty($error)): ?>
            <p class="error"><?php echo $error; ?></p>
        <?php endif; ?>

        <form method="POST">
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Sign In</button>
        </form>
        <p style="font-size: 0.8em; text-align: center; margin-top: 15px;">
            New here? <a href="register.php" style="color: #e50914; text-decoration: none;">Register now</a>
        </p>
    </div>
</body>

</html>