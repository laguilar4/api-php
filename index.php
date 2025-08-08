<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Si la petición es OPTIONS, termina aquí (CORS preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'database/database.php';

// Obtener la ruta solicitada (endpoint)
$request_uri = explode('/', trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/'));

// Buscar la palabra "api" y tomar lo que está después
$apiIndex = array_search('api', $request_uri);
if ($apiIndex !== false) {
    $request_uri = array_slice($request_uri, $apiIndex + 1);
}

// Método HTTP
$method = $_SERVER['REQUEST_METHOD'];

// Definir endpoints
switch ($request_uri[0] ?? '') {

    case 'estudiantes':
        if ($method === 'GET') {
            getEstudiantes();
        } elseif ($method === 'POST') {
            addEstudiante();
        } else {
            sendResponse(405, ["error" => "Método no permitido"]);
        }
        break;

    case 'profesores':
        if ($method === 'GET') {
            getProfesores();
        } else {
            sendResponse(405, ["error" => "Método no permitido"]);
        }
        break;

    case 'test':
        if ($method === 'GET') {
            sendResponse(200, ["message" => "API funcionando correctamente en local"]);
        } else {
            sendResponse(405, ["error" => "Método no permitido"]);
        }
        break;

    default:
        sendResponse(404, ["error" => "Endpoint no encontrado"]);
        break;
}

// --- Funciones ---
function getEstudiantes() {
    $conn = getConnection();
    $stmt = $conn->query("SELECT id, nombre, correo FROM estudiantes");
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    sendResponse(200, $data);
}

function addEstudiante() {
    $data = json_decode(file_get_contents("php://input"));
    if (!isset($data->nombre) || !isset($data->correo)) {
        sendResponse(400, ["error" => "Faltan datos"]);
    }
    $conn = getConnection();
    $stmt = $conn->prepare("INSERT INTO estudiantes (nombre, correo) VALUES (:nombre, :correo)");
    $stmt->bindParam(":nombre", $data->nombre);
    $stmt->bindParam(":correo", $data->correo);
    $stmt->execute();
    sendResponse(201, ["message" => "Estudiante agregado"]);
}

function getProfesores() {
    $conn = getConnection();
    $stmt = $conn->query("SELECT id, nombre, correo FROM profesores");
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    sendResponse(200, $data);
}

function sendResponse($status, $data) {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
