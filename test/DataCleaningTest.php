<?php
require_once ('../Models/classes/RetrieveDataFromAPI.class.php'); 
require_once ('../Models/classes/DataCleaning.class.php');


$startDate = '2023-11-10T06:00:00';
$endDate = '2023-11-21T19:00:00';
$vehicleId = 'FR_8553_8';
//$vehicleId = '2085341';

$buffer1 = RetrieveDataFromAPI::vehicleJourneys($startDate, $endDate, $vehicleId);
var_dump(count($buffer1));
$buffer2 = DataCleaning::relevantInformations($buffer1);
var_dump(count($buffer2));
$buffer3 = DataCleaning::recalculateTotalTimeStoppedSeconds($buffer2);
var_dump(count($buffer3));
$buffer4 = DataCleaning::removeStops($buffer3);
?>
<div>
    <pre>
        <?php echo json_encode(DataCleaning::continuous($buffer1), JSON_PRETTY_PRINT); ?>
    </pre>
    <pre>
        <?php echo json_encode(DataCleaning::continuous($buffer4), JSON_PRETTY_PRINT); ?>
    </pre>
</div>

<div style="display: flex;">
    <h4></h4>
    <div>
        <h3>Input data</h3>
        <h4><?php echo "Number of journeys in raw: " . count($buffer3) . "<br>"; ?></h4>
        <pre>
            <?php echo json_encode($buffer3, JSON_PRETTY_PRINT); ?>
        </pre>
    </div>
    <div>
        <h3>Output data</h3>
        <h4><?php echo "Number of journeys after removing stops: " . count($buffer4) . "<br>"; ?></h4>
        <pre>
            <?php echo json_encode($buffer4, JSON_PRETTY_PRINT); ?>
        </pre>
    </div>
</div>