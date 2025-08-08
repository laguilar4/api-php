<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'database/database.php';

$request_uri = explode('/', trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/'));

$apiIndex = array_search('api', $request_uri);
if ($apiIndex !== false) {
    $request_uri = array_slice($request_uri, $apiIndex + 1);
}

$method = $_SERVER['REQUEST_METHOD'];

switch ($request_uri[0] ?? '') {

    case 'users':
        if ($method === 'GET') {
            if (isset($request_uri[1]) && is_numeric($request_uri[1])) {
                getUserById($request_uri[1]);
            } else {
                getUsers();
            }
        } elseif ($method === 'POST') {
           addUser();
        } else {
            sendResponse(405, ["error" => "Método no permitido"]);
        }
        break;

    case 'tasks':
    if ($method === 'POST') {
        addTask();
    } elseif ($method === 'PUT') {
        if (isset($request_uri[1]) && is_numeric($request_uri[1])) {
            updateTask($request_uri[1]);
        } else {
            sendResponse(400, ["error" => "ID de tarea no válido"]);
        }
    } elseif ($method === 'DELETE') {
        if (isset($request_uri[1]) && is_numeric($request_uri[1])) {
            deleteTask($request_uri[1]);
        } else {
            sendResponse(400, ["error" => "ID de tarea no válido"]);
        }
    } else {
        sendResponse(405, ["error" => "Método no permitido"]);
    }
    break;

    case 'login':
        if ($method === 'POST') {
            loginUsuario();
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
function getUsers() {
    $conn = getConnection();
    $stmt = $conn->query("SELECT * FROM users WHERE role = 'student'");
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    sendResponse(200, $data);
}

function getUserById($id) {
    $conn = getConnection();
    $stmt = $conn->prepare("SELECT * FROM users WHERE role = 'student' AND id = :id");
    $stmt->bindParam(":id", $id, PDO::PARAM_INT);
    $stmt->execute();
    $estudiante = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($estudiante) {
        sendResponse(200, $estudiante);
    } else {
        sendResponse(404, ["error" => "Estudiante no encontrado"]);
    }
}

function addUser() {
    $data = json_decode(file_get_contents("php://input"));

    // Validación de datos obligatorios
    if (!isset($data->nombre) || !isset($data->email) || !isset($data->password) || !isset($data->role)) {
        sendResponse(400, ["error" => "Faltan datos obligatorios"]);
    }

    $conn = getConnection();

    // Usando SHA2 para el hash en MySQL
    $stmt = $conn->prepare("INSERT INTO dtic_uninorte.users (nombre, email, password_hash, role)
                            VALUES (:nombre, :email, SHA2(:password, 256), :role)");
    $stmt->bindParam(":nombre", $data->nombre);
    $stmt->bindParam(":email", $data->email);
    $stmt->bindParam(":password", $data->password);
    $stmt->bindParam(":role", $data->role);

    try {
        $stmt->execute();
        sendResponse(201, ["message" => "Usuario creado correctamente"]);
    } catch (PDOException $e) {
        sendResponse(500, ["error" => "Error al crear usuario", "detalle" => $e->getMessage()]);
    }
}



function sendResponse($status, $data) {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function addTask() {
    $data = json_decode(file_get_contents("php://input"));

    if (!isset($data->user_id) || !isset($data->title)) {
        sendResponse(400, ["error" => "Faltan datos obligatorios"]);
    }

    $conn = getConnection();
    $stmt = $conn->prepare("INSERT INTO tasks (user_id, title, description, status, due_date)
                            VALUES (:user_id, :title, :description, :status, :due_date)");

    $stmt->bindParam(":user_id", $data->user_id, PDO::PARAM_INT);
    $stmt->bindParam(":title", $data->title);
    $stmt->bindParam(":description", $data->description);
    $stmt->bindParam(":status", $data->status);
    $stmt->bindParam(":due_date", $data->due_date);

    try {
        $stmt->execute();
        sendResponse(201, ["message" => "Tarea creada correctamente"]);
    } catch (PDOException $e) {
        sendResponse(500, ["error" => "Error al crear tarea", "detalle" => $e->getMessage()]);
    }
}

function updateTask($id) {
    $data = json_decode(file_get_contents("php://input"));

    $conn = getConnection();
    $stmt = $conn->prepare("UPDATE tasks 
                            SET title = :title, description = :description, status = :status, due_date = :due_date
                            WHERE id = :id");

    $stmt->bindParam(":title", $data->title);
    $stmt->bindParam(":description", $data->description);
    $stmt->bindParam(":status", $data->status);
    $stmt->bindParam(":due_date", $data->due_date);
    $stmt->bindParam(":id", $id, PDO::PARAM_INT);

    try {
        $stmt->execute();
        if ($stmt->rowCount() > 0) {
            sendResponse(200, ["message" => "Tarea actualizada correctamente"]);
        } else {
            sendResponse(404, ["error" => "Tarea no encontrada"]);
        }
    } catch (PDOException $e) {
        sendResponse(500, ["error" => "Error al actualizar tarea", "detalle" => $e->getMessage()]);
    }
}

function deleteTask($id) {
    $conn = getConnection();
    $stmt = $conn->prepare("DELETE FROM tasks WHERE id = :id");
    $stmt->bindParam(":id", $id, PDO::PARAM_INT);

    try {
        $stmt->execute();
        if ($stmt->rowCount() > 0) {
            sendResponse(200, ["message" => "Tarea eliminada correctamente"]);
        } else {
            sendResponse(404, ["error" => "Tarea no encontrada"]);
        }
    } catch (PDOException $e) {
        sendResponse(500, ["error" => "Error al eliminar tarea", "detalle" => $e->getMessage()]);
    }
}

function loginUsuario() {
    $data = json_decode(file_get_contents("php://input"));

    if (!isset($data->email) || !isset($data->password)) {
        sendResponse(400, ["error" => "Faltan datos obligatorios"]);
    }

    $conn = getConnection();
    $stmt = $conn->prepare("SELECT id, nombre, email, role, created_at 
                            FROM users 
                            WHERE email = :email AND password_hash = SHA2(:password, 256)");
    $stmt->bindParam(":email", $data->email);
    $stmt->bindParam(":password", $data->password);

    $stmt->execute();
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuario) {
        sendResponse(200, [
            "message" => "Login exitoso",
            "usuario" => $usuario
        ]);
    } else {
        sendResponse(401, ["error" => "Credenciales inválidas"]);
    }
}


