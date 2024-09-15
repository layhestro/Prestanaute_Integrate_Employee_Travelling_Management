<?php
require_once ($_SERVER['DOCUMENT_ROOT'] . '/prestanaute/config.php');
require_once (APP_ROOT . '/Models/classes/Logger.class.php');
require_once (APP_ROOT . '/Models/dao/ConnectionToDataBase.php');
require_once (APP_ROOT . '/Models/dao/UsersDAO.php');

class UsersController {
    private Logger $logger;
    private UsersDAO $usersDAO;
    private ConnectionToDatabase $connectionToDb;

    public function __construct() {
        $this->logger = new Logger();
        $this->connectionToDb = new ConnectionToDatabase();
    }

    public function connect() {
        try {
            $this->connectionToDb->connect();
        } catch (Exception $e) {
            // Handle and display error messages appropriately.
            $this->logger->logError("Error connecting to database", $e->getMessage());
            throw new Exception("Fatal error, unable to connect to database. Please try again later.");
        }
    }

    public function login($username, $password) {
        try {
            if (!isset($this->usersDAO)) {
                $this->usersDAO = new UsersDAO($this->connectionToDb->getConnection());
            }

            $userData = $this->usersDAO->login($username, $password);
            return ["status" => "success", "data" => $userData];

        } catch (Exception $e) {
            // Return a generic error message for the user
            return ["status" => "error", "message" => "Invalid username or password"];
        }
    }
    
    public function register($username, $password, $vehicleId, $accessId, $vehicleRegistration) {
        try {
            if (!isset($this->usersDAO)) {
                $this->usersDAO = new UsersDAO($this->connectionToDb->getConnection());
            }
            $this->usersDAO->register($username, $password, $vehicleId, $accessId, $vehicleRegistration);
        } catch (Exception $e) {
            $this->logger->logError("Error registering user", $e->getMessage());
            return "Fatal error, unable to register user. Please try again later.";
        }
    }

    public function __destruct() {
        $this->connectionToDb->disconnect();
    }
}
?>
