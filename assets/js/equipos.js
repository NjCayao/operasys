/**
 * OperaSys - JavaScript de Equipos
 * Archivo: assets/js/equipos.js
 * Descripción: CRUD de equipos con DataTables y SweetAlert2
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // ============================================
    // DATATABLE - LISTADO DE EQUIPOS
    // ============================================
    if (document.getElementById('tablaEquipos')) {
        const tabla = $('#tablaEquipos').DataTable({
            ajax: {
                url: '../../api/equipos.php?action=listar',
                dataSrc: 'data'
            },
            columns: [
                { title: 'ID' },
                { title: 'Categoría' },
                { title: 'Código' },
                { title: 'Descripción' },
                { title: 'Estado' },
                { title: 'Fecha Registro' },
                { title: 'Acciones', orderable: false, searchable: false }
            ],
            language: {
                "decimal": "",
                "emptyTable": "No hay datos disponibles en la tabla",
                "info": "Mostrando _START_ a _END_ de _TOTAL_ registros",
                "infoEmpty": "Mostrando 0 a 0 de 0 registros",
                "infoFiltered": "(filtrado de _MAX_ registros totales)",
                "infoPostFix": "",
                "thousands": ",",
                "lengthMenu": "Mostrar _MENU_ registros",
                "loadingRecords": "Cargando...",
                "processing": "Procesando...",
                "search": "Buscar:",
                "zeroRecords": "No se encontraron registros coincidentes",
                "paginate": {
                    "first": "Primero",
                    "last": "Último",
                    "next": "Siguiente",
                    "previous": "Anterior"
                },
                "aria": {
                    "sortAscending": ": activar para ordenar la columna ascendente",
                    "sortDescending": ": activar para ordenar la columna descendente"
                }
            },
            order: [[0, 'desc']],
            responsive: true,
            pageLength: 25
        });
        
        console.log('✓ DataTable de equipos inicializado');
    }
    
    // ============================================
    // FORMULARIO AGREGAR EQUIPO
    // ============================================
    const formAgregar = document.getElementById('formAgregarEquipo');
    
    if (formAgregar) {
        formAgregar.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const btnSubmit = this.querySelector('button[type="submit"]');
            const alertMessage = document.getElementById('alertMessage');
            const alertText = document.getElementById('alertText');
            
            // Deshabilitar botón
            btnSubmit.disabled = true;
            btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
            alertMessage.style.display = 'none';
            
            try {
                const formData = new FormData(this);
                formData.append('action', 'crear');
                
                const response = await fetch('../../api/equipos.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Éxito
                    Swal.fire({
                        icon: 'success',
                        title: '¡Éxito!',
                        text: data.message,
                        confirmButtonText: 'Ver equipos'
                    }).then(() => {
                        window.location.href = 'listar.php';
                    });
                } else {
                    throw new Error(data.message);
                }
                
            } catch (error) {
                alertMessage.classList.remove('alert-success');
                alertMessage.classList.add('alert-danger');
                alertText.textContent = error.message;
                alertMessage.style.display = 'block';
                
                btnSubmit.disabled = false;
                btnSubmit.innerHTML = '<i class="fas fa-save"></i> Guardar Equipo';
            }
        });
        
        // Convertir código a mayúsculas automáticamente
        const inputCodigo = formAgregar.querySelector('input[name="codigo"]');
        if (inputCodigo) {
            inputCodigo.addEventListener('input', function() {
                this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
            });
        }
    }
    
    // ============================================
    // FORMULARIO EDITAR EQUIPO
    // ============================================
    const formEditar = document.getElementById('formEditarEquipo');
    
    if (formEditar) {
        formEditar.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const btnSubmit = this.querySelector('button[type="submit"]');
            const alertMessage = document.getElementById('alertMessage');
            const alertText = document.getElementById('alertText');
            
            btnSubmit.disabled = true;
            btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Actualizando...';
            alertMessage.style.display = 'none';
            
            try {
                const formData = new FormData(this);
                formData.append('action', 'actualizar');
                
                const response = await fetch('../../api/equipos.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Actualizado!',
                        text: data.message,
                        confirmButtonText: 'Ver equipos'
                    }).then(() => {
                        window.location.href = 'listar.php';
                    });
                } else {
                    throw new Error(data.message);
                }
                
            } catch (error) {
                alertMessage.classList.remove('alert-success');
                alertMessage.classList.add('alert-danger');
                alertText.textContent = error.message;
                alertMessage.style.display = 'block';
                
                btnSubmit.disabled = false;
                btnSubmit.innerHTML = '<i class="fas fa-save"></i> Actualizar Equipo';
            }
        });
        
        // Convertir código a mayúsculas
        const inputCodigo = formEditar.querySelector('input[name="codigo"]');
        if (inputCodigo) {
            inputCodigo.addEventListener('input', function() {
                this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
            });
        }
    }
    
});

// ============================================
// FUNCIÓN GLOBAL: ELIMINAR EQUIPO
// ============================================
async function eliminarEquipo(id, codigo) {
    
    const result = await Swal.fire({
        title: '¿Eliminar equipo?',
        html: `¿Estás seguro de eliminar el equipo <strong>${codigo}</strong>?<br><br>
               <small class="text-muted">Si el equipo tiene reportes asociados, se desactivará en lugar de eliminarse.</small>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    });
    
    if (result.isConfirmed) {
        
        try {
            const formData = new FormData();
            formData.append('action', 'eliminar');
            formData.append('id', id);
            
            const response = await fetch('../../api/equipos.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Eliminado',
                    text: data.message
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
}

// ============================================
// FUNCIÓN GLOBAL: BUSCAR EQUIPOS POR CATEGORÍA
// (Será usada en el formulario de reportes)
// ============================================
async function buscarEquiposPorCategoria(categoria) {
    try {
        const response = await fetch(`../../api/equipos.php?action=buscar_por_categoria&categoria=${encodeURIComponent(categoria)}`);
        const data = await response.json();
        
        if (data.success) {
            return data.equipos;
        } else {
            throw new Error(data.message);
        }
    } catch (error) {
        console.error('Error al buscar equipos:', error);
        return [];
    }
}

console.log('✓ Script de equipos cargado');