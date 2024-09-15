<?php
require_once ($_SERVER['DOCUMENT_ROOT'] . '/prestanaute/config.php');
require_once(APP_ROOT . '/Models/classes/Logger.class.php');
require_once(APP_ROOT . '/Models/classes/RetrieveDataFromAPI.class.php');
require_once(APP_ROOT . '/Models/classes/DataCleaning.class.php');
require_once(APP_ROOT . '/Models/dao/ConnectionToDataBase.php');
require_once(APP_ROOT . '/Models/dao/JourneysDAO.php');

/**
 * JourneyController Class
 *
 * This class provides methods to manage vehicle journeys. It is responsible for
 * connecting to the database, retrieving journey data from an API, cleaning and storing
 * the data, and handling journey validations.
 * 
 * @author Grégoire Mariette
 * @version 1.1
 */
class JourneyController {

    /**
     * @var string $vehicleId The unique identifier of the vehicle.
     */
    private string $vehicleId;

    /**
     * @var string $startDate The start date of the journey in "Y-m-d H:i:s" format.
     */
    private string $startDate;

    /**
     * @var string $endDate The end date of the journey in "Y-m-d H:i:s" format.
     */
    private string $endDate;

    /**
     * @var array $journeys Array of journey data retrieved from the API.
     */
    private array $journeys;

    private array $lastJourney;

    /**
     * @var Logger $logger Instance of the Logger class for logging errors and important information.
     */
    private Logger $logger;

    /**
     * @var ConnectionToDataBase $connectionToDb The database connection instance.
     */
    private ConnectionToDataBase $connectionToDb;

    /**
     * @var JourneysDAO $journeysDAO Data Access Object for the journeys.
     */
    private JourneysDAO $journeysDAO;

    /**
     * JourneyController constructor.
     *
     * Initializes properties and sets up Logger and ConnectionToDataBase instances.
     *
     * @param string $vehicleId The unique identifier of the vehicle.
     */
    public function __construct(string $vehicleId) {
        $this->vehicleId = $vehicleId;
        $this->logger = new Logger();
        $this->connectionToDb = new ConnectionToDataBase();
    }
    
    /**
     * Establishes a connection to the database.
     *
     * @throws Exception If there's an issue connecting to the database.
     */
    public function connect() {
        try {
            $this->connectionToDb->connect();
            $this->journeysDAO = new JourneysDAO($this->connectionToDb->getConnection());
        } catch (Exception $e) {
            $this->logger->logError("Error connecting to database", $e->getMessage());
            throw new Exception("Fatal error, unable to connect to database. Please try again later.");
        }
    }

    /**
     * Sets the start date for the journey based on the last connection date.
     * Defaults to 3 days ago if no entry is found in the database for the vehicle.
     *
     * @throws Exception If there's an error retrieving the last connection date.
     */
    public function setStartDate(): void {
        try {
            $this->startDate = $this->journeysDAO->getLastConnectionDate($this->vehicleId);
        } catch (InvalidArgumentException $e) {  // No entry in the database for the given vehicle
            $dateTime = new DateTime('-3 days', new DateTimeZone('UTC'));
            $this->startDate = $dateTime->setTimezone(new DateTimeZone('Europe/Brussels'))->format("Y-m-d H:i:s");
        } catch (Exception $e) {
            $this->logger->logError("Error retrieving last connection date from database", $e->getMessage());
            throw new Exception("Failed to retrieve last connection date from database. Please try again later.");
        }
    }

    /**
     * Sets the end date for the journey to the current date and time.
     */
    public function setEndDate(): void {
        $dateTime = new DateTime('now', new DateTimeZone('UTC'));
        $this->endDate = ($dateTime->setTimezone(new DateTimeZone('Europe/Brussels'))->format("Y-m-d H:i:s"));
    }

    public function handleNewData() {
        try {
            $this->retrieveNewDataFromApi();
            if($this->isBiggerThanOne()) {
                $this->relevantInformations();
                $this->recalculateTotalTimeStoppedSeconds();
                $this->removeStops();
                if($this->isBiggerThanOne()) {
                    $this->removeLastJourney();
                    $this->storeNewData();
                }
            }
        } catch (Exception $e) {
            throw new Exception("Failed to handle new data. Please try again later.");
        }
    }

    public function show($journey) : void {
        echo "<pre>";
        var_dump($journey);
        echo "</pre>";
        echo "-------------------------------------------------------------------------------";
    }

    /**
     * Retrieves new journey data from an external API.
     *
     * @return bool True if data is successfully retrieved, false otherwise.
     * @throws Exception If there's an error during the data retrieval process.
     */
    public function retrieveNewDataFromApi(): void {
        $vehicleId = $this->vehicleId;
        $startDate = $this->convertDateToApiFormat($this->startDate);
        $endDate = $this->convertDateToApiFormat($this->endDate);
        //$startDate = "2023-09-29T15:30:59";
        //$endDate = "2023-09-30T15:30:59";

        try {
            $this->journeys = retrieveDataFromAPI::vehicleJourneys($startDate, $endDate, $vehicleId);
        } catch (Exception $e) {
            $this->logger->logError("Error retrieving data from API", $e->getMessage());
            throw new Exception("Failed to retrieve data from API. Please try again later.");
        }
    }

    public function isBiggerThanOne(): bool {
        return (count($this->journeys) > 1);
    }

    /**
     * Converts a date string from "Y-m-d H:i:s" format to "Y-m-dTH:i:s" format and in UTC timezone.
     *
     * @param string $date The date to be converted.
     * @return string The converted date string.
     */
    private function convertDateToApiFormat(string $date): string {
        $dateTime = new DateTime($date, new DateTimeZone('Europe/Brussels'));
        return $dateTime->setTimezone(new DateTimeZone('UTC'))->format("Y-m-d\TH:i:s");
    }
    
    /**
     * Cleans the journey data by retaining only the relevant information.
     *
     * @throws Exception If there's an error during the data cleaning process.
     */
    public function relevantInformations(): void {
        try {
            $this->journeys = DataCleaning::relevantInformations($this->journeys);
        } catch (Exception $e) {
            $this->logger->logError("Error keeping data", $e->getMessage());
            throw new Exception("Failed to clean data. Please try again later.");
        }
    }

    /**
     * Recalculates and updates the total time stopped for each journey.
     *
     * @throws Exception If there's an error during the recalculation process.
     */
    public function recalculateTotalTimeStoppedSeconds(): void {
        try {
            $this->journeys = DataCleaning::recalculateTotalTimeStoppedSeconds($this->journeys);
        } catch (Exception $e) {
            $this->logger->logError("Error recalculating total time stopped", $e->getMessage());
            throw new Exception("Failed to clean data. Please try again later.");
        }
    }

    /**
     * Removes stop segments from the journey data.
     *
     * @throws Exception If there's an error during the removal process.
     */
    public function removeStops(): void {
        try {
            $this->journeys = DataCleaning::removeStops($this->journeys);
        } catch (Exception $e) {
            $this->logger->logError("Error removing stops", $e->getMessage());
            throw new Exception("Failed to clean data. Please try again later.");
        }
    }

    public function removeLastJourney(): void {
        try {
            $this->lastJourney = DataCleaning::removeLastJourney($this->journeys);
        } catch (Exception $e) {
            $this->logger->logError("Error removing last journey", $e->getMessage());
            throw new Exception("Failed to clean data. Please try again later.");
        }
    }

    /**
     * Stores the cleaned and processed journey data into the database.
     */
    public function storeNewData(): void {
        foreach($this->journeys as $journey) {
            try {
                $this->journeysDAO->storeJourney(
                    $this->vehicleId,
                    $journey['startDate'],
                    $journey['endDate'],
                    $journey['startAddress'],
                    $journey['endAddress'],
                    $journey['startLongitude'],
                    $journey['startLatitude'],
                    $journey['endLongitude'],
                    $journey['endLatitude'],
                    $journey['totalTimeStoppedSeconds']
                );
            } catch (Exception $e) {
                $this->logger->logError("Error storing journey", $e->getMessage());
                continue;
            }
        }
    }

    /**
     * Retrieves a list of journeys for the vehicle that require validation.
     *
     * @return array An array of journeys that need validation.
     * @throws Exception If there's an error during the retrieval process.
     */
    public function getJourneysToBeValidated(): array {
        try {
            $this->journeys = $this->journeysDAO->getJourneysToBeValidated($this->vehicleId);
            return $this->journeys;
        } catch (InvalidArgumentException $e) {
            return [0 => "All journeys have been validated"];
        } catch (Exception $e) {
            $this->logger->logError("Error retrieving journeys to be validated", $e->getMessage());
            throw new Exception("Failed to retrieve journeys to be validated. Please try again later.");
        }
    }

    /**
     * Validates a specific journey based on the given vehicle ID and start date.
     *
     * @param string $vehicleId The unique identifier of the vehicle.
     * @param string $startdate The starting date of the journey to be validated.
     * @return bool True if the journey is successfully validated, false otherwise.
     * @throws Exception If there's an error during the validation process.
     */
    public function validateJourney(string $vehicleId, string $startdate): bool {
        try {
            $this->journeysDAO->validateJourney($vehicleId, $startdate);
            return true;
        } catch (Exception $e) {
            $this->logger->logError("Error validating journey", $e->getMessage());
            throw new Exception("Failed to validate journey. Please try again later.");
        }
    }

    public function getjourneys(): array {
        return $this->journeys;
    } 

    public static function displayDayMonthYear(string $date): string {
        $dateTime = new DateTime($date);
        $daysFrench = [
            1 => 'Lundi', 2 => 'Mardi', 3 => 'Mercredi', 4 => 'Jeudi', 5 => 'Vendredi', 6 => 'Samedi', 7 => 'Dimanche'
        ];
        $monthsFrench = [
            1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril', 5 => 'Mai', 6 => 'Juin',
            7 => 'Juillet', 8 => 'Août', 9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'
        ];
    
        $formattedDate = $daysFrench[(int)$dateTime->format('N')] . ' ' . $dateTime->format('d') . ' ' . $monthsFrench[(int)$dateTime->format('n')] . ' ' . $dateTime->format('Y');
    
        return $formattedDate;
    }
    
    public static function displayTime(string $date): string {
        $dateTime = new DateTime($date);
        return $dateTime->format('H:i:s');
    }
    
    public static function displayDuration(int $duration): string {
        $hours = floor($duration / 3600);
        $minutes = floor(($duration / 60) % 60);
        $seconds = $duration % 60;
    
        $formattedDuration = $hours . 'h ' . $minutes . 'm ' . $seconds . 's';
    
        return $formattedDuration;
    }
}
