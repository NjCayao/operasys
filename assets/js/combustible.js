/**
 * OperaSys - JavaScript de Combustible
 * Archivo: assets/js/combustible.js
 * Versión: 3.0
 * Descripción: Gestión de abastecimientos de combustible con validaciones y cálculos
 */

// Variables globales
let reporteActualId = null;
let equipoActualId = null;
let horometroInicial = 0;
let horometroFinal = 0;
let capacidadTanque = 0;
let consumoPromedioHr = 0;

// ============================================
// INICIALIZACIÓN
// ============================================
document.addEventListener("DOMContentLoaded", function () {
  console.log("✓ Script de combustible V3.0 cargado");

  // Obtener datos del reporte si estamos en editar.php
  const reporteIdInput = document.getElementById("reporte_id");
  if (reporteIdInput && reporteIdInput.value) {
    reporteActualId = reporteIdInput.value;
    cargarDatosReporte();
  }

  // Event listener para el botón agregar combustible
  const btnAgregarCombustible = document.getElementById("btnAgregarCombustible");
  if (btnAgregarCombustible) {
    btnAgregarCombustible.addEventListener("click", function () {
      abrirModalCombustible();
    });
  }

  // Event listener para el formulario
  const formCombustible = document.getElementById("formCombustible");
  if (formCombustible) {
    formCombustible.addEventListener("submit", async function (e) {
      e.preventDefault();
      await guardarCombustible();
    });
  }

  // Validación en tiempo real de galones
  const inputGalones = document.getElementById("galones");
  if (inputGalones) {
    inputGalones.addEventListener("input", function () {
      validarCapacidadTanque(this.value);
    });
  }

  // Validación en tiempo real de horómetro
  const inputHorometro = document.getElementById("horometro_combustible");
  if (inputHorometro) {
    inputHorometro.addEventListener("input", function () {
      validarHorometro(this.value);
    });
  }
});

// ============================================
// CARGAR DATOS DEL REPORTE
// ============================================
async function cargarDatosReporte() {
  try {
    const response = await fetch(
      `../../api/reportes.php?action=obtener&id=${reporteActualId}`
    );
    const data = await response.json();

    if (data.success) {
      const reporte = data.reporte;
      
      horometroInicial = parseFloat(reporte.horometro_inicial || 0);
      horometroFinal = parseFloat(reporte.horometro_final || 0);
      capacidadTanque = parseFloat(reporte.capacidad_tanque || 0);
      consumoPromedioHr = parseFloat(reporte.consumo_promedio_hr || 0);
      equipoActualId = reporte.equipo_id;

      console.log("✓ Datos del reporte cargados para combustible");
      console.log(`Horómetro: ${horometroInicial} - ${horometroFinal}`);
      console.log(`Capacidad tanque: ${capacidadTanque} gal`);
      console.log(`Consumo promedio: ${consumoPromedioHr} gal/hr`);

      // Cargar lista de combustibles
      await cargarCombustibles();
      
      // Actualizar resumen
      actualizarResumenCombustible();
    }
  } catch (error) {
    console.error("Error al cargar datos del reporte:", error);
  }
}

// ============================================
// ABRIR MODAL COMBUSTIBLE
// ============================================
function abrirModalCombustible() {
  const form = document.getElementById("formCombustible");
  if (form) {
    form.reset();
  }

  // Establecer hora actual por defecto
  const inputHora = document.getElementById("hora_abastecimiento");
  if (inputHora) {
    const ahora = new Date();
    const horas = String(ahora.getHours()).padStart(2, '0');
    const minutos = String(ahora.getMinutes()).padStart(2, '0');
    inputHora.value = `${horas}:${minutos}`;
  }

  // Sugerir horómetro (último registrado o final si existe)
  const inputHorometro = document.getElementById("horometro_combustible");
  if (inputHorometro && horometroFinal > 0) {
    inputHorometro.value = horometroFinal;
  } else if (inputHorometro && horometroInicial > 0) {
    inputHorometro.value = horometroInicial;
  }

  // Limpiar alertas
  const alert = document.getElementById("alertCombustible");
  if (alert) {
    alert.style.display = "none";
  }

  $("#modalCombustible").modal("show");
}

// ============================================
// VALIDAR CAPACIDAD DEL TANQUE
// ============================================
function validarCapacidadTanque(galones) {
  const inputGalones = document.getElementById("galones");
  const alertCombustible = document.getElementById("alertCombustible");

  galones = parseFloat(galones);

  if (isNaN(galones) || galones <= 0) {
    return;
  }

  if (capacidadTanque > 0 && galones > capacidadTanque) {
    mostrarAlertaCombustible(
      `⚠️ Los galones (${galones.toFixed(2)}) exceden la capacidad del tanque (${capacidadTanque.toFixed(2)} gal)`,
      "warning"
    );
    inputGalones.classList.add("is-invalid");
  } else {
    inputGalones.classList.remove("is-invalid");
    if (alertCombustible && alertCombustible.classList.contains("alert-warning")) {
      alertCombustible.style.display = "none";
    }
  }
}

// ============================================
// VALIDAR HORÓMETRO
// ============================================
function validarHorometro(horometro) {
  const inputHorometro = document.getElementById("horometro_combustible");
  horometro = parseFloat(horometro);

  if (isNaN(horometro)) {
    return;
  }

  if (horometro < horometroInicial) {
    mostrarAlertaCombustible(
      `⚠️ El horómetro (${horometro.toFixed(1)}) no puede ser menor al inicial (${horometroInicial.toFixed(1)})`,
      "danger"
    );
    inputHorometro.classList.add("is-invalid");
  } else if (horometroFinal > 0 && horometro > horometroFinal) {
    mostrarAlertaCombustible(
      `⚠️ El horómetro (${horometro.toFixed(1)}) no puede ser mayor al final (${horometroFinal.toFixed(1)})`,
      "danger"
    );
    inputHorometro.classList.add("is-invalid");
  } else {
    inputHorometro.classList.remove("is-invalid");
    const alertCombustible = document.getElementById("alertCombustible");
    if (alertCombustible && alertCombustible.classList.contains("alert-danger")) {
      alertCombustible.style.display = "none";
    }
  }
}

// ============================================
// GUARDAR COMBUSTIBLE
// ============================================
async function guardarCombustible() {
  const alertCombustible = document.getElementById("alertCombustible");
  const btnSubmit = document.querySelector("#formCombustible button[type='submit']");

  const horometro = parseFloat(document.getElementById("horometro_combustible").value);
  const horaAbastecimiento = document.getElementById("hora_abastecimiento").value;
  const galones = parseFloat(document.getElementById("galones").value);
  const observaciones = document.getElementById("observaciones_combustible").value.trim();

  // Validaciones finales
  if (!horometro || !horaAbastecimiento || !galones) {
    mostrarAlertaCombustible("Todos los campos marcados con * son obligatorios", "danger");
    return;
  }

  if (galones <= 0) {
    mostrarAlertaCombustible("Los galones deben ser mayor a 0", "danger");
    return;
  }

  if (capacidadTanque > 0 && galones > capacidadTanque) {
    mostrarAlertaCombustible(
      `Los galones (${galones.toFixed(2)}) exceden la capacidad del tanque (${capacidadTanque.toFixed(2)} gal)`,
      "danger"
    );
    return;
  }

  if (horometro < horometroInicial || (horometroFinal > 0 && horometro > horometroFinal)) {
    mostrarAlertaCombustible("El horómetro está fuera del rango válido", "danger");
    return;
  }

  btnSubmit.disabled = true;
  btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
  alertCombustible.style.display = "none";

  try {
    const formData = new FormData();
    formData.append("action", "agregar_combustible");
    formData.append("reporte_id", reporteActualId);
    formData.append("horometro", horometro);
    formData.append("hora_abastecimiento", horaAbastecimiento);
    formData.append("galones", galones);
    formData.append("observaciones", observaciones);

    const response = await fetch("../../api/reportes.php", {
      method: "POST",
      body: formData,
    });

    const data = await response.json();

    if (data.success) {
      $("#modalCombustible").modal("hide");
      await cargarCombustibles();
      actualizarResumenCombustible();

      Swal.fire({
        icon: "success",
        title: "¡Combustible registrado!",
        text: data.message,
        timer: 2000,
        showConfirmButton: false,
      });
    } else {
      throw new Error(data.message);
    }
  } catch (error) {
    mostrarAlertaCombustible(error.message, "danger");
  } finally {
    btnSubmit.disabled = false;
    btnSubmit.innerHTML = '<i class="fas fa-save"></i> Guardar';
  }
}

// ============================================
// CARGAR LISTA DE COMBUSTIBLES
// ============================================
async function cargarCombustibles() {
  try {
    const response = await fetch(
      `../../api/reportes.php?action=obtener&id=${reporteActualId}`
    );
    const data = await response.json();

    if (data.success && data.reporte) {
      const container = document.getElementById("listaCombustible");
      
      // Obtener combustibles (ajustar según tu estructura de respuesta)
      const combustibles = data.combustibles || [];

      if (!combustibles || combustibles.length === 0) {
        container.innerHTML =
          '<p class="text-muted text-center"><i class="fas fa-info-circle"></i> No hay abastecimientos registrados.</p>';
        return;
      }

      let html = '<div class="table-responsive"><table class="table table-bordered table-hover table-sm">';
      html += '<thead class="thead-light"><tr>';
      html += '<th width="8%">#</th>';
      html += '<th width="15%">Horómetro</th>';
      html += '<th width="15%">Hora</th>';
      html += '<th width="15%">Galones</th>';
      html += '<th>Observaciones</th>';
      html += '<th width="10%">Acción</th>';
      html += '</tr></thead><tbody>';

      let totalGalones = 0;

      combustibles.forEach((comb, index) => {
        totalGalones += parseFloat(comb.galones);
        
        html += `<tr>
          <td class="text-center">${index + 1}</td>
          <td class="text-center"><strong>${parseFloat(comb.horometro).toFixed(1)}</strong></td>
          <td class="text-center">${comb.hora_abastecimiento || '-'}</td>
          <td class="text-center"><span class="badge badge-info">${parseFloat(comb.galones).toFixed(2)} gal</span></td>
          <td>${comb.observaciones || '<em class="text-muted">-</em>'}</td>
          <td class="text-center">
            <button onclick="eliminarCombustible(${comb.id})" class="btn btn-sm btn-danger" title="Eliminar">
              <i class="fas fa-trash"></i>
            </button>
          </td>
        </tr>`;
      });

      html += `</tbody><tfoot class="font-weight-bold bg-light"><tr>
        <td colspan="3" class="text-right">TOTAL ABASTECIDO:</td>
        <td class="text-center"><span class="badge badge-primary">${totalGalones.toFixed(2)} gal</span></td>
        <td colspan="2"></td>
      </tr></tfoot></table></div>`;

      container.innerHTML = html;
    }
  } catch (error) {
    console.error("Error al cargar combustibles:", error);
  }
}

// ============================================
// ELIMINAR COMBUSTIBLE
// ============================================
async function eliminarCombustible(combustibleId) {
  const result = await Swal.fire({
    title: "¿Eliminar registro?",
    text: "Esta acción no se puede deshacer",
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#d33",
    cancelButtonColor: "#3085d6",
    confirmButtonText: "Sí, eliminar",
    cancelButtonText: "Cancelar",
  });

  if (!result.isConfirmed) return;

  try {
    const formData = new FormData();
    formData.append("action", "eliminar_combustible");
    formData.append("id", combustibleId);

    const response = await fetch("../../api/reportes.php", {
      method: "POST",
      body: formData,
    });

    const data = await response.json();

    if (data.success) {
      await cargarCombustibles();
      actualizarResumenCombustible();

      Swal.fire({
        icon: "success",
        title: "Eliminado",
        text: data.message,
        timer: 2000,
        showConfirmButton: false,
      });
    } else {
      throw new Error(data.message);
    }
  } catch (error) {
    Swal.fire({
      icon: "error",
      title: "Error",
      text: error.message,
    });
  }
}

// ============================================
// ACTUALIZAR RESUMEN DE COMBUSTIBLE
// ============================================
async function actualizarResumenCombustible() {
  try {
    const response = await fetch(
      `../../api/reportes.php?action=obtener&id=${reporteActualId}`
    );
    const data = await response.json();

    if (data.success) {
      const totalAbastecido = parseFloat(data.reporte.total_abastecido || 0);
      const horasMotor = parseFloat(data.reporte.horas_motor || 0);
      const consumoEstimado = horasMotor * consumoPromedioHr;
      const diferencia = totalAbastecido - consumoEstimado;

      // Actualizar en la vista si existe el contenedor
      const containerResumen = document.getElementById("resumenCombustible");
      if (containerResumen) {
        let html = '<div class="row">';
        
        html += '<div class="col-md-4"><div class="info-box bg-light">';
        html += '<span class="info-box-icon"><i class="fas fa-calculator"></i></span>';
        html += '<div class="info-box-content">';
        html += '<span class="info-box-text">Consumo Estimado</span>';
        html += `<span class="info-box-number">${consumoEstimado.toFixed(2)} gal</span>`;
        html += '</div></div></div>';
        
        html += '<div class="col-md-4"><div class="info-box bg-info">';
        html += '<span class="info-box-icon"><i class="fas fa-gas-pump"></i></span>';
        html += '<div class="info-box-content">';
        html += '<span class="info-box-text">Total Abastecido</span>';
        html += `<span class="info-box-number">${totalAbastecido.toFixed(2)} gal</span>`;
        html += '</div></div></div>';
        
        const colorDiferencia = diferencia >= 0 ? 'bg-success' : 'bg-danger';
        const iconoDiferencia = diferencia >= 0 ? 'arrow-up' : 'arrow-down';
        
        html += `<div class="col-md-4"><div class="info-box ${colorDiferencia}">`;
        html += `<span class="info-box-icon"><i class="fas fa-${iconoDiferencia}"></i></span>`;
        html += '<div class="info-box-content">';
        html += '<span class="info-box-text">Diferencia</span>';
        html += `<span class="info-box-number">${diferencia.toFixed(2)} gal</span>`;
        html += '</div></div></div>';
        
        html += '</div>';
        
        containerResumen.innerHTML = html;
      }

      console.log(`✓ Resumen actualizado: Estimado ${consumoEstimado.toFixed(2)} / Abastecido ${totalAbastecido.toFixed(2)}`);
    }
  } catch (error) {
    console.error("Error al actualizar resumen:", error);
  }
}

// ============================================
// CALCULAR CONSUMO ESTIMADO
// ============================================
function calcularConsumoEstimado() {
  if (!horometroInicial || !horometroFinal || horometroFinal <= horometroInicial) {
    return 0;
  }

  const horasMotor = horometroFinal - horometroInicial;
  return horasMotor * consumoPromedioHr;
}

// ============================================
// MOSTRAR ALERTA EN MODAL
// ============================================
function mostrarAlertaCombustible(mensaje, tipo) {
  const alert = document.getElementById("alertCombustible");
  if (alert) {
    alert.className = `alert alert-${tipo}`;
    alert.innerHTML = `<i class="fas fa-exclamation-triangle"></i> ${mensaje}`;
    alert.style.display = "block";
  }
}

// ============================================
// EXPORTAR FUNCIONES GLOBALES
// ============================================
window.eliminarCombustible = eliminarCombustible;
window.cargarCombustibles = cargarCombustibles;
window.actualizarResumenCombustible = actualizarResumenCombustible;

console.log("✓ Módulo de combustible disponible globalmente");