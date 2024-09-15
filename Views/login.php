<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/prestanaute/config.php');
require_once(APP_ROOT . '/Controllers/UsersController.php');
$usersController = new UsersController();

$error_message = "";

function sanitizeString($input) {
    return htmlspecialchars(filter_var($input, FILTER_SANITIZE_STRING), ENT_QUOTES, 'UTF-8');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $usersController->connect();
    $username = sanitizeString($_POST['username']);
    $password = sanitizeString($_POST['password']);

    $result = $usersController->login($username, $password);
    if ($result['status'] == 'success') {
        // If not already started, start the session
        if (!session_id()) {
            echo "session started";
            session_start();
        }
        $_SESSION['username'] = $result['data']['username'];
        $_SESSION['vehicleId'] = $result['data']['vehicleId'];
        $_SESSION['accessId'] = $result['data']['accessId'];
        $_SESSION['vehicleRegistration'] = $result['data']['vehicleRegistration'];
        
        if($_SESSION['username'] == 'newuser') {
            header('Location: register.php');
            exit;
        }
        else {
            header('Location: journey.php');
            exit;
        }
    } else {
        $error_message = $result['message'];
    }
}
//<p>Don't have an account? <a href="register.php">Register here</a></p>
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <link rel="stylesheet" href="/prestanaute/assets/css/styles.css">
    <link rel="icon" href="/prestanaute/assets/img/tabicon.ico" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inconsolata:wght@500&family=Josefin+Sans:wght@300&family=Open+Sans&display=swap" rel="stylesheet">

</head>
<body class="login-page">
    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post" class="user-form">
        <input type="text" name="username" class="user-form-text" placeholder="Nom d'utilisateur" required>
        <input type="password" name="password" class="user-form-text" placeholder="Mot de passe" required>
        <input type="submit" class="user-form-button" value="Login">
    </form>
    
    <?php
    if (!empty($error_message)) {
        echo '<div class="error">' . $error_message . '</div>';
    }
    ?>
    <footer class="login-footer">
        <p>Annapurna I Nepal 8091m</p>
        
        <div>
            <img src="../assets/img/icons/BD-logo-white.png" alt="" srcset="">  
            <p>2023 Bertrand Darimont</p> 
        </div>
    </footer>
</body>
</html>
