<?php
require_once 'conexion.php';
session_start();

// Verificar si la sesión ya tiene un rol definido
if (isset($_SESSION['rol'])) {
    switch ($_SESSION['rol']) {
        case 1:
            header('Location: admin.html');
            exit;
        case 2:
            header('Location: user.html');
            exit;
        case 3:
            header('Location: developers.html');
            exit;
        default:
            header('Location: error.html');
            exit;
    }
}

// Verificar si los datos del formulario están presentes
if (!empty($_POST['email']) && !empty($_POST['password'])) {
    // Limpiar y validar las entradas
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = htmlspecialchars($_POST['password'], ENT_QUOTES, 'UTF-8');

    // Validar formato del email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "Correo electrónico inválido.";
        exit;
    }

    try {
        // Crear conexión con PDO
        $db = (new Database())->connect();

        // Preparar consulta para evitar inyecciones SQL
        $query = $db->prepare('SELECT * FROM usuarios WHERE email = :email AND contraseña = :password');
        $query->execute(['email' => $email, 'password' => $password]);

        // Obtener datos del usuario
        $row = $query->fetch(PDO::FETCH_ASSOC);

        if ($row && isset($row['rol_id'])) {
            // Usar rol_id en lugar de rol
            $_SESSION['rol'] = $row['rol_id'];

            // Redirigir según el rol del usuario
            switch ($_SESSION['rol']) {
                case 1:
                    header('Location: admin.html');
                    exit;
                case 2:
                    header('Location: user.html');
                    exit;
                case 3:
                    header('Location: developers.html');
                    exit;
                default:
                    header('Location: error.html');
                    exit;
            }
        } else {
            // No existe el usuario o la contraseña es incorrecta
            header('Location: login.html');
            exit;
        }
    } catch (PDOException $e) {
        // Manejo de errores de conexión o consulta
        echo "Error en la conexión: " . $e->getMessage();
    }
} else {
    echo "Por favor, complete todos los campos.";
}
?>