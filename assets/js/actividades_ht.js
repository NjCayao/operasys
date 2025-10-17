/**
 * OperaSys - Gestión de Actividades HT
 * Archivo: assets/js/actividades_ht.js
 * Versión: 3.0
 */

let tablaActividades;
const rolUsuario = $('#tablaActividades').data('rol');

$(document).ready(function() {
    
    // Inicializar DataTable
    tablaActividades = $('#tablaActividades').DataTable({
        processing: true,
        ajax: {
            url: '../../api/actividades_ht.php?action=listar',
            type: 'GET',
            dataSrc: function(json) {
                if (!json.success) {
                    Swal.fire('Error', json.message || 'Error al cargar actividades', 'error');
                    return [];
                }
                return json.data;
            },
            error: function(xhr, error, thrown) {
                Swal.fire('Error', 'Error al cargar datos del servidor', 'error');
            }
        },
        columns: [
            { data: 0 }, // ID
            { data: 1 }, // Código
            { data: 2 }, // Nombre (con icono frecuente)
            { data: 3 }, // Descripción
            { data: 4 }, // Rendimiento
            { data: 5 }, // Orden
            { data: 6 }, // Estado
            { data: 7 }, // Fecha
            { data: 8 }  // Acciones
        ],
        order: [[5, 'asc']], // Ordenar por orden
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
        },
        responsive: true,
        autoWidth: false
    });

    // Botón Nueva Actividad
    $('#btnNuevaActividad').on('click', function() {
        abrirModalNuevo();
    });

    // Submit del formulario
    $('#formActividad').on('submit', function(e) {
        e.preventDefault();
        guardarActividad();
    });
});

// Abrir modal para crear
function abrirModalNuevo() {
    $('#actividad_id').val('');
    $('#formActividad')[0].reset();
    $('#tituloModal').text('Nueva Actividad HT');
    $('#grupoEstado').hide();
    $('#alertModal').hide();
    $('#modalActividad').modal('show');
}

// Editar actividad
function editarActividad(id) {
    $.ajax({
        url: '../../api/actividades_ht.php',
        type: 'GET',
        data: { action: 'obtener', id: id },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                const act = response.actividad;
                
                $('#actividad_id').val(act.id);
                $('#codigo').val(act.codigo);
                $('#nombre').val(act.nombre);
                $('#descripcion').val(act.descripcion);
                $('#rendimiento_referencial').val(act.rendimiento_referencial);
                $('#orden_mostrar').val(act.orden_mostrar);
                $('#es_frecuente').prop('checked', act.es_frecuente == 1);
                $('#estado').val(act.estado);
                
                $('#tituloModal').text('Editar Actividad HT');
                $('#grupoEstado').show();
                $('#alertModal').hide();
                $('#modalActividad').modal('show');
            } else {
                Swal.fire('Error', response.message, 'error');
            }
        },
        error: function() {
            Swal.fire('Error', 'Error al obtener actividad', 'error');
        }
    });
}

// Guardar actividad
function guardarActividad() {
    const actividadId = $('#actividad_id').val();
    const formData = {
        action: actividadId ? 'actualizar' : 'crear',
        id: actividadId,
        codigo: $('#codigo').val().trim(),
        nombre: $('#nombre').val().trim(),
        descripcion: $('#descripcion').val().trim(),
        rendimiento_referencial: $('#rendimiento_referencial').val().trim(),
        orden_mostrar: $('#orden_mostrar').val(),
        es_frecuente: $('#es_frecuente').is(':checked') ? 1 : 0,
        estado: $('#estado').val()
    };

    // Validar
    if (!formData.nombre) {
        mostrarAlertaModal('El nombre es obligatorio', 'warning');
        return;
    }

    $.ajax({
        url: '../../api/actividades_ht.php',
        type: 'POST',
        data: formData,
        dataType: 'json',
        beforeSend: function() {
            $('#formActividad button[type="submit"]').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Guardando...');
        },
        success: function(response) {
            if (response.success) {
                Swal.fire({
                    icon: 'success',
                    title: '¡Éxito!',
                    text: response.message,
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    $('#modalActividad').modal('hide');
                    tablaActividades.ajax.reload(null, false);
                });
            } else {
                mostrarAlertaModal(response.message, 'danger');
            }
        },
        error: function() {
            mostrarAlertaModal('Error al comunicarse con el servidor', 'danger');
        },
        complete: function() {
            $('#formActividad button[type="submit"]').prop('disabled', false).html('<i class="fas fa-save"></i> Guardar');
        }
    });
}

// Eliminar actividad
function eliminarActividad(id, nombre) {
    Swal.fire({
        title: '¿Eliminar actividad?',
        html: `<p>¿Está seguro de eliminar:<br><strong>${nombre}</strong>?</p>
               <p class="text-muted small">Si tiene reportes asociados, solo se desactivará.</p>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '../../api/actividades_ht.php',
                type: 'POST',
                data: { action: 'eliminar', id: id },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        Swal.fire('Eliminado', response.message, 'success');
                        tablaActividades.ajax.reload(null, false);
                    } else {
                        Swal.fire('Error', response.message, 'error');
                    }
                },
                error: function() {
                    Swal.fire('Error', 'Error al eliminar', 'error');
                }
            });
        }
    });
}

// Mostrar alerta en modal
function mostrarAlertaModal(mensaje, tipo) {
    $('#alertModal')
        .removeClass('alert-success alert-danger alert-warning alert-info')
        .addClass('alert-' + tipo)
        .html('<i class="icon fas fa-exclamation-triangle"></i> ' + mensaje)
        .fadeIn();
}