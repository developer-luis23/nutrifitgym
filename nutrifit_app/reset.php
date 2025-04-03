<?php
session_start();
require 'conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'];
    $nueva_contraseña = $_POST['new_password'];

    // Validate inputs
    if (empty($token) || empty($nueva_contraseña)) {
        echo "Token y nueva contraseña son obligatorios.";
        exit;
    }

    $nueva_contraseña = password_hash($nueva_contraseña, PASSWORD_BCRYPT);

    $sql = "SELECT usuario_id FROM recuperacion_contraseña WHERE token = ? AND usado = 0";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($usuario_id);
        $stmt->fetch();

        $sql_update = "UPDATE usuarios SET contraseña = ? WHERE id = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("si", $nueva_contraseña, $usuario_id);
        $stmt_update->execute();
        $stmt_update->close();

        $sql_usado = "UPDATE recuperacion_contraseña SET usado = 1 WHERE token = ?";
        $stmt_usado = $conn->prepare($sql_usado);
        $stmt_usado->bind_param("s", $token);
        $stmt_usado->execute();
        $stmt_usado->close();

        echo "Contraseña restablecida exitosamente.";
    } else {
        echo "Token inválido o ya utilizado.";
    }

    $stmt->close();
    $conn->close();
}
?>
