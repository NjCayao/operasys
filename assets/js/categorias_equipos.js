/**
 * OperaSys - JavaScript para Categorías de Equipos V3.2
 * Archivo: assets/js/categorias_equipos.js
 */

$(document).ready(function() {
    
    // ============================================
    // DATATABLE - LISTADO DE CATEGORÍAS
    // ============================================
    if ($('#tablaCategorias').length) {
        $('#tablaCategorias').DataTable({
            ajax: {
                url: '../../api/categorias_equipos.php?action=listar',
                dataSrc: 'data'
            },
            columns: [
                { data: 0 }, // ID
                { data: 1 }, // Nombre
                { data: 2 }, // Descripción
                { data: 3 }, // Consumo
                { data: 4 }, // Capacidad
                { data: 5 }, // Equipos
                { data: 6 }, // Orden
                { data: 7 }, // Estado
                { data: 8 }  // Acciones
            ],
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
            },
            responsive: true,
            autoWidth: false,
            order: [[7, 'asc'], [2, 'asc']] // Ordenar por orden y nombre
        });
    }
    
    // ============================================
    // FORMULARIO AGREGAR CATEGORÍA
    // ============================================
    $('#formAgregarCategoria').on('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        formData.append('action', 'crear');
        
        $.ajax({
            url: '../../api/categorias_equipos.php',
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
    // FORMULARIO EDITAR CATEGORÍA
    // ============================================
    $('#formEditarCategoria').on('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        formData.append('action', 'actualizar');
        
        $.ajax({
            url: '../../api/categorias_equipos.php',
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
    
});

// ============================================
// EDITAR CATEGORÍA
// ============================================
function editarCategoria(id) {
    window.location.href = `editar.php?id=${id}`;
}

// ============================================
// ELIMINAR CATEGORÍA
// ============================================
function eliminarCategoria(id, nombre) {
    Swal.fire({
        title: '¿Eliminar categoría?',
        html: `Se eliminará: <strong>${nombre}</strong><br><small>Si tiene equipos asociados, solo se desactivará</small>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '../../api/categorias_equipos.php',
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
                            $('#tablaCategorias').DataTable().ajax.reload();
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