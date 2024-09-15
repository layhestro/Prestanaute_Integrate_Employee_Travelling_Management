<?php
/**
 * The retrieveDataFromAPI class provides methods to interact with a specific API endpoint.
 * The API endpoint is used to retrieve vehicle journey data.
 *
 * This class primarily offers the capability to fetch journey details for a given vehicle
 * over a specified date range. It manages the connection, data retrieval, and error handling
 * when interacting with the external API.
 *
 * @author Grégoire Mariette
 * @version 2.0
 */
class retrieveDataFromAPI {
    /** @var string The API endpoint URL. */
    private const ENDPOINT = '';

    /** @var string Authentication credentials for the API. */
    private const AUTH = '';

    /**
     * Fetches vehicle journey details from the Masternaut Connect API.
     *
     * @param string $startDate Starting date for the journey search in the format 'Y-m-d'.
     * @param string $endDate Ending date for the journey search in the format 'Y-m-d'.
     * @param string $vehicleId The unique identifier of the vehicle.
     * 
     * @return array A list of vehicle journeys.
     * 
     * @throws Exception Throws an exception if an error occurs during the API call or if the response is unexpected.
     */
    public static function vehicleJourneys(string $startDate, string $endDate, string $vehicleId): array {
        $endpoint = self::ENDPOINT . "journey/detail/vehicle";
        $pageIndex = 0;
        $page = [];

        do {
            $params = [
                'startDate' => $startDate,
                'endDate'   => $endDate,
                'vehicleId' => $vehicleId,
                'pageSize'  => '200',
                'pageIndex' => $pageIndex
            ];

            try {
                $buffer = json_decode(self::GetRequest($endpoint, self::AUTH, $params), true);
            } catch (Exception $e) {
                throw new Exception("Failed to retrieve or decode the data from the API for the following vehicle : " . $vehicleId . ": " . $e->getMessage());
            }
            if (!isset($buffer['items'])) {
                throw new Exception("Unexpected API response: " . json_encode($buffer));
            }

            $page['totalPages'] = $buffer['totalPages'];
            $page['totalCount'] = $buffer['totalCount'];
            $page['items'] = array_merge($page['items'] ?? [], $buffer['items'] ?? []);
            
            $pageIndex++;
        } while (isset($buffer["totalPages"]) && $pageIndex < $buffer["totalPages"]);

        return $page['items'];
    }

    /**
     * Makes a GET request to the specified API endpoint.
     *
     * @param string $endpoint The API endpoint URL.
     * @param string $auth The authentication credentials.
     * @param array $params Optional query parameters.
     * 
     * @return string The API response.
     * 
     * @throws Exception Throws an exception if there's a CURL error.
     */
    private static function GetRequest(string $endpoint, string $auth, array $params = array()): string {
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL             => $endpoint . '?' . http_build_query($params),
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_HTTP_VERSION    => CURL_HTTP_VERSION_1_1,
            CURLOPT_TIMEOUT         => 40,
            CURLOPT_USERPWD         => $auth,
            CURLOPT_HTTPAUTH        => CURLAUTH_BASIC,
        ]);

        $response = curl_exec($curl);
        $error = curl_error($curl);

        curl_close($curl);

        if ($error) {
            throw new Exception("CURL error : " . $error);
        }

        return $response;
    }
}
?>