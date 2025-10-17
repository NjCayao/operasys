/**
 * OperaSys - Gestión de Motivos HP
 * Archivo: assets/js/motivos_hp.js
 * Versión: 3.0 - CORREGIDO
 */

let tablaMotivos;
const rolUsuario = $('#tablaMotivos').data('rol');

$(document).ready(function() {
    
    // Inicializar DataTable
    tablaMotivos = $('#tablaMotivos').DataTable({
        processing: true,
        ajax: {
            url: '../../api/motivos_hp.php?action=listar',
            type: 'GET',
            dataSrc: function(json) {
                if (!json.success) {
                    Swal.fire('Error', json.message || 'Error al cargar motivos', 'error');
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
            { data: 3 }, // Categoría
            { data: 4 }, // Tipo (Justificada/No justificada)
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

    // Botón Nuevo Motivo
    $('#btnNuevoMotivo').on('click', function() {
        abrirModalNuevo();
    });

    // Submit del formulario
    $('#formMotivo').on('submit', function(e) {
        e.preventDefault();
        guardarMotivo();
    });
});

// Abrir modal para crear
function abrirModalNuevo() {
    $('#motivo_id').val('');
    $('#formMotivo')[0].reset();
    $('#categoria_parada').val('operacional');
    $('#es_justificada').prop('checked', true);
    $('#tituloModal').text('Nuevo Motivo HP');
    $('#grupoEstado').hide();
    $('#alertModal').hide();
    $('#modalMotivo').modal('show');
}

// Editar motivo
function editarMotivo(id) {
    $.ajax({
        url: '../../api/motivos_hp.php',
        type: 'GET',
        data: { action: 'obtener', id: id },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                const mot = response.motivo;
                
                // CAMPO CÓDIGO INTEGRADO AQUÍ
                $('#motivo_id').val(mot.id);
                $('#codigo').val(mot.codigo || '');  // <-- AGREGADO
                $('#nombre').val(mot.nombre);
                $('#descripcion').val(mot.descripcion || '');
                $('#categoria_parada').val(mot.categoria_parada);
                $('#orden_mostrar').val(mot.orden_mostrar);
                $('#es_frecuente').prop('checked', mot.es_frecuente == 1);
                $('#es_justificada').prop('checked', mot.es_justificada == 1);
                $('#requiere_observacion').prop('checked', mot.requiere_observacion == 1);
                $('#estado').val(mot.estado);
                
                $('#tituloModal').text('Editar Motivo HP');
                $('#grupoEstado').show();
                $('#alertModal').hide();
                $('#modalMotivo').modal('show');
            } else {
                Swal.fire('Error', response.message, 'error');
            }
        },
        error: function() {
            Swal.fire('Error', 'Error al obtener motivo', 'error');
        }
    });
}

// Guardar motivo
function guardarMotivo() {
    const motivoId = $('#motivo_id').val();
    
    // CAMPO CÓDIGO INTEGRADO EN EL FORMDATA
    const formData = {
        action: motivoId ? 'actualizar' : 'crear',
        id: motivoId,
        codigo: $('#codigo').val().trim(),  // <-- AGREGADO
        nombre: $('#nombre').val().trim(),
        descripcion: $('#descripcion').val().trim(),
        categoria_parada: $('#categoria_parada').val(),
        orden_mostrar: $('#orden_mostrar').val(),
        es_frecuente: $('#es_frecuente').is(':checked') ? 1 : 0,
        es_justificada: $('#es_justificada').is(':checked') ? 1 : 0,
        requiere_observacion: $('#requiere_observacion').is(':checked') ? 1 : 0,
        estado: $('#estado').val()
    };

    // Validar
    if (!formData.nombre) {
        mostrarAlertaModal('El nombre es obligatorio', 'warning');
        return;
    }

    $.ajax({
        url: '../../api/motivos_hp.php',
        type: 'POST',
        data: formData,
        dataType: 'json',
        beforeSend: function() {
            $('#formMotivo button[type="submit"]').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Guardando...');
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
                    $('#modalMotivo').modal('hide');
                    tablaMotivos.ajax.reload(null, false);
                });
            } else {
                mostrarAlertaModal(response.message, 'danger');
            }
        },
        error: function() {
            mostrarAlertaModal('Error al comunicarse con el servidor', 'danger');
        },
        complete: function() {
            $('#formMotivo button[type="submit"]').prop('disabled', false).html('<i class="fas fa-save"></i> Guardar');
        }
    });
}

// Eliminar motivo
function eliminarMotivo(id, nombre) {
    Swal.fire({
        title: '¿Eliminar motivo?',
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
                url: '../../api/motivos_hp.php',
                type: 'POST',
                data: { action: 'eliminar', id: id },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        Swal.fire('Eliminado', response.message, 'success');
                        tablaMotivos.ajax.reload(null, false);
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