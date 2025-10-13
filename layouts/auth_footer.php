<?php
/**
 * OperaSys - Layout Footer para Autenticación
 * Archivo: layouts/auth_footer.php
 * Descripción: Footer para login/register
 */

$base_path = isset($auth_base_path) ? $auth_base_path : '../../';
?>

<!-- jQuery LOCAL -->
<script src="<?php echo $base_path; ?>vendor/adminlte/plugins/jquery/jquery.min.js"></script>
<!-- Bootstrap 4 LOCAL -->
<script src="<?php echo $base_path; ?>vendor/adminlte/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- AdminLTE App LOCAL -->
<script src="<?php echo $base_path; ?>vendor/adminlte/dist/js/adminlte.min.js"></script>

<?php if (isset($custom_js_file)): ?>
<!-- JavaScript Personalizado -->
<script src="<?php echo $base_path . $custom_js_file; ?>"></script>
<?php endif; ?>

</body>
</html>