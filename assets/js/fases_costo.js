/**
 * OperaSys - JavaScript de Fases de Costo
 * Archivo: assets/js/fases_costo.js
 * Descripción: CRUD de fases de costo
 */

document.addEventListener('DOMContentLoaded', function() {
    
    console.log('✓ Script de fases de costo cargado');
    
    // ============================================
    // DATATABLE - LISTADO DE FASES
    // ============================================
    if (document.getElementById('tablaFases')) {
        const tabla = $('#tablaFases').DataTable({
            ajax: {
                url: '../../api/fases_costo.php?action=listar',
                dataSrc: 'data'
            },
            columns: [
                { title: 'ID' },
                { title: 'Código' },
                { title: 'Descripción' },
                { title: 'Proyecto' },
                { title: 'Estado' },
                { title: 'Fecha Registro' },
                { title: 'Acciones', orderable: false, searchable: false }
            ],
            language: {
                "decimal": "",
                "emptyTable": "No hay datos disponibles",
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
            order: [[1, 'asc']], // Ordenar por código
            responsive: true,
            pageLength: 25
        });
        
        console.log('✓ DataTable inicializado');
    }
    
    // ============================================
    // BOTÓN NUEVA FASE
    // ============================================
    const btnNuevaFase = document.getElementById('btnNuevaFase');
    if (btnNuevaFase) {
        btnNuevaFase.addEventListener('click', function() {
            limpiarFormulario();
            $('#modalFase').modal('show');
        });
    }
    
    // ============================================
    // CONVERTIR CÓDIGO A MAYÚSCULAS
    // ============================================
    const inputCodigo = document.getElementById('codigo');
    if (inputCodigo) {
        inputCodigo.addEventListener('input', function() {
            this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
        });
    }
    
    // ============================================
    // FORMULARIO GUARDAR FASE
    // ============================================
    const formFase = document.getElementById('formFase');
    if (formFase) {
        formFase.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const alertModal = document.getElementById('alertModal');
            const btnSubmit = this.querySelector('button[type="submit"]');
            
            btnSubmit.disabled = true;
            btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
            alertModal.style.display = 'none';
            
            try {
                const faseId = document.getElementById('fase_id').value;
                const formData = new FormData();
                
                formData.append('action', faseId ? 'actualizar' : 'crear');
                if (faseId) formData.append('id', faseId);
                formData.append('codigo', document.getElementById('codigo').value.trim());
                formData.append('descripcion', document.getElementById('descripcion').value.trim());
                formData.append('proyecto', document.getElementById('proyecto').value.trim());
                if (faseId) formData.append('estado', document.getElementById('estado').value);
                
                const response = await fetch('../../api/fases_costo.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    $('#modalFase').modal('hide');
                    
                    Swal.fire({
                        icon: 'success',
                        title: '¡Éxito!',
                        text: data.message,
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    throw new Error(data.message);
                }
                
            } catch (error) {
                alertModal.className = 'alert alert-danger';
                alertModal.textContent = error.message;
                alertModal.style.display = 'block';
                
                btnSubmit.disabled = false;
                btnSubmit.innerHTML = '<i class="fas fa-save"></i> Guardar';
            }
        });
    }
    
});

// ============================================
// FUNCIÓN GLOBAL: EDITAR FASE
// ============================================
async function editarFase(id) {
    try {
        const response = await fetch(`../../api/fases_costo.php?action=obtener&id=${id}`);
        const data = await response.json();
        
        if (data.success) {
            const fase = data.fase;
            
            document.getElementById('fase_id').value = fase.id;
            document.getElementById('codigo').value = fase.codigo;
            document.getElementById('descripcion').value = fase.descripcion;
            document.getElementById('proyecto').value = fase.proyecto || '';
            document.getElementById('estado').value = fase.estado;
            
            document.getElementById('tituloModal').textContent = 'Editar Fase de Costo';
            document.getElementById('grupoEstado').style.display = 'block';
            
            $('#modalFase').modal('show');
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
// FUNCIÓN GLOBAL: ELIMINAR FASE
// ============================================
async function eliminarFase(id, codigo) {
    const result = await Swal.fire({
        title: '¿Eliminar fase de costo?',
        html: `¿Está seguro de eliminar <strong>${codigo}</strong>?<br><br>
               <small class="text-muted">Si tiene reportes asociados, se desactivará en lugar de eliminarse.</small>`,
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
        formData.append('id', id);
        
        const response = await fetch('../../api/fases_costo.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Eliminado',
                text: data.message,
                timer: 2000,
                showConfirmButton: false
            }).then(() => {
                location.reload();
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
// FUNCIÓN: LIMPIAR FORMULARIO
// ============================================
function limpiarFormulario() {
    document.getElementById('formFase').reset();
    document.getElementById('fase_id').value = '';
    document.getElementById('tituloModal').textContent = 'Nueva Fase de Costo';
    document.getElementById('grupoEstado').style.display = 'none';
    document.getElementById('alertModal').style.display = 'none';
}

console.log('✓ Funciones de fases de costo disponibles');