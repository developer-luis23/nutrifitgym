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

        // Inserción de datos en la tabla usuarios (sin rol inicialmente)
        $sql = "INSERT INTO usuarios (nombre, apellido, email, contraseña) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssss", $nombre, $apellido, $email, $contraseña);

        if ($stmt->execute()) {
            $id = $stmt->insert_id; // Obtener el ID del registro recién creado

            // Asignación automática de rol basado en condiciones
            define('ROL_ADMINISTRADOR', 1);
            define('ROL_TECNICO', 2);
            define('ROL_USUARIO', 3);

            $rol_id = ($id == 1) ? ROL_ADMINISTRADOR : (($id % 2 == 0) ? ROL_TECNICO : ROL_USUARIO);

            // Validar que el rol existe en la tabla roles antes de asignarlo
            $query_check_role = "SELECT COUNT(*) AS count FROM roles WHERE id = ?";
            $stmt_check_role = $conn->prepare($query_check_role);
            $stmt_check_role->bind_param("i", $rol_id);
            $stmt_check_role->execute();
            $result_role = $stmt_check_role->get_result();
            $row_role = $result_role->fetch_assoc();

            if ($row_role['count'] == 0) {
                throw new Exception("Error: El rol asignado automáticamente no existe en la tabla roles.");
            }

            // Actualización del rol en la tabla usuarios
            $sql_rol = "UPDATE usuarios SET rol_id = ? WHERE id = ?";
            $stmt_rol = $conn->prepare($sql_rol);
            $stmt_rol->bind_param("ii", $rol_id, $id);

            if ($stmt_rol->execute()) {
                echo json_encode(['success' => 'Usuario registrado correctamente con rol asignado automáticamente.']);
            } else {
                throw new Exception("Error al asignar el rol: " . $stmt_rol->error);
            }
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
    if (isset($stmt_check_role)) $stmt_check_role->close();
    if (isset($stmt_rol)) $stmt_rol->close();
    $conn->close();
}

// Función de validación
function validateInput($field, $message) {
    if (empty($field)) {
        throw new Exception($message);
    }
}
?>