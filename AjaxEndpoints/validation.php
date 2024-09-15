<?php
require_once ($_SERVER['DOCUMENT_ROOT'] . '/prestanaute/config.php');
require_once(APP_ROOT . '/Controllers/ValidationController.php');

function sanitizeString($input) {
    return htmlspecialchars(filter_var($input, FILTER_SANITIZE_STRING), ENT_QUOTES, 'UTF-8');
}

// Initialize the ValidationController & Logger
$validationController = new ValidationController();

// Connect to the database
try {
    $validationController->connect();
} catch (Exception $e) {
    echo json_encode(['error' => 'Une erreur est survenue lors de la connexion à la base de données. ressayer plus tard.']);
    exit();
}

//sanitize the input using the function
$vehicleId = sanitizeString($_POST['vehicleId']);
$accessId = filter_input(INPUT_POST, 'accessId', FILTER_VALIDATE_INT);
$operationType = filter_input(INPUT_POST, 'operation', FILTER_VALIDATE_INT);
$startDate = sanitizeString($_POST['startDate']);
$endDate = sanitizeString($_POST['endDate']);
$totalTimeStoppedSeconds = filter_input(INPUT_POST, 'totalTimeStoppedSeconds', FILTER_VALIDATE_INT);

if (empty($vehicleId) || empty($accessId) || empty($operationType) || empty($startDate) || empty($endDate) || empty($totalTimeStoppedSeconds)) {
    echo json_encode(['error' => 'Des données requises sont manquantes. Ressayer plus tard.']);
    exit();
}

$worksiteString = isset($_POST['worksite']) ? sanitizeString($_POST['worksite']) : null;

if (strlen($_POST['comment']) > 255) {
    echo json_encode(['error' => 'Le commentaire ne peut pas dépasser 255 caractères.']);
    exit();
}
$comment = isset($_POST['comment']) ? sanitizeString($_POST['comment']) : null;

// if operation type is 4 or 16, then we don't need to check for worksite
if ($operationType == 4 || $operationType == 16 || $operationType == 30) {
    try {   
        $worksiteString = "";

        if($validationController->entryExists($accessId, $startDate)) {
            echo json_encode(['error' => 'Ce trajet a déjà été validé. Veuillez contactez Arnaud']);
        }
        else {
            if ($validationController->insertEntry($worksiteString, $accessId, $operationType, $startDate, $endDate, $comment, $totalTimeStoppedSeconds)) {
                if($validationController->validateJourney($vehicleId, $startDate)) {
                    echo json_encode(['success' => 'trajet validé avec succès!']);
                }
            }
        }
    } catch (Exception $e) {
        echo json_encode(['error' => 'Une erreur est survenue lors de la validation du trajet. Ressayer plus tard.']);
    }
}
// if operation type is not 4 or 16, then we need to check for worksite
else {
    if (!empty($worksiteString)) {
        // Validate the worksite ID
        try {
            $validWorksiteId = $validationController->getWorksiteId($worksiteString);
        } catch (InvalidArgumentException $e) {
            echo json_encode(['error' => "le numéro de chantier n'est pas valide."]);
            exit();
        } catch (Exception $e) {
            echo json_encode(['error' => 'Une erreur est survenue lors de la connexion à la base de données. Ressayer plus tard.']);
            exit();
        }
        
        try {
            if($validationController->entryExists($accessId, $startDate)) {
                echo json_encode(['error' => 'Ce trajet a déjà été validé. Veuillez contactez Arnaud']);
            }
            else {
                if ($validationController->insertEntry($validWorksiteId, $accessId, $operationType, $startDate, $endDate, $comment, $totalTimeStoppedSeconds)) {
                    if($validationController->validateJourney($vehicleId, $startDate)) {
                        echo json_encode(['success' => 'trajet validé avec succès!']);
                    }
                }
            }
        } catch (Exception $e) {
            echo json_encode(['error' => 'Une erreur est survenue lors de la validation du trajet. Ressayer plus tard.']);
        }
    } 
    else {
        echo json_encode(['error' => 'Pour ce type d\'opération, le chantier doit être spécifié.']);
    }
}

?>