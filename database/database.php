<?php
function getConnection() {
    $host = "54.147.36.38";//"localhost";
    $dbname = "dtic_uninorte";
    $user = "root";
    $pass = "PasSWoRD-PrueBaTECnica";

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        echo json_encode(["error" => $e->getMessage()]);
        exit;
    }
}
?>
