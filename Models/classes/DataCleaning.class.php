<?php
/**
 * DataCleaning Class
 * 
 * Provides functionalities to clean and format data related to journeys.
 * 
 * @author Grégoire Mariette
 * @version 2.0
 */
class DataCleaning {
/**
 * Default address for the start of the journey if none is provided.
 */
const DEFAULT_START_ADDRESS = "adresse depart non disponible";

/**
 * Default address for the end of the journey if none is provided.
 */
const DEFAULT_END_ADDRESS = "adresse arrive non disponible";

/**
 * Default value for coordinates if none is provided.
 */
const DEFAULT_COORDINATE = 0;

/**
 * Maximum allowed stop time in seconds. Journeys with stop times below this
 * and not equal to 0 will be considered for merging with subsequent journeys.
 */
const MAXIMAL_STOP_TIME = 180;

public static function continuous(array $journeys) {
    $buffer = [];
    $j = 0;
    for($i = 0; $i < count($journeys) - 1; $i++) {
        $seconds = $journeys[$i]['totalTimeStoppedSeconds'];
        $dateTime1 = new DateTime($journeys[$i]['endDate']); 
        $dateTime1->modify("+$seconds seconds");
        $dateTime2 = new DateTime($journeys[$i+1]['startDate']);
        if ($dateTime1 != $dateTime2) {
            $buffer[$j][] = $journeys[$i];
            $buffer[$j][] = $journeys[$i+1];
            $buffer[$j][] = $dateTime1;
            $buffer[$j][] = $dateTime2;
            $buffer[$j][] = $dateTime1->diff($dateTime2)->format('%a days %h hours %i minutes %s seconds');
            $j++;
        }
    } 
    return $buffer;  
}

/**
 * Extracts and formats relevant information from an array of journeys.
 * #TODO : handle case where start or end date is missing
 * @param array $journeys The journeys to be processed.
 * @return array Formatted data for each journey.
 */
public static function relevantInformations(array $journeys): array {
    $buffer = [];
    foreach ($journeys as $journey) {
        // Check if both startDate and endDate are set and valid
        if (self::isValidDateFormat($journey['startDate']) && self::isValidDateFormat($journey['endDate'])) {
            $buffer[] = [
                'startDate' => self::convertDate($journey['startDate']),
                'endDate'   => self::convertDate($journey['endDate']),
                'startAddress' => $journey['startAddress'] ?? self::DEFAULT_START_ADDRESS,
                'endAddress'   => $journey['endAddress'] ?? self::DEFAULT_END_ADDRESS,
                'startLongitude' => $journey['startCoordinate']['longitude'] ?? self::DEFAULT_COORDINATE,
                'startLatitude'  => $journey['startCoordinate']['latitude'] ?? self::DEFAULT_COORDINATE,
                'endLongitude'   => $journey['endCoordinate']['longitude'] ?? self::DEFAULT_COORDINATE,
                'endLatitude'    => $journey['endCoordinate']['latitude'] ?? self::DEFAULT_COORDINATE,
            ];
        }
        // Optionally, handle invalid date formats here
    }
    return $buffer;
}

public static function isValidDateFormat($date): bool {
    if (empty($date)) {
        return false;
    }
    $regex = '/^2\d{3}-(0[1-9]|1[0-2])-(0[1-9]|[12][0-9]|3[01])T([01][0-9]|2[0-3]):([0-5][0-9]):([0-5][0-9])$/';

    return preg_match($regex, $date) === 1;
}


/**
 * Converts a date string from UTC to Europe/Brussels timezone and formats it.
 * 
 * @param string $date The date string in UTC to be converted.
 * @return string The formatted date string in Europe/Brussels timezone.
 */
public static function convertDate(string $date): string {
    $dateTime = new DateTime($date, new DateTimeZone('UTC'));
    return $dateTime->setTimezone(new DateTimeZone('Europe/Brussels'))->format("Y-m-d H:i:s");
}

/**
 * Recalculates the total time stopped in seconds for each journey based on the difference between 
 * the end date of the current journey and the start date of the subsequent journey. 
 * It sets totalTimeStoppedSeconds to 0 for the last journey.
 * 
 * @param array $journeys The journeys for which the totalTimeStoppedSeconds needs recalculating.
 * 
 * @return array Journeys with recalculated totalTimeStoppedSeconds.
 */
public static function recalculateTotalTimeStoppedSeconds(array $journeys): array {
    $size = count($journeys);
    for ($i = 0; $i < $size - 1; $i++) {
        $currentJourneyEnd = new DateTime($journeys[$i]['endDate']);
        $nextJourneyStart = new DateTime($journeys[$i + 1]['startDate']);
        
        $diff = $nextJourneyStart->diff($currentJourneyEnd);
        $totalStopTime = ($diff->days * 24 * 3600) + ($diff->h * 3600) + ($diff->i * 60) + $diff->s;

        $journeys[$i]['totalTimeStoppedSeconds'] = $totalStopTime;
    }

    // Set totalTimeStoppedSeconds to 0 for the last journey
    $journeys[count($journeys) - 1]['totalTimeStoppedSeconds'] = 0;

    return $journeys;
}

/**
 * Processes a list of journeys and merges those that have short stops with subsequent journeys.
 * The merged journeys will have a combined start and end time, and the stop time will be recalculated.
 * 
 * @param array $journeys The list of journeys.
 * 
 * @return array Processed journeys with short stops merged.
 */
public static function removeStops(array $journeys): array {
    $buffer = array();
    $i = 0;
    do {
        if ($journeys[$i]['totalTimeStoppedSeconds'] < self::MAXIMAL_STOP_TIME && $journeys[$i]['totalTimeStoppedSeconds'] != 0) { 
            $j = $i;
            do {
                $j++;
            }
            while($journeys[$j]['totalTimeStoppedSeconds'] < self::MAXIMAL_STOP_TIME && 
            $journeys[$j]['totalTimeStoppedSeconds'] != 0);
            $buffer[] = self::mergeJourney($journeys, $i, $j);
            $j++;
            $i = $j;
        }
        else {
            $buffer[] = $journeys[$i];
            $i++;
        }
    } while($i < count($journeys));
    return $buffer;
}

/**
 * Merges two or more journeys into a single journey based on the provided indices.
 * The start attributes are taken from the first journey, and the end attributes are taken from the last journey.
 * 
 * @param array $journeys The list of journeys.
 * @param int $start Index of the first journey to be merged.
 * @param int $end Index of the last journey to be merged.
 * 
 * @return array Merged journey.
 */
private static function mergeJourney(array $journeys, int $start, int $end): array {
    return array(
        'startDate'               => $journeys[$start]['startDate'],
        'endDate'                 => $journeys[$end]['endDate'],

        'startAddress'            => $journeys[$start]['startAddress'],
        'endAddress'              => $journeys[$end]['endAddress'],

        'startLongitude'          => $journeys[$start]['startLongitude'],
        'startLatitude'           => $journeys[$start]['startLatitude'],
        
        'endLongitude'            => $journeys[$end]['endLongitude'],
        'endLatitude'             => $journeys[$end]['endLatitude'],

        'totalTimeStoppedSeconds' => $journeys[$end]['totalTimeStoppedSeconds'],
    );
}

public static function removeLastJourney(array &$journeys): array {
    return array_pop($journeys);
}

}
?>