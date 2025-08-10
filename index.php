<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'database/database.php';

// ** Se requiere la librería JWT para el manejo de tokens **
require_once 'vendor/autoload.php';
use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

// Clave secreta para firmar y verificar tokens JWT (debe ser segura y privada)
$claveSecreta = 'UNIVERSIDADNORTE2025++';

// Función para validar el token JWT y retornar el objeto decodificado con info del usuario
function validarToken() {
    global $claveSecreta;

    // Obtener headers HTTP
    // Normalizar todos los headers a minúsculas para evitar problemas de casing
    $headers = array_change_key_case(getallheaders(), CASE_LOWER);

    if (!isset($headers['authorization'])) {
        sendResponse(401, ["error" => "Token de autorización requerido"]);
    }

    $authHeader = $headers['authorization'];

    // Verificar que el header tenga el formato correcto "Bearer <token>"
    if (stripos($authHeader, 'Bearer ') !== 0) {
        sendResponse(401, ["error" => "Formato de token inválido"]);
    }

    // Extraer token removiendo "Bearer "
    $token = substr($authHeader, 7);


    try {
        // Decodificar token, validar firma y expiración
        $decoded = JWT::decode($token, new Key($claveSecreta, 'HS256'));
        return $decoded; // Contiene los datos del payload (user_id, role, etc)
    } catch (Exception $e) {
        sendResponse(401, ["error" => "Token inválido o expirado"]);
    }
}

$request_uri = explode('/', trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/'));

$apiIndex = array_search('api', $request_uri);
if ($apiIndex !== false) {
    $request_uri = array_slice($request_uri, $apiIndex + 1);
}

$method = $_SERVER['REQUEST_METHOD'];

switch ($request_uri[0] ?? '') {

    case 'users':
        // ** Aquí protegemos el endpoint con JWT **
        $user = validarToken();

        // Ejemplo: solo usuarios admin pueden listar todos usuarios
        if ($method === 'GET') {
            if ($user->role !== 'admin') {
                sendResponse(403, ["error" => "Acceso no autorizado"]);
            }
            if (isset($request_uri[1]) && is_numeric($request_uri[1])) {
                getUserById($request_uri[1]);
            } else {
                getUsers();
            }
        } elseif ($method === 'POST') {
            // Solo admin puede crear usuarios
            if ($user->role !== 'admin') {
                sendResponse(403, ["error" => "Acceso no autorizado"]);
            }
            addUser();
        } else {
            sendResponse(405, ["error" => "Método no permitido"]);
        }
        break;

    case 'tasks':
        $user = validarToken();

        if ($method === 'POST') {
            // Solo usuarios autenticados pueden crear tareas
            addTask($user);
        } elseif ($method === 'PUT') {
            if (isset($request_uri[1]) && is_numeric($request_uri[1])) {
                updateTask($request_uri[1], $user);
            } else {
                sendResponse(400, ["error" => "ID de tarea no válido"]);
            }
        } elseif ($method === 'GET') {
            if (isset($request_uri[1]) && is_numeric($request_uri[1])) {
                // El usuario solo puede obtener sus propias tareas o admin puede ver cualquiera
                if ($user->role !== 'admin' && intval($user->user_id) !== intval($request_uri[1])) {
                    sendResponse(403, ["error" => "Acceso no autorizado"]);
                }
                getTaksUser($request_uri[1]);
            } else {
                sendResponse(400, ["error" => "ID de tarea no válido"]);
            }
        } elseif ($method === 'DELETE') {
            if (isset($request_uri[1]) && is_numeric($request_uri[1])) {
                deleteTask($request_uri[1], $user);
            } else {
                sendResponse(400, ["error" => "ID de tarea no válido"]);
            }
        } else {
            sendResponse(405, ["error" => "Método no permitido"]);
        }
        break;

    case 'login':
        // Login no requiere token
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
    $stmt = $conn->query("SELECT id, nombre, email, role, created_at FROM users");
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    sendResponse(200, $data);
}

function getUserById($id) {
    $conn = getConnection();
    $stmt = $conn->prepare("SELECT id, nombre, email, role, created_at FROM users WHERE role = 'student' AND id = :id");
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

    // Validar formato email
    if (!filter_var($data->email, FILTER_VALIDATE_EMAIL)) {
        sendResponse(400, ["error" => "Email inválido"]);
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
        // No devolver detalle técnico en producción
        sendResponse(500, ["error" => "Error al crear usuario"]);
    }
}

function sendResponse($status, $data) {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function addTask($user) {
    $data = json_decode(file_get_contents("php://input"));

    if (!isset($data->title)) {
        sendResponse(400, ["error" => "Faltan datos obligatorios"]);
    }

    // El usuario autenticado será el propietario de la tarea (ignoramos user_id enviado)
    $user_id = $user->user_id;

    $conn = getConnection();
    $stmt = $conn->prepare("INSERT INTO tasks (user_id, title, description, status, due_date)
                            VALUES (:user_id, :title, :description, :status, :due_date)");

    $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
    $stmt->bindParam(":title", $data->title);
    $stmt->bindParam(":description", $data->description);
    $stmt->bindParam(":status", $data->status);
    $stmt->bindParam(":due_date", $data->due_date);

    try {
        $stmt->execute();
        sendResponse(201, ["message" => "Tarea creada correctamente"]);
    } catch (PDOException $e) {
        sendResponse(500, ["error" => "Error al crear tarea"]);
    }
}

function getTaksUser($id) {
    $conn = getConnection();
    $stmt = $conn->prepare("SELECT 
    t.id as id, 
    t.user_id as user_id, 
    t.title as title, 
    t.description as description, 
    t.status as status 
    FROM tasks t 
    WHERE t.user_id = :id");
    $stmt->bindParam(":id", $id, PDO::PARAM_INT);
    $stmt->execute();
    $tareas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($tareas) {
        sendResponse(200, $tareas);
    } else {
        sendResponse(404, ["error" => "Tareas no encontradas"]);
    }
}

function updateTask($id, $user) {
    $data = json_decode(file_get_contents("php://input"));

    $conn = getConnection();

    // Verificar que la tarea pertenece al usuario o el usuario es admin
    $stmtCheck = $conn->prepare("SELECT user_id FROM tasks WHERE id = :id");
    $stmtCheck->bindParam(":id", $id, PDO::PARAM_INT);
    $stmtCheck->execute();
    $task = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if (!$task) {
        sendResponse(404, ["error" => "Tarea no encontrada"]);
    }

    if ($user->role !== 'admin' && intval($user->user_id) !== intval($task['user_id'])) {
        sendResponse(403, ["error" => "No tienes permiso para modificar esta tarea"]);
    }

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
            sendResponse(200, ["message" => "No hubo cambios en la tarea"]);
        }
    } catch (PDOException $e) {
        sendResponse(500, ["error" => "Error al actualizar tarea"]);
    }
}

function deleteTask($id, $user) {
    $conn = getConnection();

    // Verificar que la tarea pertenece al usuario o es admin
    $stmtCheck = $conn->prepare("SELECT user_id FROM tasks WHERE id = :id");
    $stmtCheck->bindParam(":id", $id, PDO::PARAM_INT);
    $stmtCheck->execute();
    $task = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if (!$task) {
        sendResponse(404, ["error" => "Tarea no encontrada"]);
    }

    if ($user->role !== 'admin' && intval($user->user_id) !== intval($task['user_id'])) {
        sendResponse(403, ["error" => "No tienes permiso para eliminar esta tarea"]);
    }

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
        sendResponse(500, ["error" => "Error al eliminar tarea"]);
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

    global $claveSecreta;

    if ($usuario) {
        $payload = [
            'iat' => time(),
            'exp' => time() + (60*60*24), 
            'user_id' => $usuario['id'],
            'role' => $usuario['role'],
            'nombre' => $usuario['nombre'],
            'email' => $usuario['email']
        ];
        $jwt = JWT::encode($payload, $claveSecreta, 'HS256');

        sendResponse(200, [
            "message" => "Login exitoso",
            "token" => $jwt,
            "usuario" => $usuario
        ]);
    } else {
        sendResponse(401, ["error" => "Credenciales inválidas"]);
    }
}
