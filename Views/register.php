<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/prestanaute/config.php');
require_once(APP_ROOT . '/Controllers/UsersController.php');

if (!session_id()) {
    session_start();
}

$message = "";  // Placeholder for feedback messages

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $vehicleId = $_POST['vehicleId'];
    $accessId = $_POST['accessId'];
    $vehicleRegistration = $_POST['vehicleRegistration'];

    $usersController = new UsersController();
    $usersController->connect();
    $message = $usersController->register($username, $password, $vehicleId, $accessId, $vehicleRegistration);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link rel="stylesheet" href="/prestanaute/assets/css/styles.css">
</head>

<body class="login-page">
    <?php
    if (!empty($error_message)) {
        echo '<div class="error">' . $error_message . '</div>';
    }
    ?>
    <h2>Register</h2>
    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post" class="user-form">
        <input type="text" id="username" name="username" class="user-form-text" placeholder="Username" required>
        <input type="password" id="password" name="password" class="user-form-text" placeholder="Password" required>
        <input type="text" id="vehicleRegistration" name="vehicleRegistration" class="user-form-text" placeholder="Vehicle Registration" required>
        <input type="text" id="vehicleId" name="vehicleId" class="user-form-text" placeholder="Vehicle ID" required>
        <input type="text" id="accessId" name="accessId" class="user-form-text" placeholder="Access ID" required>
        <input type="submit" value="Register" class="user-form-button">
    </form>
</body>
</html>