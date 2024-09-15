<?php
// Include necessary files
require_once ($_SERVER['DOCUMENT_ROOT'] . '/prestanaute/config.php');
require_once(APP_ROOT . '/Controllers/WorksiteController.php');
require_once(APP_ROOT . '/Models/classes/Logger.class.php');

function sanitizeString($input) {
    return htmlspecialchars(filter_var($input, FILTER_SANITIZE_STRING), ENT_QUOTES, 'UTF-8');
}

$logger = new Logger();

// Check if the POST request is set and has 'query' parameter
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['query'])) {

    // Sanitize the input using the function
    $searchTerm = sanitizeString($_POST['query']);

    try {
        // Initialize the WorksiteController
        $worksiteController = new WorksiteController();
        $worksiteController->connect();

        // Search for the worksite based on the sanitized query
        $results = $worksiteController->searchWorksiteByNameOrId($searchTerm);

        // Return the results in JSON format
        echo json_encode($results);
        
    } catch (Exception $e) {
        // Log the error or handle it as needed
        $logger->logError("Error retrieving worksite", $e->getMessage());
        echo json_encode(['error' => 'An error occurred while processing the request.']);
    }
} else {
    $logger->logError("Invalid request", "The request method is not POST or the query parameter is not set.");
    echo json_encode(['error' => 'Invalid request.']);
}
?>
