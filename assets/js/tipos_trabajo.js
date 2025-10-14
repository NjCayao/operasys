/**
 * OperaSys - JavaScript de Tipos de Trabajo
 * Archivo: assets/js/tipos_trabajo.js
 * Descripción: CRUD completo de tipos de trabajo
 */

document.addEventListener('DOMContentLoaded', function() {
    
    console.log('✓ Script de tipos de trabajo cargado');
    
    // ============================================
    // DATATABLE
    // ============================================
    const tablaTipos = $('#tablaTipos').DataTable({
        ajax: {
            url: '../../api/tipos_trabajo.php?action=listar',
            dataSrc: 'data'
        },
        columns: [
            { title: 'ID' },
            { title: 'Nombre' },
            { title: 'Descripción' },
            { title: 'Estado' },
            { title: 'Fecha' },
            { title: 'Acciones', orderable: false, searchable: false }
        ],
        language: {
            "decimal": "",
            "emptyTable": "No hay tipos de trabajo registrados",
            "info": "Mostrando _START_ a _END_ de _TOTAL_ registros",
            "infoEmpty": "Mostrando 0 a 0 de 0 registros",
            "infoFiltered": "(filtrado de _MAX_ registros)",
            "lengthMenu": "Mostrar _MENU_ registros",
            "loadingRecords": "Cargando...",
            "processing": "Procesando...",
            "search": "Buscar:",
            "zeroRecords": "No se encontraron registros",
            "paginate": {
                "first": "Primero",
                "last": "Último",
                "next": "Siguiente",
                "previous": "Anterior"
            }
        },
        order: [[1, 'asc']], // Ordenar por nombre
        responsive: true,
        pageLength: 25
    });
    
    // ============================================
    // BOTÓN NUEVO TIPO
    // ============================================
    document.getElementById('btnNuevoTipo').addEventListener('click', function() {
        limpiarFormulario();
        $('#modalTipo').modal('show');
    });
    
    // ============================================
    // FORMULARIO CREAR/EDITAR
    // ============================================
    document.getElementById('formTipo').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const tipoId = document.getElementById('tipo_id').value;
        const action = tipoId ? 'actualizar' : 'crear';
        const alertModal = document.getElementById('alertModal');
        const btnSubmit = this.querySelector('button[type="submit"]');
        
        btnSubmit.disabled = true;
        btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
        alertModal.style.display = 'none';
        
        try {
            const formData = new FormData();
            formData.append('action', action);
            if (tipoId) formData.append('id', tipoId);
            formData.append('nombre', document.getElementById('nombre').value);
            formData.append('descripcion', document.getElementById('descripcion').value);
            if (tipoId) formData.append('estado', document.getElementById('estado').value);
            
            const response = await fetch('../../api/tipos_trabajo.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                $('#modalTipo').modal('hide');
                tablaTipos.ajax.reload();
                
                Swal.fire({
                    icon: 'success',
                    title: '¡Éxito!',
                    text: data.message,
                    timer: 2000,
                    showConfirmButton: false
                });
            } else {
                throw new Error(data.message);
            }
            
        } catch (error) {
            mostrarAlerta(alertModal, error.message, 'danger');
        } finally {
            btnSubmit.disabled = false;
            btnSubmit.innerHTML = '<i class="fas fa-save"></i> Guardar';
        }
    });
    
});

// ============================================
// EDITAR TIPO
// ============================================
async function editarTipo(tipoId) {
    try {
        const response = await fetch(`../../api/tipos_trabajo.php?action=obtener&id=${tipoId}`);
        const data = await response.json();
        
        if (data.success) {
            const tipo = data.tipo;
            
            document.getElementById('tipo_id').value = tipo.id;
            document.getElementById('nombre').value = tipo.nombre;
            document.getElementById('descripcion').value = tipo.descripcion || '';
            document.getElementById('estado').value = tipo.estado;
            
            document.getElementById('tituloModal').textContent = 'Editar Tipo de Trabajo';
            document.getElementById('grupoEstado').style.display = 'block';
            
            $('#modalTipo').modal('show');
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
// ELIMINAR TIPO
// ============================================
async function eliminarTipo(tipoId, nombreTipo) {
    const result = await Swal.fire({
        title: '¿Eliminar tipo de trabajo?',
        html: `<p>Nombre: <strong>${nombreTipo}</strong></p>
               <p class="text-muted">Si tiene reportes asociados, se desactivará en lugar de eliminarse.</p>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    });
    
    if (!result.isConfirmed) return;
    
    try {
        const formData = new FormData();
        formData.append('action', 'eliminar');
        formData.append('id', tipoId);
        
        const response = await fetch('../../api/tipos_trabajo.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            $('#tablaTipos').DataTable().ajax.reload();
            
            Swal.fire({
                icon: 'success',
                title: '¡Eliminado!',
                text: data.message,
                timer: 2000,
                showConfirmButton: false
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
// LIMPIAR FORMULARIO
// ============================================
function limpiarFormulario() {
    document.getElementById('formTipo').reset();
    document.getElementById('tipo_id').value = '';
    document.getElementById('tituloModal').textContent = 'Nuevo Tipo de Trabajo';
    document.getElementById('grupoEstado').style.display = 'none';
    document.getElementById('alertModal').style.display = 'none';
}

// ============================================
// MOSTRAR ALERTA
// ============================================
function mostrarAlerta(elemento, mensaje, tipo) {
    elemento.className = `alert alert-${tipo}`;
    elemento.textContent = mensaje;
    elemento.style.display = 'block';
}

console.log('✓ Funciones de tipos de trabajo disponibles');