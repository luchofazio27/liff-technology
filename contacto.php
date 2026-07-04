<?php
/**
 * ============================================================
 * LIFF TECHNOLOGY — contacto.php (con PHPMailer + SMTP)
 * Procesa el envío del formulario de contacto vía SMTP real.
 * ============================================================
 *
 * REQUISITO PREVIO — Instalar PHPMailer:
 *
 *   OPCIÓN A (recomendada si tenés Composer):
 *     composer require phpmailer/phpmailer
 *     -> esto crea la carpeta /vendor con el autoload.
 *
 *   OPCIÓN B (sin Composer, manual):
 *     1) Descargá el repo: https://github.com/PHPMailer/PHPMailer
 *     2) Copiá la carpeta "src" dentro de tu proyecto y renombrala a "PHPMailer"
 *        Necesitás estos 3 archivos:
 *          /PHPMailer/PHPMailer.php
 *          /PHPMailer/SMTP.php
 *          /PHPMailer/Exception.php
 *     3) La estructura final queda:
 *          /liff-technology/
 *            index.html
 *            styles.css
 *            script.js
 *            contacto.php
 *            /PHPMailer/
 *              PHPMailer.php
 *              SMTP.php
 *              Exception.php
 *
 *   Este archivo detecta automáticamente cuál de las dos opciones tenés instalada.
 */

// ---------- Carga de PHPMailer (Composer o manual) ----------
if (file_exists(__DIR__ . "/vendor/autoload.php")) {
    // Opción A: Composer
    require __DIR__ . "/vendor/autoload.php";
} else {
    // Opción B: archivos manuales
    require __DIR__ . "/PHPMailer/Exception.php";
    require __DIR__ . "/PHPMailer/PHPMailer.php";
    require __DIR__ . "/PHPMailer/SMTP.php";
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

header("Content-Type: application/json; charset=UTF-8");

// ============================================================
// CONFIGURACIÓN — COMPLETAR ANTES DE PROBAR EL ENVÍO
// ============================================================

// --- Datos de tu cuenta de correo (la que vas a usar para enviar) ---
$SMTP_HOST     = "smtp.hostinger.com";        // CAMBIAR si tu proveedor usa otro host SMTP
$SMTP_USER     = "no-reply@tudominio.com";    // CAMBIAR — tu casilla real de Hostinger
$SMTP_PASS     = "TU_CONTRASEÑA_AQUI";        // CAMBIAR — contraseña de esa casilla de correo
$SMTP_PORT     = 465;                         // 465 con SSL (SMTPS) o 587 con TLS (STARTTLS)
$SMTP_SECURE   = PHPMailer::ENCRYPTION_SMTPS; // usar ENCRYPTION_STARTTLS si usás el puerto 587

// --- Datos de envío/recepción ---
$DESTINATARIO      = "tu-email@tudominio.com"; // CAMBIAR — a dónde llegan las consultas
$NOMBRE_REMITENTE  = "Liff Technology — Web";   // nombre visible como remitente
$log_local         = __DIR__ . "/contactos.log"; // respaldo local, útil mientras probás

// ============================================================
// A partir de acá no hace falta tocar nada
// ============================================================

// Solo aceptar peticiones POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(["ok" => false, "error" => "Método no permitido."]);
    exit;
}

// ---------- Leer datos (acepta JSON o form-data) ----------
$input = json_decode(file_get_contents("php://input"), true);
if (!$input) {
    $input = $_POST; // fallback si se envía como form-data en vez de JSON
}

$nombre  = isset($input["nombre"])  ? trim($input["nombre"])  : "";
$email   = isset($input["email"])   ? trim($input["email"])   : "";
$tipo    = isset($input["tipo"])    ? trim($input["tipo"])    : "No especificado";
$mensaje = isset($input["mensaje"]) ? trim($input["mensaje"]) : "";

// ---------- Validación server-side ----------
$errores = [];

if (mb_strlen($nombre) < 2) {
    $errores[] = "El nombre es obligatorio.";
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errores[] = "El email no es válido.";
}

if (!empty($errores)) {
    http_response_code(400);
    echo json_encode(["ok" => false, "error" => implode(" ", $errores)]);
    exit;
}

// ---------- Sanitizar antes de usar en el mail ----------
$nombre_limpio  = htmlspecialchars($nombre, ENT_QUOTES, "UTF-8");
$email_limpio   = htmlspecialchars($email, ENT_QUOTES, "UTF-8");
$tipo_limpio    = htmlspecialchars($tipo, ENT_QUOTES, "UTF-8");
$mensaje_limpio = htmlspecialchars($mensaje, ENT_QUOTES, "UTF-8");

// ---------- Registro local (respaldo, útil en XAMPP) ----------
$linea_log = date("Y-m-d H:i:s") . " | {$nombre_limpio} | {$email_limpio} | {$tipo_limpio} | " . str_replace("\n", " ", $mensaje_limpio) . PHP_EOL;
@file_put_contents($log_local, $linea_log, FILE_APPEND);

// ---------- Armar y enviar el mail con PHPMailer ----------
$mail = new PHPMailer(true);

try {
    // Configuración del servidor SMTP
    $mail->isSMTP();
    $mail->Host       = $SMTP_HOST;
    $mail->SMTPAuth   = true;
    $mail->Username   = $SMTP_USER;
    $mail->Password   = $SMTP_PASS;
    $mail->SMTPSecure = $SMTP_SECURE;
    $mail->Port       = $SMTP_PORT;
    $mail->CharSet    = "UTF-8";

    // Descomentar la siguiente línea solo si necesitás ver el detalle
    // de la conexión SMTP mientras debuggeás en local:
    // $mail->SMTPDebug = SMTP::DEBUG_SERVER;

    // Remitente y destinatario
    $mail->setFrom($SMTP_USER, $NOMBRE_REMITENTE);
    $mail->addAddress($DESTINATARIO);
    $mail->addReplyTo($email_limpio, $nombre_limpio);

    // Contenido
    $mail->isHTML(true);
    $mail->Subject = "Nueva consulta desde la web — Liff Technology";
    $mail->Body    = "
        <h2>Nueva consulta recibida</h2>
        <p><strong>Nombre:</strong> {$nombre_limpio}</p>
        <p><strong>Email:</strong> {$email_limpio}</p>
        <p><strong>Tipo de espacio:</strong> {$tipo_limpio}</p>
        <p><strong>Mensaje:</strong><br>" . nl2br($mensaje_limpio) . "</p>
        <hr>
        <p style='font-size:12px;color:#888;'>Enviado el " . date("d/m/Y H:i:s") . " desde " . $_SERVER["REMOTE_ADDR"] . "</p>
    ";
    $mail->AltBody = "Nombre: {$nombre_limpio}\nEmail: {$email_limpio}\nTipo: {$tipo_limpio}\nMensaje: {$mensaje_limpio}";

    $mail->send();

    echo json_encode(["ok" => true, "message" => "Consulta enviada correctamente."]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "ok" => false,
        "error" => "No se pudo enviar el mail. Detalle: " . $mail->ErrorInfo
    ]);
}
