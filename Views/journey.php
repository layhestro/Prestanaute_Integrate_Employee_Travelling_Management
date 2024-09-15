<?php
require_once ($_SERVER['DOCUMENT_ROOT'] . '/prestanaute/config.php');
require_once (APP_ROOT . '/Controllers/JourneysController.php');
session_start();
// Check if the user is not logged in, and if so, redirect to the login page
if (!isset($_SESSION['vehicleId'])) {
    header('Location: login.php');
    exit;
}

$journeysController = new JourneyController($_SESSION['vehicleId']);
try {
    $journeysController->connect();
    $journeysController->setStartDate();
    $journeysController->setEndDate();
    $journeysController->handleNewData();
    $journeys = $journeysController->getJourneysToBeValidated();
} catch (Exception $e) {
    echo $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Journey Page</title>
    <!-- Add any styles or scripts you want -->
    <link rel="stylesheet" href="/prestanaute/assets/css/styles.css">
    <link rel="stylesheet" href="//code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
    <link rel="icon" href="/prestanaute/assets/img/tabicon.ico" type="image/x-icon">
    
    <script src="https://code.jquery.com/jquery-3.6.0.js"></script>
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.js"></script>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inconsolata:wght@500&family=Josefin+Sans:wght@300&family=Open+Sans&display=swap" rel="stylesheet">
</head>

<body data-number-of-journeys="<?php echo count($journeys); ?>" class="journey-page">
    <div class="loader-wrapper">
        <div class="loader">PRESTANAUTE</div>
    </div>

    <header class="user-info" id="detailsHeader">
        <div class="greeting">Bienvenue, <?php echo $_SESSION['username']; ?></div>
        <div class="notification">
            <div id="j-Count" class="notification-item">
                <?php echo count($journeys); ?>
            </div> 
            <div>
                trajet(s) à valider
            </div>
        </div>
    </header>


    <main>
        <section class="user-details" id="detailsContent">
            <div class="details-block">
                <h2>Détails</h2>
                <div class="detail-item">
                    <span class="detail-label">Id Masternaute:</span> 
                    <span><?php echo $_SESSION['vehicleId']; ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Id Access:</span> 
                    <span><?php echo $_SESSION['accessId']; ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Plaque d'immatriculation:</span> 
                    <span><?php echo $_SESSION['vehicleRegistration']; ?></span>
                </div>
            </div>
        </section>

        <section class="journey-deck">            
            <?php 
            if (isset($journeys) && is_array($journeys) && count($journeys) > 0) {
                if ($journeys[0] !== "All journeys have been validated") {
                    foreach($journeys as $i=>$journey) {
            ?>
            <article class="journey-card" id="card-<?php echo $i ?>">
                <div class="journey-header">
                <?php echo ($i+1) . ". " . $journeysController::displayDayMonthYear($journey['startDate']) ?>
                </div>

                <div class="journey-info-grid">
                    <div class="info-item start-address">
                        <p><?php echo $journey['startAddress']; ?></p>
                    </div>
                    <div class="info-item end-address">
                        <p><?php echo $journey['endAddress']; ?></p>
                    </div>
                    <div class="info-item text">
                        <p>Durée de l'intervention</p>
                    </div>
                    <div class="info-item start-time">
                        <p><?php echo $journeysController::displayTime($journey['startDate']); ?></p>
                    </div>
                    <div class="info-item end-time">
                        <p><?php echo $journeysController::displayTime($journey['endDate']); ?></p>
                    </div>
                    <div class="info-item duration">
                        <p><?php echo $journeysController::displayDuration($journey['totalTimeStoppedSeconds']); ?></p>
                    </div>
                </div>
                
                <div class="journey-map" id="card-map-<?php echo $i ?>"
                    data-long-start="<?php echo $journey["startLongitude"] ?>"
                    data-long-end="<?php echo $journey["endLongitude"] ?>"
                    data-lat-start="<?php echo $journey["startLatitude"] ?>"
                    data-lat-end="<?php echo $journey["endLatitude"] ?>">
                </div>
                
                <div class="journey-validation-error"></div>

                <form class="journey-validation-form" data-journey-id="<?php echo $journey['id']; ?>">
                    <div class="form-group ui-widget">
                        <input type="text" class="client-name" placeholder="Nom du client" name="worksite">
                    </div>
                    
                    <div class="form-group">
                        <select id="activity" name="operation">
                            <option value="18" selected >EN - Entretien</option>
                            <option value="4">PA - Pause</option>
                            <option value="15">CH - Chantier</option>
                            <option value="16">LO - Logistique</option>
                            <option value="19">DP - Dépannage</option>
                            <option value="30">TP - trajet privé</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <textarea id="comment" name="comment" rows="3" cols="50" placeholder="Commentaires" maxlength="255"></textarea>
                    </div>
                    
                    <input type="hidden" name="vehicleId" value="<?php echo $_SESSION['vehicleId'] ?>">
                    <input type="hidden" name="accessId" value="<?php echo $_SESSION['accessId'] ?>">
                    <input type="hidden" name="startDate" value="<?php echo $journey['startDate']; ?>">
                    <input type="hidden" name="endDate" value="<?php echo $journey['endDate']; ?>">
                    <input type="hidden" name="totalTimeStoppedSeconds" value="<?php echo $journey['totalTimeStoppedSeconds']; ?>">
                
                    
                    <input type="submit" value="Valider le trajet" class="validate-button" disabled="true">
                </form>
            </article>
            <?php 
                    }
                }
            } else { ?>
                <div class="empty-state">
                    <div class="empty-icon">&#x1F5DE;</div> <!-- This is a unicode paper icon, you can replace it with an SVG or PNG later -->
                    <p class="empty-message">Aucun trajet à valider.</p>
                    <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="refresh-link">Rafraichir</a>
                </div>
            <?php }
            ?>        
        </section>
    </main>

    <footer class="journey-footer">
        <img src="../assets/img/icons/BD-logo-white.png" alt="" srcset="" class="journey-footer-img">  
        <p>2023 Prestanaute</p> 
    </footer>

    <script type="text/javascript">
        $(window).on("load", function() {
            $(".loader-wrapper").fadeOut(4000);
            initializeMaps();
        });
        
        $(document).ready(function() {
            initializeAutocompletes();
            bindValidationButtonEvent();
            bindOperationInputChangeEvent();

            $('#detailsHeader').on('click', function() {
                // Toggle the display of the detailsContent
                $('#detailsContent').toggle();
                // Check if the detailsContent is visible and set display to flex if it is
                if ($('#detailsContent').is(':visible')) {
                    $('#detailsContent').css('display', 'flex');
                }
            });
        });

        function bindOperationInputChangeEvent() {
            $('select[name="operation"], input[name="worksite"]').on('change keyup', updateValidationButtonState);
        }

        function updateValidationButtonState() {
            var selectedValue = $(this).closest('form').find('select[name="operation"]').val();
            var clientNameLength = $(this).closest('form').find('input[name="worksite"]').val().length;

            var buttonState = (selectedValue == "4" || selectedValue == "16" || selectedValue == "30") || 
                (selectedValue != "4" && selectedValue != "16" && selectedValue != "30" && clientNameLength >= 3);

            $(this).closest('form').find('.validate-button').prop('disabled', !buttonState);
        }

        function bindValidationButtonEvent() {
            $('.validate-button').on('click', handleValidationButtonClick);
        }

        function handleValidationButtonClick(event) {
            event.preventDefault();

            var form = $(this).closest('form');
            var card = $(this).closest('.journey-card');
            console.log(form.serialize());
            
            $.ajax({
                url: '/prestanaute/AjaxEndpoints/validation.php',
                type: 'POST',
                data: form.serialize(),
                dataType: 'json',
                success: function(response) {
                    handleValidationSuccess(response, card);
                },
                error: handleValidationError
            });
        }

        function handleValidationSuccess(response, card) {            
            // Check the response object for a success or error message
            if (response.success) {
                // If there's a success message, take appropriate action
                card.slideUp(2000);
                decreaseJourneyCount();
            } else if (response.error) {
                var errorMessageElement = card.find('.journey-validation-error');
                errorMessageElement.slideDown(1000);
                errorMessageElement.text(response.error);
                errorMessageElement.css('display', 'flex');
                errorMessageElement.css('justify-content', 'center');
                errorMessageElement.css('align-items', 'center');
            }
        }

        function handleValidationError() {
            alert('Erreur lors de la connexion au serveur. Ressayer plus tard.');
        }

        function initializeMaps() {
            var numberOfJourneys = $("body").data("number-of-journeys");
            for (var i = 0; i < numberOfJourneys; i++) {
                initMap(i);
            }
        }

        function initMap(i) {
            var id = "card-map-" + i;
            var element = $("#" + id);
            if (!element.length) {  // Check if the jQuery object has any elements
                console.error("Element with ID", id, "was not found.");
                return; // exit the function
            }

            var startPosition = {
                lat: parseFloat(element.data("lat-start")),
                lng: parseFloat(element.data("long-start"))
            };

            var endPosition = {
                lat: parseFloat(element.data("lat-end")),
                lng: parseFloat(element.data("long-end"))
            };

            var map = new google.maps.Map(element[0], {  // Use element[0] to get the raw DOM element
                center: endPosition,
                zoom: 15,
            });

            var markerEnd = new google.maps.Marker({
                position: endPosition,
                map: map,
                title: "Arrivée",
            });
        }

        function initializeAutocompletes() {
            $(".client-name").each(function() {
                initializeAutocomplete(this);
            });
        }

        function initializeAutocomplete(inputElement) {
            var cache = {};

            $(inputElement).autocomplete({
                source: function(request, response) {
                    if (request.term in cache) {
                        response(cache[request.term]);
                        return;
                    }
                    
                    $.ajax({
                        type: "POST",
                        url: "/prestanaute/AjaxEndpoints/autocomplete.php",
                        data: {query: request.term},
                        success: function(data) {
                            var parsedData = JSON.parse(data);
                            cache[request.term] = parsedData;
                            response(parsedData);
                        }
                    });
                },
                minLength: 3
            });
        }

        function decreaseJourneyCount() {
            // Get the current count
            var currentCount = parseInt($('#j-Count').text(), 10);  // Convert string to integer
            // Decrease the count
            var newCount = currentCount - 1;

            // Update the div content with the new count
            $('#j-Count').text(newCount);
        }

    </script>
    <script async
    src="https://maps.googleapis.com/maps/api/js?key=AIzaSyDzSRxj1mUuZlAWu7HWwQGmvjI0kVqmTuQ">
    </script>
</body>
</html>