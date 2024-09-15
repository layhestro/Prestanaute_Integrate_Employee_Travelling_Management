<?php 
require_once ($_SERVER['DOCUMENT_ROOT'] . '/prestanaute/config.php');
require_once(APP_ROOT . '/Models/classes/Logger.class.php');
require_once(APP_ROOT . '/Models/dao/ConnectionToDataBase.php');
require_once(APP_ROOT . '/Models/dao/WorksiteDAO.php');

/**
 * Class WorksiteController
 * 
 * Controller responsible for managing operations related to the worksite. 
 * It acts as an intermediary between the frontend and the data access layer (WorksiteDAO).
 * 
 * @author Grégoire Mariette
 * @version 1.1
 */
class WorksiteController {
    
    /** 
     * @var Logger Logger instance for logging errors and messages. 
     */
    private Logger $logger;
    
    /** 
     * @var ConnectionToDataBase Database connection instance. 
     */
    private ConnectionToDataBase $connectionToDb;
    
    /** 
     * @var WorksiteDAO DAO instance for worksite-related operations. 
     */
    private WorksiteDAO $worksiteDAO;

    /**
     * WorksiteController constructor.
     * 
     * Initializes the logger and the database connection.
     */
    public function __construct() {
        $this->logger = new Logger();
        $this->connectionToDb = new ConnectionToDataBase();
    }

    /**
     * Establishes a connection to the database.
     *
     * @throws Exception If there's an issue connecting to the database.
     */
    public function connect(): void {
        try {
            $this->connectionToDb->connect();
            $this->worksiteDAO = new WorksiteDAO($this->connectionToDb->getConnection());
        } catch (Exception $e) {
            $this->logger->logError("Error connecting to database", $e->getMessage());
            throw new Exception("Fatal error, unable to connect to database. Please try again later.");
        }
    }

    /**
     * Searches for a worksite by its name or ID.
     * 
     * @param string $searchTerm The term to search for.
     * @return array An array of search results.
     */
    public function searchWorksiteByNameOrId(string $searchTerm): array {
        try {
            $searchResults = $this->worksiteDAO->searchWorksiteByNameOrId($searchTerm);
        } catch (Exception $e) {
            $this->logger->logError("Error searching for worksites", $e->getMessage());
        }
        return $searchResults;
    }

    /**
     * Retrieves the worksite ID based on the input string.
     * 
     * @param string $worksiteString String in the format "worksiteId | worksiteName".
     * @return string The retrieved worksite ID.
     * @throws Exception If there's an issue retrieving the worksite ID.
     */
    public function getWorksiteId(string $worksiteString): string {
        try {
            $worksiteId = $this->worksiteDAO->getWorksiteId($worksiteString);
        } catch (InvalidArgumentException $e) {
            return "Invalid worksite id";
        } catch (Exception $e) {
            $this->logger->logError("Error getting worksite ID", $e->getMessage());
            throw new Exception("Error getting worksite ID");
        }
        return $worksiteId;
    }
}
?>
