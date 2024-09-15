<?php
/**
 * Class ValidatedDAO
 * 
 * Provides methods to interact with the 'Validated' table in the database.
 * 
 * Table Structure:
 * `id` int(11) NOT NULL AUTO_INCREMENT,
 * `worksiteId` varchar(50) DEFAULT NULL,
 * `accessId` int(50) NOT NULL,
 * `operationType` int(50) NOT NULL,
 * `startDate` date NOT NULL,
 * `endDate` date NOT NULL,
 * 'comment' varchar(255) NOT NULL,
 * `totalTimeStoppedSeconds` int(11) NOT NULL: The total time stopped until the next journey, in seconds.
 * PRIMARY KEY (`id`).
 * 
 * @author Grégoire Mariette
 * @version 1.0
 */
class ValidatedDAO {
    private mysqli $mysqli;

    public function __construct($mysqli) {
        $this->mysqli = $mysqli;
    }

    /**
     * Inserts a new record into the 'Validated' table.
     * 
     * @param string $worksiteId The worksite ID.
     * @param int $accessId The access ID.
     * @param int $operationType The operation type.
     * @param string $startDate The start date in the format "YYYY-MM-DD".
     * @param string $endDate The end date in the format "YYYY-MM-DD".
     * @param string $comment The comment.
     * @param int $totalTimeStoppedSeconds The total time stopped in seconds.
     * @throws Exception If the insertion fails.
     */
    public function insertEntry($worksiteId, $accessId, $operationType, $startDate, $endDate, $comment, $totalTimeStoppedSeconds): void {
        $stmt = $this->mysqli->prepare(
            "INSERT INTO validated (worksiteId, accessId, operationType, startDate, endDate, comment, totalTimeStoppedSeconds) 
            VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        
        if (!$stmt) {
            throw new Exception("Preparation failed: " . $this->mysqli->error);
        }
        
        $stmt->bind_param('siisssi', $worksiteId, $accessId, $operationType, $startDate, $endDate, $comment, $totalTimeStoppedSeconds);

        if (!$stmt->execute()) {
            throw new Exception("Execution failed: " . $stmt->error);
        }

        $stmt->close();
    }

    /**
     * Checks if a journey with the specified parameters already exists.
     * 
     * @param string $worksiteId The worksite ID.
     * @param int $accessId The access ID.
     * @param string $startDate The start date in the format "YYYY-MM-DD".
     * @return bool True if an entry exists, false otherwise.
     * @throws Exception If the check fails.
     */
    public function entryExists($accessId, $startDate): bool {
        $stmt = $this->mysqli->prepare(
            "SELECT id FROM validated WHERE accessId = ? AND startDate = ?"
        );
        
        if (!$stmt) {
            throw new Exception("Preparation failed: " . $this->mysqli->error);
        }
        
        $stmt->bind_param('is', $accessId, $startDate);

        if (!$stmt->execute()) {
            throw new Exception("Execution failed: " . $stmt->error);
        }

        $result = $stmt->get_result();

        $exists = $result->num_rows > 0;

        $stmt->close();

        return $exists;
    }
}
?>
