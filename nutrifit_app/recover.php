<?php
session_start();
require 'conexion.php';

function enviarCorreoRecuperacion($email, $conn) {
    $sql = "SELECT id FROM Usuarios WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($id);
        $stmt->fetch();

        $token = bin2hex(random_bytes(32));
        $sql_token = "INSERT INTO Recuperacion_Contraseña (usuario_id, token) VALUES (?, ?)";
        $stmt_token = $conn->prepare($sql_token);
        $stmt_token->bind_param("is", $id, $token);
        $stmt_token->execute();
        $stmt_token->close();

        // Enviar correo de recuperación de contraseña
        $to = $email;
        $subject = "Recuperación de contraseña - Nutrifit";
        $message = "Hola,\n\nHemos recibido una solicitud para restablecer tu contraseña. Haz clic en el siguiente enlace para restablecer tu contraseña:\n\n";
        $message .= "http://tu_dominio.com/reset_password.php?token=" . $token . "\n\n";
        $message .= "Si no has solicitado este cambio, ignora este correo.\n\nSaludos,\nEl equipo de Nutrifit";
        $headers = "From: no-reply@tu_dominio.com";

        if (mail($to, $subject, $message, $headers)) {
            return "Si el correo electrónico está registrado, recibirás un correo con las instrucciones para restablecer tu contraseña.";
        } else {
            return "Error al enviar el correo de recuperación.";
        }
    } else {
        return "Correo electrónico no registrado.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];

    if (empty($email)) {
        echo "El campo de correo electrónico es obligatorio.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "El formato del correo electrónico no es válido.";
    } else {
        echo enviarCorreoRecuperacion($email, $conn);
    }

    $conn->close();
}
?>
