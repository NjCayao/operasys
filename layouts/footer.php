<?php
/**
 * OperaSys - Layout Footer
 * Archivo: layouts/footer.php
 * Descripción: Footer y scripts (reutilizable)
 */
?>

    <!-- Main Footer -->
    <footer class="main-footer">
        <strong>OperaSys &copy; <?php echo date('Y'); ?></strong> - Sistema de Reportes de Operación
        <div class="float-right d-none d-sm-inline-block">
            <b>Versión</b> <?php echo SITE_VERSION; ?>
        </div>
    </footer>
</div>
<!-- ./wrapper -->

<!-- jQuery LOCAL -->
<script src="<?php echo $base_path; ?>vendor/adminlte/plugins/jquery/jquery.min.js"></script>
<!-- Bootstrap 4 LOCAL -->
<script src="<?php echo $base_path; ?>vendor/adminlte/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>

<?php if (isset($use_datatables) && $use_datatables): ?>
<!-- DataTables LOCAL -->
<script src="<?php echo $base_path; ?>vendor/adminlte/plugins/datatables/jquery.dataTables.min.js"></script>
<script src="<?php echo $base_path; ?>vendor/adminlte/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>
<script src="<?php echo $base_path; ?>vendor/adminlte/plugins/datatables-responsive/js/dataTables.responsive.min.js"></script>
<script src="<?php echo $base_path; ?>vendor/adminlte/plugins/datatables-responsive/js/responsive.bootstrap4.min.js"></script>
<?php endif; ?>

<?php if (isset($use_chart) && $use_chart): ?>
<!-- Chart.js LOCAL -->
<script src="<?php echo $base_path; ?>vendor/adminlte/plugins/chart.js/Chart.min.js"></script>
<?php endif; ?>

<?php if (isset($use_sweetalert) && $use_sweetalert): ?>
<!-- SweetAlert2 LOCAL -->
<script src="<?php echo $base_path; ?>vendor/adminlte/plugins/sweetalert2/sweetalert2.all.min.js"></script>
<?php endif; ?>

<!-- AdminLTE App LOCAL -->
<script src="<?php echo $base_path; ?>vendor/adminlte/dist/js/adminlte.min.js"></script>

<!-- Offline Support -->
<script src="<?php echo $base_path; ?>assets/js/offline.js"></script>

<?php if (isset($extra_js)): ?>
<!-- JavaScript Adicional -->
<?php echo $extra_js; ?>
<?php endif; ?>

<?php if (isset($custom_js_file)): ?>
<!-- JavaScript Personalizado -->
<script src="<?php echo $base_path . $custom_js_file; ?>"></script>
<?php endif; ?>

</body>
</html>