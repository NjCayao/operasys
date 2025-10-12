/**
 * OperaSys - JavaScript de Reportes
 * Archivo: assets/js/reportes.js
 * Descripción: Crear y listar reportes con geolocalización
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // ============================================
    // FORMULARIO CREAR REPORTE
    // ============================================
    const formCrear = document.getElementById('formCrearReporte');
    
    if (formCrear) {
        
        // Cambio de categoría → Cargar equipos
        const selectCategoria = document.getElementById('categoria_equipo');
        const selectEquipo = document.getElementById('equipo_id');
        
        selectCategoria.addEventListener('change', async function() {
            const categoria = this.value;
            
            if (!categoria) {
                selectEquipo.disabled = true;
                selectEquipo.innerHTML = '<option value="">Primero seleccione una categoría</option>';
                return;
            }
            
            // Mostrar loading
            selectEquipo.disabled = true;
            selectEquipo.innerHTML = '<option value="">Cargando equipos...</option>';
            
            try {
                // Buscar equipos por categoría (función del módulo 3)
                const response = await fetch(`../../api/equipos.php?action=buscar_por_categoria&categoria=${encodeURIComponent(categoria)}`);
                const data = await response.json();
                
                if (data.success && data.equipos.length > 0) {
                    selectEquipo.innerHTML = '<option value="">Seleccionar equipo</option>';
                    
                    data.equipos.forEach(equipo => {
                        const option = document.createElement('option');
                        option.value = equipo.id;
                        option.textContent = `${equipo.codigo} - ${equipo.descripcion || 'Sin descripción'}`;
                        selectEquipo.appendChild(option);
                    });
                    
                    selectEquipo.disabled = false;
                } else {
                    selectEquipo.innerHTML = '<option value="">No hay equipos disponibles en esta categoría</option>';
                }
                
            } catch (error) {
                console.error('Error al cargar equipos:', error);
                selectEquipo.innerHTML = '<option value="">Error al cargar equipos</option>';
            }
        });
        
        // Botón obtener ubicación GPS
        const btnUbicacion = document.getElementById('btnObtenerUbicacion');
        const inputUbicacion = document.getElementById('ubicacion');
        
        if (btnUbicacion) {
            btnUbicacion.addEventListener('click', function() {
                
                if (!navigator.geolocation) {
                    alert('Tu navegador no soporta geolocalización');
                    return;
                }
                
                btnUbicacion.disabled = true;
                btnUbicacion.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Obteniendo...';
                
                navigator.geolocation.getCurrentPosition(
                    function(position) {
                        const lat = position.coords.latitude.toFixed(6);
                        const lng = position.coords.longitude.toFixed(6);
                        
                        inputUbicacion.value = `${lat}, ${lng}`;
                        
                        btnUbicacion.disabled = false;
                        btnUbicacion.innerHTML = '<i class="fas fa-check"></i> ¡Obtenida!';
                        btnUbicacion.classList.remove('btn-info');
                        btnUbicacion.classList.add('btn-success');
                        
                        console.log('✓ Ubicación GPS obtenida:', lat, lng);
                    },
                    function(error) {
                        console.error('Error GPS:', error);
                        alert('No se pudo obtener la ubicación. Verifica los permisos del navegador.');
                        
                        btnUbicacion.disabled = false;
                        btnUbicacion.innerHTML = '<i class="fas fa-location-arrow"></i> Reintentar';
                    }
                );
            });
        }
        
        // Submit del formulario
        formCrear.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const btnSubmit = this.querySelector('button[type="submit"]');
            const alertMessage = document.getElementById('alertMessage');
            const alertText = document.getElementById('alertText');
            
            // Validar que haya equipo seleccionado
            if (!selectEquipo.value) {
                alertMessage.classList.remove('alert-success');
                alertMessage.classList.add('alert-danger');
                alertText.textContent = 'Por favor seleccione un equipo';
                alertMessage.style.display = 'block';
                return;
            }
            
            // Deshabilitar botón
            btnSubmit.disabled = true;
            btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
            alertMessage.style.display = 'none';
            
            try {
                const formData = new FormData(this);
                formData.append('action', 'crear');
                
                // Verificar si hay conexión
                if (!navigator.onLine) {
                    // MODO OFFLINE: Guardar en IndexedDB
                    console.log('⚠ Modo offline - Guardando localmente');
                    
                    await window.OperaSysOffline.guardarReporteOffline(formData);
                    
                    btnSubmit.innerHTML = '<i class="fas fa-check"></i> ¡Guardado Offline!';
                    btnSubmit.classList.remove('btn-primary');
                    btnSubmit.classList.add('btn-warning');
                    
                    alertMessage.classList.remove('alert-danger');
                    alertMessage.classList.add('alert-warning');
                    alertText.innerHTML = '<strong>Guardado offline.</strong> El reporte se sincronizará automáticamente cuando tengas conexión.';
                    alertMessage.style.display = 'block';
                    
                    setTimeout(() => {
                        window.location.href = 'listar.php';
                    }, 2000);
                    
                } else {
                    // MODO ONLINE: Enviar al servidor
                    const response = await fetch('../../api/reportes.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        btnSubmit.innerHTML = '<i class="fas fa-check"></i> ¡Guardado!';
                        btnSubmit.classList.remove('btn-primary');
                        btnSubmit.classList.add('btn-success');
                        
                        alertMessage.classList.remove('alert-danger');
                        alertMessage.classList.add('alert-success');
                        alertText.textContent = data.message;
                        alertMessage.style.display = 'block';
                        
                        setTimeout(() => {
                            window.location.href = 'listar.php';
                        }, 2000);
                    } else {
                        throw new Error(data.message);
                    }
                }
                
            } catch (error) {
                alertMessage.classList.remove('alert-success');
                alertMessage.classList.add('alert-danger');
                alertText.textContent = error.message;
                alertMessage.style.display = 'block';
                
                btnSubmit.disabled = false;
                btnSubmit.innerHTML = '<i class="fas fa-save"></i> Guardar Reporte';
            }
        });
    }
    
    // ============================================
    // TABLA DE REPORTES (LISTADO)
    // ============================================
    if (document.getElementById('tablaReportes')) {
        
        // Cargar estadísticas
        cargarEstadisticas();
        
        // Inicializar DataTable
        const tabla = $('#tablaReportes').DataTable({
            ajax: {
                url: '../../api/reportes.php?action=listar',
                dataSrc: 'data'
            },
            columns: [
                { title: 'ID' },
                { title: 'Fecha' },
                { title: 'Equipo' },
                { title: 'Hora Inicio' },
                { title: 'Hora Fin' },
                { title: 'Horas' },
                { title: 'Actividad' },
                { title: 'Estado' },
                { title: 'Acciones', orderable: false, searchable: false }
            ],
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
            },
            order: [[0, 'desc']],
            responsive: true,
            pageLength: 25
        });
        
        console.log('✓ DataTable de reportes inicializado');
    }
    
});

// ============================================
// FUNCIÓN: CARGAR ESTADÍSTICAS
// ============================================
async function cargarEstadisticas() {
    try {
        const response = await fetch('../../api/reportes.php?action=estadisticas');
        const data = await response.json();
        
        if (data.success) {
            const stats = data.estadisticas;
            
            document.getElementById('totalReportes').textContent = stats.total_reportes;
            document.getElementById('reportesHoy').textContent = stats.reportes_hoy;
            document.getElementById('horasTrabajadas').textContent = stats.horas_mes;
            document.getElementById('pendientesSinc').textContent = stats.pendientes_sinc;
            
            // Actualizar indicador de sincronización
            const indicador = document.getElementById('estadoSincronizacion');
            if (stats.pendientes_sinc > 0) {
                indicador.innerHTML = '<i class="fas fa-sync text-warning"></i> <span class="d-none d-sm-inline">' + stats.pendientes_sinc + ' pendientes</span>';
            }
        }
    } catch (error) {
        console.error('Error al cargar estadísticas:', error);
    }
}

// ============================================
// FUNCIÓN GLOBAL: VER DETALLE DE REPORTE
// ============================================
async function verDetalleReporte(id) {
    try {
        const response = await fetch(`../../api/reportes.php?action=detalle&id=${id}`);
        const data = await response.json();
        
        if (data.success) {
            const r = data.reporte;
            
            // Crear modal con SweetAlert2
            Swal.fire({
                title: 'Reporte #' + r.id,
                html: `
                    <div class="text-left">
                        <p><strong>Fecha:</strong> ${formatearFecha(r.fecha)}</p>
                        <p><strong>Operador:</strong> ${r.operador} (${r.operador_cargo})</p>
                        <p><strong>Equipo:</strong> ${r.equipo_categoria} - ${r.equipo_codigo}</p>
                        <p><strong>Horario:</strong> ${r.hora_inicio} - ${r.hora_fin || 'En curso'}</p>
                        <p><strong>Horas trabajadas:</strong> ${r.horas_trabajadas ? r.horas_trabajadas + ' hrs' : '-'}</p>
                        <hr>
                        <p><strong>Actividad:</strong></p>
                        <p>${r.actividad}</p>
                        ${r.observaciones ? '<p><strong>Observaciones:</strong></p><p>' + r.observaciones + '</p>' : ''}
                        ${r.ubicacion ? '<p><strong>Ubicación GPS:</strong> ' + r.ubicacion + '</p>' : ''}
                    </div>
                `,
                icon: 'info',
                width: 600,
                confirmButtonText: 'Cerrar'
            });
        } else {
            throw new Error(data.message);
        }
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.message
        });
    }
}

// ============================================
// FUNCIÓN GLOBAL: DESCARGAR PDF
// ============================================
function descargarPDF(id) {
    // Verificar que el ID sea válido
    if (!id) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'ID de reporte no válido'
        });
        return;
    }
    
    // Mostrar indicador de carga
    Swal.fire({
        title: 'Generando PDF...',
        html: 'Por favor espera mientras se genera el documento',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    // Descargar PDF
    window.location.href = '../../api/pdf.php?id=' + id;
    
    // Cerrar el indicador después de 2 segundos
    setTimeout(() => {
        Swal.close();
    }, 2000);
}

// ============================================
// FUNCIÓN AUXILIAR: FORMATEAR FECHA
// ============================================
function formatearFecha(fecha) {
    const opciones = { year: 'numeric', month: 'long', day: 'numeric' };
    return new Date(fecha + 'T00:00:00').toLocaleDateString('es-ES', opciones);
}

console.log('✓ Script de reportes cargado');
