<?php 
/**
* Class WorksiteDAO
 * 
 * A Data Access Object (DAO) that encapsulates the database operations for the 'worksite' table.
 * This class allows for querying worksites by their ID or name, as well as verifying the existence
 * of a worksite in the database. It abstracts the underlying database queries and returns data
 * in a structured format, ensuring a clear separation between the database layer and the business logic.
 * 
 * Table Structure:
 * `worksiteId` varchar(64) DEFAULT NULL,
 * `worksiteName` varchar(256) DEFAULT NULL.
 * 
 * Usage:
 * The class should be instantiated with an active mysqli connection and can then be used to
 * perform various operations related to the 'worksite' table. All methods throw exceptions
 * in case of errors, allowing the calling code to handle and report errors as needed.
 * 
 * Example:
 * $worksiteDAO = new WorksiteDAO($mysqli);
 * $results = $worksiteDAO->searchWorksiteByNameOrId("exampleTerm");
 * 
 * @author Grégoire Mariette
 * @version 1.1
 */
class WorksiteDAO {
    /**
     * @var mysqli An instance of the mysqli class for database interactions.
     */
    private mysqli $mysqli;

    /**
     * WorksiteDAO constructor.
     * 
     * @param mysqli $mysqli An instance of the mysqli class.
     */
    public function __construct(mysqli $mysqli) {
        $this->mysqli = $mysqli;
    }
    
    /**
     * Searches for worksites by name or ID in the database.
     * 
     * Executes a SQL query to search for worksites that match the provided search term in either
     * the 'worksiteName' or 'worksiteId' columns. The results are sorted by 'worksiteId' in descending order.
     * 
     * @param string $searchTerm The term to search for in the database.
     * @return array An array of worksites matching the search term in the format "worksiteId | worksiteName".
     *               Returns an array with a single element "Pas de correspondance" if no matches are found.
     * @throws Exception If there's an error in preparing or executing the SQL statement.
     */
    public function searchWorksiteByNameOrId(string $searchTerm): array {
        // Prepare the SQL statement with placeholders
        $query = "SELECT worksiteName, worksiteId FROM worksite WHERE worksiteName 
        LIKE ? OR worksiteId LIKE ? ORDER BY worksiteId DESC";
        
        $stmt = $this->mysqli->prepare($query);
        if (!$stmt) {
            throw new Exception('Statement preparation error: ' . $this->mysqli->error);
        }

        // Bind the parameters to the placeholders
        $param = "%" . $searchTerm . "%";
        $stmt->bind_param('ss', $param, $param);

        // Execute the statement
        if (!$stmt->execute()) {
            throw new Exception('Statement execution error: ' . $stmt->error);
        }

        $result = $stmt->get_result();
        
        if($result->num_rows == 0) {
            return array("Pas de correspondance");
        }
        
        $searchResults = array();
        while($row = $result->fetch_assoc()) {
            $searchResults[] = $row["worksiteId"] . " | " . $row["worksiteName"];
        }
        
        // Close the prepared statement
        $stmt->close();

        return $searchResults;
    }

    /**
     * Retrieves and verifies a worksite ID from a given string.
     * 
     * Extracts the 'worksiteId' from the provided string and checks if it exists in the database.
     * The provided string is expected to be in the format "worksiteId | worksiteName".
     * 
     * @param string $worksiteString String in the format "worksiteId | worksiteName".
     * @return string The verified worksite ID.
     * @throws Exception If the worksite ID does not exist in the database or there's an error in the SQL execution.
     */
    public function getWorksiteId(string $worksiteString): string {
        // Extract the worksite ID from the string
        $parts = explode(" | ", $worksiteString);

        // Check if the resulting array has exactly 2 parts
        if (count($parts) !== 2) {
            throw new InvalidArgumentException('The provided worksite string format is incorrect.');
        }

        list($worksiteId, $worksiteName) = $parts;

        // Prepare the SQL statement to check if the worksite ID exists
        $query = "SELECT worksiteId FROM worksite WHERE worksiteId = ?";
        
        $stmt = $this->mysqli->prepare($query);
        if (!$stmt) {
            throw new Exception('Statement preparation error: ' . $this->mysqli->error);
        }

        // Bind the parameter to the placeholder
        $stmt->bind_param('s', $worksiteId);

        // Execute the statement
        if (!$stmt->execute()) {
            throw new Exception('Statement execution error: ' . $stmt->error);
        }

        $result = $stmt->get_result();
        
        if($result->num_rows == 0) {
            throw new InvalidArgumentException('Worksite ID does not exist in the database.');
        }

        // Close the prepared statement
        $stmt->close();

        return $worksiteId;
    }
}
?>