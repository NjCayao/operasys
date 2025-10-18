/**
 * OperaSys - JavaScript para Contratas V3.1
 * Archivo: assets/js/contratas.js
 */

$(document).ready(function() {
    
    // ============================================
    // DATATABLE - LISTADO DE CONTRATAS
    // ============================================
    if ($('#tablaContratas').length) {
        $('#tablaContratas').DataTable({
            ajax: {
                url: '../../api/contratas.php?action=listar',
                dataSrc: 'data'
            },
            columns: [
                { data: 0 }, // ID
                { data: 1 }, // Razón Social
                { data: 2 }, // RUC
                { data: 3 }, // Contacto
                { data: 4 }, // Teléfono
                { data: 5 }, // Equipos
                { data: 6 }, // Vigencia
                { data: 7 }, // Estado
                { data: 8 }  // Acciones
            ],
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
            },
            responsive: true,
            autoWidth: false,
            order: [[1, 'asc']]
        });
    }
    
    // ============================================
    // FORMULARIO AGREGAR CONTRATA
    // ============================================
    $('#formAgregarContrata').on('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        formData.append('action', 'crear');
        
        // Obtener la ruta base desde el DOM
        const basePath = $('base').attr('href') || window.BASE_PATH || '../../';
        
        $.ajax({
            url: basePath + 'api/contratas.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Éxito!',
                        text: response.message,
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        window.location.href = 'listar.php';
                    });
                } else {
                    mostrarAlerta('danger', response.message);
                }
            },
            error: function() {
                mostrarAlerta('danger', 'Error al conectar con el servidor');
            }
        });
    });
    
    // ============================================
    // FORMULARIO EDITAR CONTRATA
    // ============================================
    $('#formEditarContrata').on('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        formData.append('action', 'actualizar');
        
        $.ajax({
            url: '../../api/contratas.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Actualizado!',
                        text: response.message,
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        window.location.href = 'listar.php';
                    });
                } else {
                    mostrarAlerta('danger', response.message);
                }
            },
            error: function() {
                mostrarAlerta('danger', 'Error al conectar con el servidor');
            }
        });
    });
    
    // ============================================
    // VALIDACIÓN DE FECHAS
    // ============================================
    $('#fecha_fin_contrato').on('change', function() {
        const fechaInicio = $('#fecha_inicio_contrato').val();
        const fechaFin = $(this).val();
        
        if (fechaInicio && fechaFin) {
            if (new Date(fechaFin) < new Date(fechaInicio)) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Fecha inválida',
                    text: 'La fecha de fin debe ser posterior a la fecha de inicio'
                });
                $(this).val('');
            }
        }
    });
    
});

// ============================================
// VER DETALLE DE CONTRATA
// ============================================
function verContrata(id) {
    $('#contenidoModalContrata').html(`
        <div class="text-center">
            <i class="fas fa-spinner fa-spin fa-3x"></i>
            <p>Cargando...</p>
        </div>
    `);
    
    $('#modalVerContrata').modal('show');
    
    $.ajax({
        url: '../../api/contratas.php',
        type: 'GET',
        data: { action: 'obtener', id: id },
        success: function(response) {
            if (response.success) {
                const c = response.contrata;
                
                // Obtener equipos de la contrata
                $.ajax({
                    url: '../../api/contratas.php',
                    type: 'GET',
                    data: { action: 'obtener_equipos', id: id },
                    success: function(responseEquipos) {
                        let equiposHTML = '<p class="text-muted">Sin equipos asignados</p>';
                        
                        if (responseEquipos.success && responseEquipos.equipos.length > 0) {
                            equiposHTML = '<div class="table-responsive"><table class="table table-sm table-bordered"><thead><tr><th>Código</th><th>Categoría</th><th>Tarifa</th><th>Estado</th></tr></thead><tbody>';
                            
                            responseEquipos.equipos.forEach(eq => {
                                const tarifa = eq.tarifa_alquiler 
                                    ? `S/ ${parseFloat(eq.tarifa_alquiler).toFixed(2)}/${eq.tipo_tarifa}` 
                                    : '-';
                                const estadoBadge = eq.estado == 1 
                                    ? '<span class="badge badge-success">Activo</span>' 
                                    : '<span class="badge badge-secondary">Inactivo</span>';
                                
                                equiposHTML += `<tr>
                                    <td><strong>${eq.codigo}</strong></td>
                                    <td>${eq.categoria}</td>
                                    <td>${tarifa}</td>
                                    <td>${estadoBadge}</td>
                                </tr>`;
                            });
                            
                            equiposHTML += '</tbody></table></div>';
                        }
                        
                        const vigencia = (c.fecha_inicio_contrato && c.fecha_fin_contrato) 
                            ? `${formatearFecha(c.fecha_inicio_contrato)} - ${formatearFecha(c.fecha_fin_contrato)}` 
                            : '<span class="text-muted">No definida</span>';
                        
                        const estadoBadge = c.estado == 1 
                            ? '<span class="badge badge-success">Activo</span>' 
                            : '<span class="badge badge-danger">Inactivo</span>';
                        
                        const html = `
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong><i class="fas fa-building"></i> Razón Social:</strong><br>${c.razon_social}</p>
                                    <p><strong><i class="fas fa-id-card"></i> RUC:</strong><br>${c.ruc}</p>
                                    <p><strong><i class="fas fa-user"></i> Contacto:</strong><br>${c.contacto || '-'}</p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong><i class="fas fa-phone"></i> Teléfono:</strong><br>${c.telefono || '-'}</p>
                                    <p><strong><i class="fas fa-envelope"></i> Email:</strong><br>${c.email || '-'}</p>
                                    <p><strong><i class="fas fa-map-marker-alt"></i> Dirección:</strong><br>${c.direccion || '-'}</p>
                                </div>
                            </div>
                            <hr>
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong><i class="fas fa-calendar-alt"></i> Vigencia:</strong><br>${vigencia}</p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong><i class="fas fa-toggle-on"></i> Estado:</strong><br>${estadoBadge}</p>
                                </div>
                            </div>
                            <hr>
                            <h5><i class="fas fa-truck-monster"></i> Equipos Asignados (${c.total_equipos})</h5>
                            ${equiposHTML}
                        `;
                        
                        $('#contenidoModalContrata').html(html);
                    }
                });
            } else {
                $('#contenidoModalContrata').html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i> ${response.message}
                    </div>
                `);
            }
        },
        error: function() {
            $('#contenidoModalContrata').html(`
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i> Error al cargar datos
                </div>
            `);
        }
    });
}

// ============================================
// EDITAR CONTRATA
// ============================================
function editarContrata(id) {
    window.location.href = `editar.php?id=${id}`;
}

// ============================================
// ELIMINAR CONTRATA
// ============================================
function eliminarContrata(id, razonSocial) {
    Swal.fire({
        title: '¿Eliminar contrata?',
        html: `Se eliminará: <strong>${razonSocial}</strong><br><small>Si tiene equipos asociados, solo se desactivará</small>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '../../api/contratas.php',
                type: 'POST',
                data: { action: 'eliminar', id: id },
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: '¡Eliminado!',
                            text: response.message,
                            showConfirmButton: false,
                            timer: 1500
                        }).then(() => {
                            $('#tablaContratas').DataTable().ajax.reload();
                        });
                    } else {
                        Swal.fire('Error', response.message, 'error');
                    }
                },
                error: function() {
                    Swal.fire('Error', 'No se pudo conectar con el servidor', 'error');
                }
            });
        }
    });
}

// ============================================
// FUNCIONES AUXILIARES
// ============================================
function mostrarAlerta(tipo, mensaje) {
    const alertDiv = $('#alertMessage');
    const alertText = $('#alertText');
    
    alertDiv.removeClass('alert-success alert-danger alert-warning');
    alertDiv.addClass(`alert-${tipo}`);
    alertText.text(mensaje);
    alertDiv.fadeIn();
    
    setTimeout(() => {
        alertDiv.fadeOut();
    }, 5000);
}

function formatearFecha(fecha) {
    if (!fecha) return '-';
    const partes = fecha.split('-');
    return `${partes[2]}/${partes[1]}/${partes[0]}`;
}