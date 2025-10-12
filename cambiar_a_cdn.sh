#!/bin/bash
# Script para cambiar AdminLTE LOCAL por CDN en todos los archivos PHP

echo "=== Cambiando AdminLTE LOCAL a CDN ==="

# Directorio del proyecto
DIR="/home/claude/operasys"

# Encontrar todos los archivos PHP
find "$DIR/modules" -name "*.php" -type f | while read file; do
    echo "Procesando: $file"
    
    # Hacer backup
    cp "$file" "$file.bak"
    
    # Cambiar CSS
    sed -i 's|../../vendor/adminlte/dist/css/adminlte.min.css|https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css|g' "$file"
    sed -i 's|../../vendor/adminlte/plugins/fontawesome-free/css/all.min.css|https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css|g' "$file"
    sed -i 's|../../vendor/adminlte/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css|https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap4.min.css|g' "$file"
    sed -i 's|../../vendor/adminlte/plugins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css|https://cdnjs.cloudflare.com/ajax/libs/tempusdominus-bootstrap-4/5.39.0/css/tempusdominus-bootstrap-4.min.css|g' "$file"
    
    # Cambiar JS
    sed -i 's|../../vendor/adminlte/plugins/jquery/jquery.min.js|https://code.jquery.com/jquery-3.7.1.min.js|g' "$file"
    sed -i 's|../../vendor/adminlte/plugins/bootstrap/js/bootstrap.bundle.min.js|https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js|g' "$file"
    sed -i 's|../../vendor/adminlte/dist/js/adminlte.min.js|https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js|g' "$file"
    sed -i 's|../../vendor/adminlte/plugins/datatables/jquery.dataTables.min.js|https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js|g' "$file"
    sed -i 's|../../vendor/adminlte/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js|https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap4.min.js|g' "$file"
    sed -i 's|../../vendor/adminlte/plugins/sweetalert2/sweetalert2.min.js|https://cdn.jsdelivr.net/npm/sweetalert2@11|g' "$file"
    sed -i 's|../../vendor/adminlte/plugins/moment/moment.min.js|https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js|g' "$file"
    sed -i 's|../../vendor/adminlte/plugins/chart.js/Chart.min.js|https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js|g' "$file"
    
    # Cambiar comentarios LOCAL a CDN
    sed -i 's|<!-- AdminLTE CSS LOCAL -->|<!-- AdminLTE CSS CDN -->|g' "$file"
    sed -i 's|<!-- Font Awesome LOCAL -->|<!-- Font Awesome CDN -->|g' "$file"
    sed -i 's|<!-- DataTables LOCAL -->|<!-- DataTables CDN -->|g' "$file"
    sed -i 's|<!-- jQuery LOCAL -->|<!-- jQuery CDN -->|g' "$file"
    sed -i 's|<!-- Bootstrap 4 LOCAL -->|<!-- Bootstrap 4 CDN -->|g' "$file"
    sed -i 's|<!-- AdminLTE App LOCAL -->|<!-- AdminLTE App CDN -->|g' "$file"
    sed -i 's|<!-- SweetAlert2 LOCAL -->|<!-- SweetAlert2 CDN -->|g' "$file"
    sed -i 's|<!-- Moment.js LOCAL -->|<!-- Moment.js CDN -->|g' "$file"
    sed -i 's|<!-- Chart.js LOCAL -->|<!-- Chart.js CDN -->|g' "$file"
    
done

echo "=== Conversión completada ==="
echo "Los archivos originales están respaldados con extensión .bak"
