/**
 * OperaSys - JavaScript del Dashboard
 * Archivo: assets/js/dashboard.js
 * Descripción: Carga de estadísticas y gráficos con Chart.js
 */

// Variables globales para los gráficos
let graficoReportesMes;
let graficoEquipos;

document.addEventListener('DOMContentLoaded', function() {
    
    // Cargar todas las secciones del dashboard
    cargarEstadisticas();
    cargarGraficoReportesMes();
    cargarGraficoEquipos();
    cargarUltimosReportes();
    
    // Si es admin, cargar actividad reciente
    cargarActividadReciente();
    
    // Actualizar indicador de conexión
    actualizarEstadoConexion();
    
    console.log('✓ Dashboard inicializado');
});

// ============================================
// CARGAR ESTADÍSTICAS GENERALES
// ============================================
async function cargarEstadisticas() {
    try {
        const response = await fetch('../../api/dashboard.php?action=estadisticas');
        const data = await response.json();
        
        if (data.success) {
            const stats = data.estadisticas;
            
            // Actualizar tarjetas
            document.getElementById('totalReportes').textContent = stats.total_reportes || 0;
            document.getElementById('reportesHoy').textContent = stats.reportes_hoy || 0;
            document.getElementById('horasMes').textContent = stats.horas_mes || 0;
            document.getElementById('equiposActivos').textContent = stats.equipos_activos || 0;
            
            // Si es admin, actualizar estadísticas de usuarios
            if (stats.usuarios_operador !== undefined) {
                const elemOperadores = document.getElementById('totalOperadores');
                const elemSupervisores = document.getElementById('totalSupervisores');
                const elemAdmins = document.getElementById('totalAdmins');
                
                if (elemOperadores) elemOperadores.textContent = stats.usuarios_operador || 0;
                if (elemSupervisores) elemSupervisores.textContent = stats.usuarios_supervisor || 0;
                if (elemAdmins) elemAdmins.textContent = stats.usuarios_admin || 0;
            }
            
            console.log('✓ Estadísticas cargadas');
        }
    } catch (error) {
        console.error('Error al cargar estadísticas:', error);
    }
}

// ============================================
// GRÁFICO: REPORTES POR MES
// ============================================
async function cargarGraficoReportesMes() {
    try {
        const response = await fetch('../../api/dashboard.php?action=reportes_mes');
        const data = await response.json();
        
        if (data.success) {
            const ctx = document.getElementById('graficoReportesMes');
            
            if (!ctx) return;
            
            // Destruir gráfico anterior si existe
            if (graficoReportesMes) {
                graficoReportesMes.destroy();
            }
            
            graficoReportesMes = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.labels,
                    datasets: [{
                        label: 'Reportes Creados',
                        data: data.data,
                        backgroundColor: 'rgba(46, 134, 171, 0.2)',
                        borderColor: 'rgba(46, 134, 171, 1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    }
                }
            });
            
            console.log('✓ Gráfico de reportes por mes cargado');
        }
    } catch (error) {
        console.error('Error al cargar gráfico de reportes:', error);
    }
}

// ============================================
// GRÁFICO: EQUIPOS MÁS USADOS
// ============================================
async function cargarGraficoEquipos() {
    try {
        const response = await fetch('../../api/dashboard.php?action=equipos_mas_usados');
        const data = await response.json();
        
        if (data.success) {
            const ctx = document.getElementById('graficoEquipos');
            
            if (!ctx) return;
            
            // Destruir gráfico anterior si existe
            if (graficoEquipos) {
                graficoEquipos.destroy();
            }
            
            graficoEquipos = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: data.labels,
                    datasets: [{
                        label: 'Cantidad de Usos',
                        data: data.data,
                        backgroundColor: [
                            'rgba(255, 99, 132, 0.6)',
                            'rgba(54, 162, 235, 0.6)',
                            'rgba(255, 206, 86, 0.6)',
                            'rgba(75, 192, 192, 0.6)',
                            'rgba(153, 102, 255, 0.6)'
                        ],
                        borderColor: [
                            'rgba(255, 99, 132, 1)',
                            'rgba(54, 162, 235, 1)',
                            'rgba(255, 206, 86, 1)',
                            'rgba(75, 192, 192, 1)',
                            'rgba(153, 102, 255, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    }
                }
            });
            
            console.log('✓ Gráfico de equipos más usados cargado');
        }
    } catch (error) {
        console.error('Error al cargar gráfico de equipos:', error);
    }
}

// ============================================
// CARGAR ÚLTIMOS REPORTES
// ============================================
async function cargarUltimosReportes() {
    try {
        const response = await fetch('../../api/dashboard.php?action=ultimos_reportes');
        const data = await response.json();
        
        if (data.success) {
            const tbody = document.querySelector('#tablaUltimosReportes tbody');
            
            if (!tbody) return;
            
            tbody.innerHTML = '';
            
            if (data.reportes.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No hay reportes aún</td></tr>';
                return;
            }
            
            data.reportes.forEach(reporte => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${reporte.fecha}</td>
                    <td>${reporte.equipo}</td>
                    <td>${reporte.horas}</td>
                    <td>${reporte.actividad}</td>
                    <td>${reporte.estado}</td>
                    <td>
                        <button onclick="verDetalleReporte(${reporte.id})" class="btn btn-sm btn-info">
                            <i class="fas fa-eye"></i>
                        </button>
                    </td>
                `;
                tbody.appendChild(tr);
            });
            
            console.log('✓ Últimos reportes cargados');
        }
    } catch (error) {
        console.error('Error al cargar últimos reportes:', error);
    }
}

// ============================================
// CARGAR ACTIVIDAD RECIENTE (Solo Admin)
// ============================================
async function cargarActividadReciente() {
    try {
        const lista = document.getElementById('actividadReciente');
        
        if (!lista) return; // No es admin
        
        const response = await fetch('../../api/dashboard.php?action=actividad_reciente');
        const data = await response.json();
        
        if (data.success) {
            lista.innerHTML = '';
            
            if (data.actividades.length === 0) {
                lista.innerHTML = '<li class="list-group-item text-center text-muted">No hay actividad reciente</li>';
                return;
            }
            
            data.actividades.forEach(act => {
                const li = document.createElement('li');
                li.className = 'list-group-item';
                li.innerHTML = `
                    <div class="d-flex align-items-center">
                        <div class="mr-3">
                            <i class="fas ${act.icono} text-${act.color} fa-2x"></i>
                        </div>
                        <div class="flex-grow-1">
                            <strong>${act.usuario}</strong>
                            <p class="mb-0 text-muted small">${act.detalle || act.accion}</p>
                            <small class="text-muted">${act.tiempo}</small>
                        </div>
                    </div>
                `;
                lista.appendChild(li);
            });
            
            console.log('✓ Actividad reciente cargada');
        }
    } catch (error) {
        console.error('Error al cargar actividad reciente:', error);
    }
}

// ============================================
// ACTUALIZAR ESTADO DE CONEXIÓN
// ============================================
function actualizarEstadoConexion() {
    const indicador = document.getElementById('estadoConexion');
    
    if (!indicador) return;
    
    if (navigator.onLine) {
        indicador.innerHTML = '<i class="fas fa-wifi text-success"></i> <span class="d-none d-sm-inline">Online</span>';
    } else {
        indicador.innerHTML = '<i class="fas fa-wifi-slash text-danger"></i> <span class="d-none d-sm-inline">Offline</span>';
    }
}

// Eventos de conexión
window.addEventListener('online', actualizarEstadoConexion);
window.addEventListener('offline', actualizarEstadoConexion);

// ============================================
// FUNCIÓN GLOBAL: ACTUALIZAR DASHBOARD
// ============================================
function actualizarDashboard() {
    console.log('🔄 Actualizando dashboard...');
    
    // Mostrar indicador de carga
    const btn = event.target.closest('button');
    const iconoOriginal = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Actualizando...';
    btn.disabled = true;
    
    // Recargar todas las secciones
    Promise.all([
        cargarEstadisticas(),
        cargarGraficoReportesMes(),
        cargarGraficoEquipos(),
        cargarUltimosReportes(),
        cargarActividadReciente()
    ]).then(() => {
        console.log('✓ Dashboard actualizado');
        btn.innerHTML = iconoOriginal;
        btn.disabled = false;
        
        // Mostrar notificación
        if (typeof toastr !== 'undefined') {
            toastr.success('Dashboard actualizado');
        }
    }).catch(error => {
        console.error('Error al actualizar dashboard:', error);
        btn.innerHTML = iconoOriginal;
        btn.disabled = false;
    });
}

// ============================================
// FUNCIÓN GLOBAL: VER DETALLE DE REPORTE
// (Usa la función del módulo de reportes)
// ============================================
// Ya está definida en reportes.js

console.log('✓ Script de dashboard cargado');
