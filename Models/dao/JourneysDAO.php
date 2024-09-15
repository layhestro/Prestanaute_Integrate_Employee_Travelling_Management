<?php 
/**
 * JourneysDAO Class
 *
 * This class provides methods to interact with the 'journeys' table in the database.
 * The class offers functionalities to retrieve, store, and validate journey records.
 *
 * Table Structure:
 * - `id` int(11) NOT NULL AUTO_INCREMENT: A unique identifier for each journey record.
 * - `vehicleid` varchar(60) NOT NULL: The ID of the vehicle associated with the journey.
 * - `needToBeValidated` tinyint(1) NOT NULL: A flag indicating if the journey record needs validation (1 means it requires validation, 0 means it's validated).
 * - `startDate` datetime NOT NULL: The starting date and time of the journey.
 * - `endDate` datetime NOT NULL: The ending date and time of the journey.
 * - `startAddress` varchar(255) NOT NULL: The address where the journey began.
 * - `endAddress` varchar(255) NOT NULL: The address where the journey ended.
 * - `startLongitude` float NOT NULL: The longitude coordinate of the starting address.
 * - `startLatitude` float NOT NULL: The latitude coordinate of the starting address.
 * - `endLongitude` float NOT NULL: The longitude coordinate of the ending address.
 * - `endLatitude` float NOT NULL: The latitude coordinate of the ending address.
 * - `totalTimeStoppedSeconds` int(11) NOT NULL: The total time stopped until the next journey, in seconds.
 * - PRIMARY KEY (`id`): The primary key for the table is the 'id' column.
 *
 * @author Grégoire Mariette
 * @version 1.1
 */
class JourneysDAO {
    /**
     * The MySQLi database connection object.
     * 
     * @var mysqli
     */
    private mysqli $mysqli;

    /**
     * JourneysDAO constructor.
     *
     * Initializes a new instance of the JourneysDAO class and sets the MySQLi database connection object.
     * 
     * @param mysqli $mysqli The MySQLi database connection object.
     */
    public function __construct(mysqli $mysqli) {
        $this->mysqli = $mysqli;
    }

    /**
     * Retrieves the date of the last journey entry for a given vehicle.
     *
     * @param string $vehicleId The ID of the vehicle for which the last connection date is being fetched.
     * @return string The date of the last journey entry in the format "Y-m-d H:i:s".
     * @throws Exception If there is an error executing the query.
     * @throws InvalidArgumentException If there are no entries in the database for the given vehicle.
     */
    public function getLastConnectionDate(string $vehicleId): string {
        $stmt = $this->mysqli->prepare("SELECT MAX(endDate) FROM journeys WHERE vehicleId = ?");
        if ($stmt === false) {
            var_dump("Prepare failed: " . $this->mysqli->error);
        }
        
        $stmt->bind_param("s", $vehicleId);

        if (!$stmt->execute()) {
            throw new Exception("Failed to retrieve last connection date: " . $stmt->error);
        }

        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        if (!$row || is_null($row['MAX(endDate)'])) {
            throw new InvalidArgumentException("No entry in the database for vehicle: " . $vehicleId);
        }

        return $row['MAX(endDate)'];
    }

    /**
     * Inserts a new journey record into the database for a given vehicle and journey details.
     * If a journey with the same vehicleId and startDate already exists, no insertion will occur.
     *
     * @param string $vehicleId The unique identifier of the vehicle.
     * @param string $startDate The starting date and time of the journey, formatted as "Y-m-d H:i:s".
     * @param string $endDate The ending date and time of the journey, formatted as "Y-m-d H:i:s".
     * @param string $startAddress The starting address of the journey.
     * @param string $endAddress The ending address of the journey.
     * @param float $startLongitude The starting longitude coordinate of the journey.
     * @param float $startLatitude The starting latitude coordinate of the journey.
     * @param float $endLongitude The ending longitude coordinate of the journey.
     * @param float $endLatitude The ending latitude coordinate of the journey.
     * @param int $totalTimeStoppedSeconds The total time the vehicle was stopped during the journey, in seconds.
     * @throws Exception If there is an error inserting the journey record into the database.
     * @return void
     */
    public function storeJourney(string $vehicleId, 
    string $startDate, string $endDate, 
    string $startAddress, string $endAddress, 
    string $startLongitude, string $startLatitude, 
    string $endLongitude, string $endLatitude, int $totalTimeStoppedSeconds): void {
        $needToBeValidated = 1;
        
        if (!$this->journeyAlreadyExists($vehicleId, $startDate)) {
            $stmt = $this->mysqli->prepare("INSERT INTO journeys (vehicleid, needToBeValidated, startDate, endDate, startAddress, endAddress, startLongitude, startLatitude, endLongitude, endLatitude, totalTimeStoppedSeconds) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sissssssssi", $vehicleId, $needToBeValidated, $startDate, $endDate, $startAddress, $endAddress, $startLongitude, $startLatitude, $endLongitude, $endLatitude, $totalTimeStoppedSeconds);
        
            if (!$stmt->execute()) {
                throw new Exception("Failed to insert journey for vehicle " . $vehicleId . ": " . $stmt->error);
            }

            $stmt->close();
        }
         // Verify if the journey was successfully inserted
        if (!$this->journeyAlreadyExists($vehicleId, $startDate)) {
            throw new Exception("Failed to find the inserted journey for vehicle " . $vehicleId);
        }
    }

    /**
     * Checks if a journey record with the same vehicleId and startDate already exists in the database.
     *
     * @param string $vehicleId The unique identifier of the vehicle.
     * @param string $startDate The starting date and time of the journey to check, formatted as "Y-m-d H:i:s".
     * @throws Exception If there's an issue executing the query.
     * @return bool True if a journey record with the given vehicleId and startDate exists, otherwise false.
     */
    private function journeyAlreadyExists(string $vehicleId, string $startDate): bool {
        $stmt = $this->mysqli->prepare("SELECT id FROM journeys WHERE vehicleId = ? AND startDate = ?");
        $stmt->bind_param("ss", $vehicleId, $startDate);
        $stmt->execute();
        $stmt->store_result();
        return ($stmt->num_rows > 0);
    }
    
    /**
     * Retrieves all journeys for a specific vehicle that are pending validation.
     *
     * @param string $vehicleId The unique identifier of the vehicle for which journeys are being fetched.
     * @throws Exception If there's an error while fetching the journeys from the database.
     * @return array An associative array of journeys that need validation, with each journey having fields from the journeys table.
     */
    public function getJourneysToBeValidated(string $vehicleId): array {
        $stmt = $this->mysqli->prepare("SELECT * FROM journeys WHERE vehicleId = ? AND needToBeValidated = 1");
        $stmt->bind_param("s", $vehicleId);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to retrieve journeys to validate for vehicle " . $vehicleId . ": " . $stmt->error);
        }
    
        $result = $stmt->get_result();
        $journeys = $result->fetch_all(MYSQLI_ASSOC);
    
        $stmt->close();

        if (count($journeys) === 0) {
            throw new InvalidArgumentException("No journeys to be validated for vehicle: " . $vehicleId);
        }

        return $journeys;
    }
    
    /**
     * Marks a particular journey of a specific vehicle as validated in the database.
     * Specifically, it sets the `needToBeValidated` field for the journey to 0.
     *
     * @param string $vehicleId The unique identifier of the vehicle.
     * @param string $startDate The starting date and time of the journey to be marked as validated, formatted as "Y-m-d H:i:s".
     * @throws Exception If there's an error updating the journey in the database.
     * @return void
     */
    public function validateJourney(string $vehicleId, string $startDate): void {
        $stmt = $this->mysqli->prepare("UPDATE journeys SET needToBeValidated = 0 WHERE vehicleId = ? AND startDate = ?");
        $stmt->bind_param("ss", $vehicleId, $startDate);
    
        if (!$stmt->execute()) {
            throw new Exception("Failed to set journey as validated for vehicle " . $vehicleId . " on date " . $startDate . ": " . $stmt->error);
        }
    
        $stmt->close();
    }
    
    /**
     * Recalculates the stop time of the previous last journey for a given vehicle.
     * 
     * This method fetches the most recent journey for the specified vehicle ID where 
     * the totalTimeStoppedSeconds is zero. It then calculates the time difference 
     * between the end of this journey and the start of the new journey (from the 
     * provided $firstJourneys array). The result (in seconds) is then updated 
     * in the totalTimeStoppedSeconds field of the previous journey.
     *
     * @param string $vehicleId The ID of the vehicle.
     * @param array $firstJourneys An associative array containing the details of 
     *                             the new journey, especially the 'startDate'.
     * 
     * @throws InvalidArgumentException If no matching journey is found for the given 
     *                                  vehicle ID.
     * @throws Exception If any database operations fail.
     * 
     * @return void
     */
    public function recalculateStopTimeOfThePreviousLastJourney(string $vehicleId, array $firstJourneys): void {
        // 1. Retrieve the last journey for the given vehicle ID where totalTimeStoppedSeconds is 0
        $query = "SELECT * FROM journeys WHERE vehicleId = ? AND totalTimeStoppedSeconds = 0 ORDER BY endDate DESC LIMIT 1";
        $stmt = $this->mysqli->prepare($query);
        if (!$stmt) {
            throw new Exception('Prepare failed: ' . $this->mysqli->error);
        }
        
        $stmt->bind_param("s", $vehicleId);
        
        if (!$stmt->execute()) {
            throw new Exception('Execute failed: ' . $stmt->error);
        }
    
        $result = $stmt->get_result();
        $lastJourney = $result->fetch_assoc();
    
        if (!$lastJourney) {
            throw new InvalidArgumentException('No matching journey found for the provided vehicle ID.');
        }
        
        $stmt->close();
        // 2. Calculate the time difference in seconds
        $endDateOfLastJourney = new DateTime($lastJourney['endDate']);
        $startDateOfFirstJourney = new DateTime($firstJourneys['startDate']);
        
        $timeDifference = $endDateOfLastJourney->diff($startDateOfFirstJourney);
        $timeDifferenceInSeconds = $timeDifference->days * 24 * 60 * 60 + $timeDifference->h * 60 * 60 + $timeDifference->i * 60 + $timeDifference->s;
    
        // 3. Update the totalTimeStoppedSeconds field of the last journey
        $updateQuery = "UPDATE journeys SET totalTimeStoppedSeconds = ? WHERE id = ?";
        $stmt = $this->mysqli->prepare($updateQuery);
    
        if (!$stmt) {
            throw new Exception('Prepare failed for update: ' . $this->mysqli->error);
        }
    
        $stmt->bind_param("ii", $timeDifferenceInSeconds, $lastJourney['id']);
    
        if (!$stmt->execute()) {
            throw new Exception('Execute failed for update: ' . $stmt->error);
        }
        $stmt->close();
    }

    public function getAllJourneyForOneVehicle(string $vehicleId): array {
        $stmt = $this->mysqli->prepare("SELECT * FROM journeys WHERE vehicleId = ?");
        $stmt->bind_param("s", $vehicleId);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to retrieve journeys to validate for vehicle " . $vehicleId . ": " . $stmt->error);
        }
    
        $result = $stmt->get_result();
        $journeys = $result->fetch_all(MYSQLI_ASSOC);
    
        $stmt->close();

        if (count($journeys) === 0) {
            throw new InvalidArgumentException("No journeys to be validated for vehicle: " . $vehicleId);
        }

        return $journeys;
    }

}
?>