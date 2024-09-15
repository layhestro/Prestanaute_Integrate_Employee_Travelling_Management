<?php
class Logger {
    private string $dataLogFile;
    private string $errorLogFile;

    public function __construct($dataLogFilePath = '../logs/data_logs.json', $errorLogFilePath = '../logs/error_logs.json') {
        $this->dataLogFile = $dataLogFilePath;
        $this->errorLogFile = $errorLogFilePath;
    }

    private function writeLog($filePath, $message, $data = []) {
        $currentLogs = [];

        // Check if the log file already exists and read its content
        if (file_exists($filePath)) {
            $currentLogs = json_decode(file_get_contents($filePath), true);
        }

        // Append the new log
        $currentLogs[] = [
            'timestamp' => date('Y-m-d H:i:s'),
            'message'   => $message,
            'data'      => $data
        ];

        // Write back to the log file
        file_put_contents($filePath, json_encode($currentLogs, JSON_PRETTY_PRINT));
    }

    public function logData($message, $data = []) {
        $this->writeLog($this->dataLogFile, $message, $data);
    }

    public function logError($message, $data = []) {
        $this->writeLog($this->errorLogFile, $message, $data);
    }
}
?>
