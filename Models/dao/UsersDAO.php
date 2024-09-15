<?php 
/**
 *  Class users is used to access to the table users in the database
 *  the table has the following structure :
 *  `id` smallint(6) NOT NULL AUTO_INCREMENT,
 *  `username` varchar(64) NOT NULL,
 *  `password` varchar(64) NOT NULL,
 *  `vehicleId` varchar(64) NOT NULL,
 *  `accessId` smallint(6) NOT NULL,
 *  `vehiculeRegistration` varchar(32) NOT NULL,
 *  PRIMARY KEY (`User_Id`),
 *  UNIQUE KEY `User_Name` (`User_Name`)
 */
class UsersDAO {
    private mysqli $connection;

    public function __construct(mysqli $connection) {
        $this->connection = $connection;
    }

    public function login(string $username, string $password): array {
        $stmt = $this->connection->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to retrieve user details: " . $stmt->error);
        }

        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user && password_verify($password, $user['password'])) {
            return [
                'username' => $user['username'],
                'vehicleId' => $user['vehicleId'],
                'accessId' => $user['accessId'],
                'vehicleRegistration' => $user['vehicleRegistration']
            ];
        } else {
            throw new Exception("Invalid username or password");
        }
    }

    public function register(string $username, string $password, string $vehicleId, int $accessId, string $vehiculeRegistration): void {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $this->connection->prepare("INSERT INTO users (username, password, vehicleId, accessId, vehicleRegistration) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssis", $username, $hashedPassword, $vehicleId, $accessId, $vehiculeRegistration);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to register user: " . $stmt->error);
        }
    }
}
?>