/**
 * OperaSys - Auditoría JS
 * Archivo: assets/js/auditoria.js
 * Descripción: Lógica para visualizar el registro de actividades del sistema
 */

$(document).ready(function() {
    cargarUsuarios();
    inicializarTabla();
    
    // Event Listeners
    $('#btnAplicarFiltrosAuditoria').on('click', aplicarFiltros);
});

let tablaAuditoria;

/**
 * Cargar lista de usuarios para el filtro
 */
function cargarUsuarios() {
    $.ajax({
        url: '../../api/usuarios.php?action=listar',
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                let html = '<option value="">Todos</option>';
                response.data.forEach(function(user) {
                    html += `<option value="${user.id}">${user.nombre_completo} (${user.rol})</option>`;
                });
                $('#filtro_usuario').html(html);
            }
        },
        error: function() {
            console.error('Error al cargar usuarios');
        }
    });
}

/**
 * Inicializar DataTable
 */
function inicializarTabla() {
    tablaAuditoria = $('#tablaAuditoria').DataTable({
        ajax: {
            url: '../../api/auditoria.php?action=listar',
            type: 'GET',
            dataSrc: function(json) {
                if (json.success) {
                    return json.data;
                }
                return [];
            }
        },
        columns: [
            { data: 'id' },
            { 
                data: 'fecha',
                render: function(data) {
                    // Formatear fecha: 2025-10-14 15:30:45 → 14/10/2025 15:30
                    const fecha = new Date(data);
                    const dia = String(fecha.getDate()).padStart(2, '0');
                    const mes = String(fecha.getMonth() + 1).padStart(2, '0');
                    const anio = fecha.getFullYear();
                    const hora = String(fecha.getHours()).padStart(2, '0');
                    const min = String(fecha.getMinutes()).padStart(2, '0');
                    return `${dia}/${mes}/${anio}<br><small class="text-muted">${hora}:${min}</small>`;
                }
            },
            { 
                data: null,
                render: function(data) {
                    const rolBadge = data.usuario_rol === 'admin' ? 'danger' : 
                                    data.usuario_rol === 'supervisor' ? 'warning' : 'info';
                    return `${data.usuario_nombre}<br>
                            <span class="badge badge-${rolBadge}">${data.usuario_rol}</span>`;
                }
            },
            { 
                data: 'accion',
                render: function(data) {
                    let icon = 'fas fa-circle';
                    let color = 'secondary';
                    
                    switch(data) {
                        case 'login':
                            icon = 'fas fa-sign-in-alt';
                            color = 'success';
                            break;
                        case 'logout':
                            icon = 'fas fa-sign-out-alt';
                            color = 'warning';
                            break;
                        case 'crear_reporte':
                            icon = 'fas fa-plus-circle';
                            color = 'primary';
                            break;
                        case 'editar_reporte':
                            icon = 'fas fa-edit';
                            color = 'info';
                            break;
                        case 'eliminar_reporte':
                            icon = 'fas fa-trash';
                            color = 'danger';
                            break;
                        case 'crear_usuario':
                            icon = 'fas fa-user-plus';
                            color = 'success';
                            break;
                        case 'editar_usuario':
                            icon = 'fas fa-user-edit';
                            color = 'info';
                            break;
                        case 'crear_equipo':
                            icon = 'fas fa-truck-monster';
                            color = 'success';
                            break;
                        case 'editar_equipo':
                            icon = 'fas fa-wrench';
                            color = 'info';
                            break;
                    }
                    
                    return `<i class="${icon} text-${color}"></i> ${data.replace('_', ' ')}`;
                }
            },
            { 
                data: 'detalle',
                render: function(data) {
                    if (!data || data === 'null') return '<span class="text-muted">Sin detalles</span>';
                    // Limitar a 100 caracteres
                    if (data.length > 100) {
                        return `<span title="${data}">${data.substring(0, 100)}...</span>`;
                    }
                    return data;
                }
            },
            { 
                data: 'ip_address',
                render: function(data) {
                    return data || '<span class="text-muted">N/A</span>';
                }
            },
            { 
                data: 'user_agent',
                render: function(data) {
                    if (!data || data === 'null') return '<span class="text-muted">N/A</span>';
                    
                    // Detectar navegador
                    let navegador = 'Desconocido';
                    let icon = 'fas fa-globe';
                    
                    if (data.includes('Chrome')) {
                        navegador = 'Chrome';
                        icon = 'fab fa-chrome';
                    } else if (data.includes('Firefox')) {
                        navegador = 'Firefox';
                        icon = 'fab fa-firefox';
                    } else if (data.includes('Safari')) {
                        navegador = 'Safari';
                        icon = 'fab fa-safari';
                    } else if (data.includes('Edge')) {
                        navegador = 'Edge';
                        icon = 'fab fa-edge';
                    }
                    
                    return `<i class="${icon}"></i> ${navegador}`;
                }
            }
        ],
        order: [[0, 'desc']],
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json',
            emptyTable: 'No hay registros de auditoría',
            zeroRecords: 'No se encontraron resultados'
        },
        responsive: true,
        pageLength: 50,
        lengthMenu: [[25, 50, 100, 250, -1], [25, 50, 100, 250, "Todos"]]
    });
}

/**
 * Aplicar filtros a la tabla
 */
function aplicarFiltros() {
    const filtros = {
        usuario_id: $('#filtro_usuario').val(),
        accion: $('#filtro_accion').val(),
        fecha_desde: $('#filtro_fecha_desde').val(),
        fecha_hasta: $('#filtro_fecha_hasta').val()
    };
    
    // Reconstruir URL con filtros
    let url = '../../api/auditoria.php?action=listar';
    Object.keys(filtros).forEach(key => {
        if (filtros[key]) {
            url += `&${key}=${filtros[key]}`;
        }
    });
    
    // Recargar tabla con filtros
    tablaAuditoria.ajax.url(url).load();
    
    Swal.fire({
        icon: 'success',
        title: 'Filtros aplicados',
        text: 'La tabla se ha actualizado',
        timer: 1500,
        showConfirmButton: false
    });
}