<?php
class Database
{
    private $host = "localhost";
    private $db_name = "gia_db";
    private $username = "root";
    private $password = "";
    public $conn;

    public function getConnection()
    {
        $this->conn = null;

        try {
            // Utilizamos el charset utf8mb4 para soportar caracteres especiales sin problemas
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4", $this->username, $this->password);
            $this->conn->exec("set names utf8mb4");
            // Configuramos PDO para que lance excepciones en caso de error
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            // Configurar el fetch mode por defecto a asociativo
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $exception) {
            echo "Error de conexiÃ³n: " . $exception->getMessage();
        }

        return $this->conn;
    }
}
