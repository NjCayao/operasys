<?php
/**
 * FPDF - Librería para generación de PDFs
 * Versión simplificada para OperaSys
 * 
 * NOTA IMPORTANTE: Este es un stub básico.
 * Para producción, descargar FPDF completo desde: http://www.fpdf.org/
 * 
 * Descarga: http://www.fpdf.org/en/download.php
 * Extraer fpdf.php y la carpeta font/ en vendor/fpdf/
 */

// Verificar si FPDF ya fue incluido
if (!class_exists('FPDF')) {
    
    // MENSAJE IMPORTANTE PARA EL DESARROLLADOR
    die('
        <h1>FPDF no esta instalado</h1>
        <p>Por favor, sigue estos pasos:</p>
        <ol>
            <li>Descarga FPDF desde: <a href="http://www.fpdf.org/en/download.php" target="_blank">http://www.fpdf.org/en/download.php</a></li>
            <li>Extrae el archivo ZIP</li>
            <li>Copia el archivo <strong>fpdf.php</strong> a: <code>vendor/fpdf/fpdf.php</code></li>
            <li>Copia la carpeta <strong>font/</strong> completa a: <code>vendor/fpdf/font/</code></li>
            <li>Recarga esta pagina</li>
        </ol>
        <p><strong>Estructura esperada:</strong></p>
        <pre>
vendor/
└── fpdf/
    ├── fpdf.php
    └── font/
        ├── courier.php
        ├── helvetica.php
        ├── times.php
        └── ...
        </pre>
    ');
}
