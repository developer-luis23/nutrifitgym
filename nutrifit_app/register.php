<?php
session_start();
require 'conexion.php';

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Capturar datos y sanitizarlos
        $nombre = htmlspecialchars(trim($_POST['first_name']));
        $apellido = htmlspecialchars(trim($_POST['last_name']));
        $email = htmlspecialchars(trim($_POST['email']));
        $contraseña = $_POST['password'];

        // Validaciones de entrada
        validateInput($nombre, "El nombre es obligatorio.");
        validateInput($apellido, "El apellido es obligatorio.");
        validateInput($email, "El email es obligatorio.");
        validateInput($contraseña, "La contraseña es obligatoria.");

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("El formato del correo electrónico no es válido.");
        }

        // Hash de la contraseña
        $contraseña = password_hash($contraseña, PASSWORD_BCRYPT);

        // Comprobación previa de email duplicado
        $query_check_email = "SELECT COUNT(*) AS count FROM usuarios WHERE email = ?";
        $stmt_check_email = $conn->prepare($query_check_email);
        $stmt_check_email->bind_param("s", $email);
        $stmt_check_email->execute();
        $result_email = $stmt_check_email->get_result();
        $row_email = $result_email->fetch_assoc();

        if ($row_email['count'] > 0) {
            throw new Exception("El correo ya está registrado.");
        }

        // Contar registros actuales en la tabla usuarios
        $query_count_users = "SELECT COUNT(*) AS count FROM usuarios";
        $result_count_users = $conn->query($query_count_users);
        $row_count_users = $result_count_users->fetch_assoc();
        $total_usuarios = $row_count_users['count'];

        // Definir el rol basado en el número de registros
        define('ROL_ADMINISTRADOR', 1);
        define('ROL_TECNICO', 2);
        define('ROL_USUARIO', 3);

        $rol_id = ($total_usuarios == 0) ? ROL_ADMINISTRADOR : (($total_usuarios == 1) ? ROL_TECNICO : ROL_USUARIO);

        // Inserción de datos en la tabla usuarios
        $sql = "INSERT INTO usuarios (nombre, apellido, email, contraseña, rol_id) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssi", $nombre, $apellido, $email, $contraseña, $rol_id);

        if ($stmt->execute()) {
            echo json_encode(['success' => 'Usuario registrado correctamente con rol asignado automáticamente.']);
        } else {
            throw new Exception("Error en la inserción: " . $stmt->error);
        }
    }
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => $e->getMessage()]);
} finally {
    if (isset($stmt)) $stmt->close();
    if (isset($stmt_check_email)) $stmt_check_email->close();
    $conn->close();
}

// Función de validación
function validateInput($field, $message) {
    if (empty($field)) {
        throw new Exception($message);
    }
}
?>
