<?php 
require_once ($_SERVER['DOCUMENT_ROOT'] . '/prestanaute/config.php');
require_once(APP_ROOT . '/Models/classes/Logger.class.php');
require_once(APP_ROOT . '/Models/dao/ConnectionToDataBase.php');
require_once(APP_ROOT . '/Models/dao/JourneysDAO.php');
require_once(APP_ROOT . '/Models/dao/WorksiteDAO.php');
require_once(APP_ROOT . '/Models/dao/ValidatedDAO.php');

class ValidationController {
    private Logger $logger;
    private ConnectionToDataBase $connectionToDb;
    private JourneysDAO $journeysDAO;
    private WorksiteDAO $worksiteDAO;
    private ValidatedDAO $validatedDAO;

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
            $this->journeysDAO = new JourneysDAO($this->connectionToDb->getConnection());
            $this->validatedDAO = new ValidatedDAO($this->connectionToDb->getConnection());
        } catch (Exception $e) {
            $this->logger->logError("Error connecting to database", $e->getMessage());
            throw new Exception("Fatal error, unable to connect to database. Please try again later.");
        }
    }

    public function getWorksiteId(string $worksiteString): string {
        try {
            $worksiteId = $this->worksiteDAO->getWorksiteId($worksiteString);
        } catch (InvalidArgumentException $e) {
            throw new InvalidArgumentException("worksiteId does not exist in table.");
        } catch (Exception $e) {
            $this->logger->logError("Error retrieving worksite ID", $e->getMessage());
            throw new Exception($e->getMessage());
        }

        return $worksiteId;
    }

    public function validateJourney(string $vehicleId, string $startDate): bool {
        try {
            $this->journeysDAO->validateJourney($vehicleId, $startDate);
            return true;
        } catch (Exception $e) {
            $this->logger->logError("Error validating journey", $e->getMessage());
            throw new Exception($e->getMessage());
        }
    }

    public function entryExists($accessId, $startDate): bool {
        try {
            return $this->validatedDAO->entryExists($accessId, $startDate);
        } catch (Exception $e) {
            $this->logger->logError("Error checking if entry exists", $e->getMessage());
            throw new Exception($e->getMessage());
        }
    }

    public function insertEntry($worksiteId, $accessId, $operationType, $startDate, $endDate, $comment, $totalTimeStoppedSeconds): bool {
        try {
            $this->validatedDAO->insertEntry($worksiteId, $accessId, $operationType, $startDate, $endDate, $comment, $totalTimeStoppedSeconds);
            return true;
        } catch (Exception $e) {
            $this->logger->logError("Error inserting entry", $e->getMessage());
            throw new Exception($e->getMessage());
        }
    }
}
?>