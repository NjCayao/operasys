<?php
/**
 * Script de diagnóstico de login
 * Archivo: test_login.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔍 Diagnóstico de Login OperaSys</h1>";
echo "<hr>";

// 1. Verificar conexión a BD
echo "<h2>1. Conexión a Base de Datos</h2>";
try {
    require_once 'config/database.php';
    echo "✅ <strong style='color:green'>Conexión exitosa</strong><br>";
    echo "Base de datos: operasys<br>";
} catch (Exception $e) {
    echo "❌ <strong style='color:red'>Error de conexión:</strong> " . $e->getMessage() . "<br>";
    exit;
}

echo "<hr>";

// 2. Verificar que existe la tabla usuarios
echo "<h2>2. Tabla 'usuarios'</h2>";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios");
    $total = $stmt->fetch()['total'];
    echo "✅ <strong style='color:green'>Tabla existe</strong><br>";
    echo "Total de usuarios: <strong>$total</strong><br>";
} catch (Exception $e) {
    echo "❌ <strong style='color:red'>Error:</strong> " . $e->getMessage() . "<br>";
    exit;
}

echo "<hr>";

// 3. Verificar usuario admin
echo "<h2>3. Usuario Admin (DNI: 12345678)</h2>";
$dni = '12345678';
$password_correcta = '12345678';

try {
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE dni = ?");
    $stmt->execute([$dni]);
    $usuario = $stmt->fetch();
    
    if ($usuario) {
        echo "✅ <strong style='color:green'>Usuario encontrado</strong><br>";
        echo "<table border='1' cellpadding='5' style='border-collapse:collapse'>";
        echo "<tr><th>Campo</th><th>Valor</th></tr>";
        echo "<tr><td>ID</td><td>{$usuario['id']}</td></tr>";
        echo "<tr><td>Nombre</td><td>{$usuario['nombre_completo']}</td></tr>";
        echo "<tr><td>DNI</td><td>{$usuario['dni']}</td></tr>";
        echo "<tr><td>Cargo</td><td>{$usuario['cargo']}</td></tr>";
        echo "<tr><td>Rol</td><td>{$usuario['rol']}</td></tr>";
        echo "<tr><td>Estado</td><td>" . ($usuario['estado'] ? '✅ Activo' : '❌ Inactivo') . "</td></tr>";
        echo "<tr><td>Password Hash</td><td><code style='font-size:10px'>{$usuario['password']}</code></td></tr>";
        echo "</table>";
    } else {
        echo "❌ <strong style='color:red'>Usuario NO encontrado</strong><br>";
        echo "El DNI '12345678' no existe en la base de datos<br>";
        exit;
    }
} catch (Exception $e) {
    echo "❌ <strong style='color:red'>Error:</strong> " . $e->getMessage() . "<br>";
    exit;
}

echo "<hr>";

// 4. Verificar password
echo "<h2>4. Verificación de Contraseña</h2>";
echo "Intentando verificar la contraseña: <strong>12345678</strong><br><br>";

$hash_en_bd = $usuario['password'];
echo "Hash en BD: <code style='font-size:10px'>$hash_en_bd</code><br><br>";

// Verificar con password_verify
$resultado = password_verify($password_correcta, $hash_en_bd);

if ($resultado) {
    echo "✅ <strong style='color:green; font-size:20px'>CONTRASEÑA CORRECTA</strong><br>";
    echo "La función password_verify() funciona correctamente<br>";
} else {
    echo "❌ <strong style='color:red; font-size:20px'>CONTRASEÑA INCORRECTA</strong><br>";
    echo "El hash NO coincide con la contraseña '12345678'<br><br>";
    
    // Generar nuevo hash
    echo "<h3>🔧 Solución:</h3>";
    $nuevo_hash = password_hash($password_correcta, PASSWORD_BCRYPT);
    echo "Nuevo hash generado: <code style='font-size:10px'>$nuevo_hash</code><br><br>";
    
    echo "<strong>Ejecuta esto en phpMyAdmin:</strong><br>";
    echo "<textarea rows='4' cols='100' style='font-family:monospace'>UPDATE usuarios 
SET password = '$nuevo_hash' 
WHERE dni = '12345678';</textarea><br>";
    
    echo "<br><button onclick='copiarSQL()'>📋 Copiar SQL</button>";
    echo "<script>
    function copiarSQL() {
        var textarea = document.querySelector('textarea');
        textarea.select();
        document.execCommand('copy');
        alert('SQL copiado al portapapeles');
    }
    </script>";
}

echo "<hr>";

// 5. Verificar estado del usuario
echo "<h2>5. Estado del Usuario</h2>";
if ($usuario['estado'] == 1) {
    echo "✅ <strong style='color:green'>Usuario ACTIVO</strong><br>";
    echo "Puede iniciar sesión<br>";
} else {
    echo "❌ <strong style='color:red'>Usuario INACTIVO</strong><br>";
    echo "El usuario está deshabilitado<br><br>";
    echo "<strong>Solución:</strong><br>";
    echo "<textarea rows='2' cols='60'>UPDATE usuarios SET estado = 1 WHERE dni = '12345678';</textarea><br>";
}

echo "<hr>";

// 6. Test de password_verify con ejemplos
echo "<h2>6. Test de password_verify()</h2>";
echo "Verificando que la función PHP funcione correctamente...<br><br>";

$test_password = "12345678";
$test_hash = password_hash($test_password, PASSWORD_BCRYPT);

echo "Password de prueba: <strong>$test_password</strong><br>";
echo "Hash generado: <code style='font-size:10px'>$test_hash</code><br>";

if (password_verify($test_password, $test_hash)) {
    echo "✅ <strong style='color:green'>password_verify() funciona correctamente</strong><br>";
} else {
    echo "❌ <strong style='color:red'>password_verify() NO funciona</strong><br>";
    echo "Problema con PHP o extensión bcrypt<br>";
}

echo "<hr>";

// 7. Resumen
echo "<h2>📋 Resumen</h2>";
echo "<ol>";
echo "<li>Conexión BD: ✅</li>";
echo "<li>Tabla usuarios: ✅</li>";
echo "<li>Usuario existe: " . ($usuario ? '✅' : '❌') . "</li>";
echo "<li>Estado activo: " . ($usuario['estado'] ? '✅' : '❌') . "</li>";
echo "<li>Password correcta: " . ($resultado ? '✅' : '❌') . "</li>";
echo "<li>password_verify() funciona: ✅</li>";
echo "</ol>";

if ($resultado && $usuario['estado'] == 1) {
    echo "<br><div style='background:green;color:white;padding:20px;font-size:18px;text-align:center'>";
    echo "🎉 <strong>TODO ESTÁ BIEN</strong> - El login debería funcionar";
    echo "</div>";
    
    echo "<br><h3>Si aún no funciona, el problema está en:</h3>";
    echo "<ul>";
    echo "<li><code>api/auth.php</code> - Revisa la línea de password_verify</li>";
    echo "<li><code>assets/js/app.js</code> - Revisa el envío del formulario</li>";
    echo "<li>Sesiones PHP - Verifica que session_start() funcione</li>";
    echo "</ul>";
} else {
    echo "<br><div style='background:red;color:white;padding:20px;font-size:18px;text-align:center'>";
    echo "⚠️ HAY PROBLEMAS - Sigue las soluciones arriba";
    echo "</div>";
}

echo "<hr>";
echo "<p><a href='modules/auth/login.php'>← Volver al Login</a></p>";
?>
