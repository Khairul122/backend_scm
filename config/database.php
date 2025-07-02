<?php
class Database {
    private static $host = "localhost";
    private static $user = "root";
    private static $pass = "";
    private static $dbname = "db_scm";
    private static $conn;

    public static function connect() {
        if (!self::$conn) {
            self::$conn = new mysqli(self::$host, self::$user, self::$pass, self::$dbname);
            if (self::$conn->connect_error) {
                http_response_code(500);
                echo json_encode(['error' => 'Database connection failed: ' . self::$conn->connect_error]);
                exit;
            }
        }
        return self::$conn;
    }
}