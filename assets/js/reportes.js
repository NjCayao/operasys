/**
 * OperaSys - JavaScript de Reportes
 * Archivo: assets/js/reportes.js
 * Versión: 3.0 - Sistema HT/HP (SIN partidas)
 * Descripción: CRUD completo de reportes con actividades HT/HP y combustible
 */

// Variables globales
let reporteActualId = null;
let actividadesHT = [];
let motivosHP = [];

document.addEventListener("DOMContentLoaded", function () {
  console.log("✓ Script de reportes V3.0 cargado");

  // ============================================
  // CARGAR EQUIPOS FILTRADOS AL INICIO (crear.php)
  // ============================================
  const selectEquipo = document.getElementById("equipo_id");
  if (selectEquipo && !document.getElementById("reporte_id")) {
    cargarEquiposFiltrados();
  }

  // ============================================
  // PASO 1: INICIAR REPORTE (crear.php)
  // ============================================
  const btnIniciarReporte = document.getElementById("btnIniciarReporte");

  if (btnIniciarReporte) {
    btnIniciarReporte.addEventListener("click", async function () {
      const equipoId = document.getElementById("equipo_id").value;
      const horometroInicial = document.getElementById("horometro_inicial")?.value;
      const alertPaso1 = document.getElementById("alertPaso1");

      if (!equipoId) {
        mostrarAlerta(alertPaso1, "Debe seleccionar un equipo", "danger");
        return;
      }

      if (!horometroInicial || horometroInicial <= 0) {
        mostrarAlerta(alertPaso1, "Debe ingresar el horómetro inicial", "danger");
        return;
      }

      btnIniciarReporte.disabled = true;
      btnIniciarReporte.innerHTML =
        '<i class="fas fa-spinner fa-spin"></i> Creando reporte...';
      alertPaso1.style.display = "none";

      try {
        const formData = new FormData();
        formData.append("action", "crear");
        formData.append("equipo_id", equipoId);
        formData.append("horometro_inicial", horometroInicial);
        formData.append("fecha", new Date().toISOString().split("T")[0]);

        const response = await fetch("../../api/reportes.php", {
          method: "POST",
          body: formData,
        });

        const data = await response.json();

        if (data.success) {
          reporteActualId = data.reporte_id;

          const selectEquipo = document.getElementById("equipo_id");
          const equipoTexto =
            selectEquipo.options[selectEquipo.selectedIndex].text;
          document.getElementById("equipoSeleccionado").textContent =
            equipoTexto;

          document.getElementById("paso1").style.display = "none";
          document.getElementById("paso2").style.display = "block";

          // Cargar catálogos HT/HP
          await cargarActividadesHT();
          await cargarMotivosHP();
        } else {
          throw new Error(data.message);
        }
      } catch (error) {
        mostrarAlerta(alertPaso1, error.message, "danger");
        btnIniciarReporte.disabled = false;
        btnIniciarReporte.innerHTML =
          '<i class="fas fa-arrow-right"></i> Iniciar Reporte';
      }
    });
  }

  // ============================================
  // EDITAR REPORTE: Cargar datos existentes
  // ============================================
  const reporteIdInput = document.getElementById("reporte_id");
  if (reporteIdInput && reporteIdInput.value) {
    reporteActualId = reporteIdInput.value;
    cargarDatosReporte();
  }

  // ============================================
  // BOTONES MODALES HT/HP
  // ============================================
  const btnAgregarHT = document.getElementById("btnAgregarHT");
  if (btnAgregarHT) {
    btnAgregarHT.addEventListener("click", function () {
      abrirModalHT();
    });
  }

  const btnAgregarHP = document.getElementById("btnAgregarHP");
  if (btnAgregarHP) {
    btnAgregarHP.addEventListener("click", function () {
      abrirModalHP();
    });
  }

  const btnAgregarCombustible = document.getElementById("btnAgregarCombustible");
  if (btnAgregarCombustible) {
    btnAgregarCombustible.addEventListener("click", function () {
      document.getElementById("formCombustible").reset();
      $("#modalCombustible").modal("show");
    });
  }

  // ============================================
  // FORMULARIO AGREGAR HT
  // ============================================
  const formHT = document.getElementById("formHT");
  if (formHT) {
    formHT.addEventListener("submit", async function (e) {
      e.preventDefault();
      await guardarActividad("HT");
    });
  }

  // ============================================
  // FORMULARIO AGREGAR HP
  // ============================================
  const formHP = document.getElementById("formHP");
  if (formHP) {
    formHP.addEventListener("submit", async function (e) {
      e.preventDefault();
      await guardarActividad("HP");
    });
  }

  // ============================================
  // FORMULARIO AGREGAR COMBUSTIBLE
  // ============================================
  const formCombustible = document.getElementById("formCombustible");
  if (formCombustible) {
    formCombustible.addEventListener("submit", async function (e) {
      e.preventDefault();

      const alertCombustible = document.getElementById("alertCombustible");
      const btnSubmit = this.querySelector('button[type="submit"]');

      btnSubmit.disabled = true;
      btnSubmit.innerHTML =
        '<i class="fas fa-spinner fa-spin"></i> Guardando...';
      alertCombustible.style.display = "none";

      try {
        const formData = new FormData();
        formData.append("action", "agregar_combustible");
        formData.append("reporte_id", reporteActualId);
        formData.append("horometro", document.getElementById("horometro_combustible").value);
        formData.append("hora_abastecimiento", document.getElementById("hora_abastecimiento").value);
        formData.append("galones", document.getElementById("galones").value);
        formData.append("observaciones", document.getElementById("observaciones_combustible").value);

        const response = await fetch("../../api/reportes.php", {
          method: "POST",
          body: formData,
        });

        const data = await response.json();

        if (data.success) {
          $("#modalCombustible").modal("hide");
          await cargarCombustiblesReporte();

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
        mostrarAlerta(alertCombustible, error.message, "danger");
      } finally {
        btnSubmit.disabled = false;
        btnSubmit.innerHTML = '<i class="fas fa-save"></i> Guardar';
      }
    });
  }

  // ============================================
  // BOTÓN FINALIZAR REPORTE
  // ============================================
  const btnFinalizarReporte = document.getElementById("btnFinalizarReporte");
  if (btnFinalizarReporte) {
    btnFinalizarReporte.addEventListener("click", async function () {
      const result = await Swal.fire({
        title: "¿Finalizar reporte?",
        html: "Una vez finalizado, <strong>no podrá editarlo</strong>.<br>¿Está seguro?",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#28a745",
        cancelButtonColor: "#6c757d",
        confirmButtonText: "Sí, finalizar",
        cancelButtonText: "Cancelar",
      });

      if (!result.isConfirmed) return;

      btnFinalizarReporte.disabled = true;
      btnFinalizarReporte.innerHTML =
        '<i class="fas fa-spinner fa-spin"></i> Finalizando...';

      try {
        const formData = new FormData();
        formData.append("action", "finalizar");
        formData.append("reporte_id", reporteActualId);
        formData.append("observaciones_generales", document.getElementById("observaciones_generales").value);

        const response = await fetch("../../api/reportes.php", {
          method: "POST",
          body: formData,
        });

        const data = await response.json();

        if (data.success) {
          Swal.fire({
            icon: "success",
            title: "¡Reporte finalizado!",
            text: data.message,
            confirmButtonText: "Ver reportes",
          }).then(() => {
            window.location.href = "listar.php";
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
        btnFinalizarReporte.disabled = false;
        btnFinalizarReporte.innerHTML =
          '<i class="fas fa-check"></i> Finalizar y Enviar';
      }
    });
  }

  // ============================================
  // BOTÓN GUARDAR BORRADOR / CAMBIOS
  // ============================================
  const btnGuardarBorrador = document.getElementById("btnGuardarBorrador");
  const btnGuardarCambios = document.getElementById("btnGuardarCambios");

  if (btnGuardarBorrador || btnGuardarCambios) {
    const btn = btnGuardarBorrador || btnGuardarCambios;

    btn.addEventListener("click", async function () {
      Swal.fire({
        icon: "success",
        title: "Cambios guardados",
        text: "El reporte se ha guardado como borrador",
        timer: 2000,
        showConfirmButton: false,
      });
    });
  }

  // ============================================
  // DATATABLE - LISTADO DE REPORTES
  // ============================================
  if (document.getElementById("tablaReportes")) {
    const tablaElement = document.getElementById("tablaReportes");
    const userRol = tablaElement.getAttribute("data-user-rol") || "operador";

    let columns;

    if (userRol === "admin" || userRol === "supervisor") {
      columns = [
        { title: "ID", width: "5%" },
        { title: "Fecha", width: "10%" },
        { title: "Operador", width: "15%" },
        { title: "Equipo", width: "15%" },
        { title: "H. Motor", width: "10%", className: "text-center" },
        { title: "HT", width: "8%", className: "text-center" },
        { title: "HP", width: "8%", className: "text-center" },
        { title: "Efic.", width: "8%", className: "text-center" },
        { title: "Estado", width: "10%", className: "text-center" },
        { title: "Acciones", width: "12%", orderable: false, searchable: false, className: "text-center" },
      ];
    } else {
      columns = [
        { title: "ID", width: "5%" },
        { title: "Fecha", width: "12%" },
        { title: "Equipo", width: "20%" },
        { title: "H. Motor", width: "10%", className: "text-center" },
        { title: "HT", width: "10%", className: "text-center" },
        { title: "HP", width: "10%", className: "text-center" },
        { title: "Estado", width: "12%", className: "text-center" },
        { title: "Acciones", width: "15%", orderable: false, searchable: false, className: "text-center" },
      ];
    }

    const tabla = $("#tablaReportes").DataTable({
      ajax: {
        url: "../../api/reportes.php?action=listar",
        dataSrc: "data",
        error: function (xhr, error, thrown) {
          console.error("Error al cargar reportes:", error);
          Swal.fire({
            icon: "error",
            title: "Error al cargar reportes",
            html: "<p>No se pudieron cargar los reportes.</p>",
          });
        },
      },
      columns: columns,
      language: {
        url: "//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json",
      },
      order: [[0, "desc"]],
      responsive: true,
      pageLength: 25,
      autoWidth: false,
    });

    console.log("✓ DataTable de reportes inicializado para rol:", userRol);
  }
});

// ============================================
// FUNCIONES AUXILIARES
// ============================================

/**
 * Cargar equipos filtrados por categoría del operador
 */
async function cargarEquiposFiltrados() {
  const selectEquipo = document.getElementById("equipo_id");
  const infoCategoria = document.getElementById("infoCategoria");

  try {
    const response = await fetch(
      "../../api/equipos.php?action=obtener_equipos_operador"
    );
    const data = await response.json();

    if (data.success) {
      const equipos = data.equipos;

      selectEquipo.innerHTML = '<option value="">Seleccionar equipo</option>';

      if (equipos.length === 0) {
        selectEquipo.innerHTML =
          '<option value="">No hay equipos disponibles</option>';
        if (infoCategoria) {
          infoCategoria.innerHTML =
            '<span class="text-danger">No se encontraron equipos de tu categoría</span>';
        }
        return;
      }

      let categoriaActual = "";
      equipos.forEach((equipo) => {
        if (categoriaActual !== equipo.categoria) {
          if (categoriaActual !== "") {
            selectEquipo.appendChild(document.createElement("optgroup")).label = "";
          }
          const optgroup = document.createElement("optgroup");
          optgroup.label = equipo.categoria;
          selectEquipo.appendChild(optgroup);
          categoriaActual = equipo.categoria;
        }

        const option = document.createElement("option");
        option.value = equipo.id;
        option.textContent =
          equipo.codigo +
          (equipo.descripcion ? " - " + equipo.descripcion : "");
        selectEquipo.lastElementChild.appendChild(option);
      });

      if (infoCategoria && data.categoria_operador !== "Todos") {
        infoCategoria.innerHTML = `<i class="fas fa-filter"></i> Mostrando equipos de categoría: <strong>${data.categoria_operador}</strong>`;
      }

      console.log("✓ Equipos filtrados cargados:", equipos.length);
    } else {
      throw new Error(data.message);
    }
  } catch (error) {
    console.error("Error al cargar equipos:", error);
    selectEquipo.innerHTML =
      '<option value="">Error al cargar equipos</option>';
  }
}

/**
 * Cargar actividades HT desde la API
 */
async function cargarActividadesHT() {
  try {
    const response = await fetch(
      "../../api/actividades_ht.php?action=listar&para_select=1"
    );
    const data = await response.json();

    if (data.success) {
      actividadesHT = data.actividades || [];
      const select = document.getElementById("actividad_ht_id");
      if (select) {
        select.innerHTML = '<option value="">Seleccionar actividad</option>';

        actividadesHT.forEach((act) => {
          if (act.estado == 1) {
            const option = document.createElement("option");
            option.value = act.id;
            option.textContent = act.codigo ? `${act.codigo} - ${act.nombre}` : act.nombre;
            select.appendChild(option);
          }
        });
      }

      console.log("✓ Actividades HT cargadas:", actividadesHT.length);
    }
  } catch (error) {
    console.error("Error al cargar actividades HT:", error);
  }
}

/**
 * Cargar motivos HP desde la API
 */
async function cargarMotivosHP() {
  try {
    const response = await fetch(
      "../../api/motivos_hp.php?action=listar&para_select=1"
    );
    const data = await response.json();

    if (data.success) {
      motivosHP = data.motivos || [];
      const select = document.getElementById("motivo_hp_id");
      if (select) {
        select.innerHTML = '<option value="">Seleccionar motivo de parada</option>';

        motivosHP.forEach((mot) => {
          if (mot.estado == 1) {
            const option = document.createElement("option");
            option.value = mot.id;
            option.textContent = mot.codigo ? `${mot.codigo} - ${mot.nombre}` : mot.nombre;
            select.appendChild(option);
          }
        });
      }

      console.log("✓ Motivos HP cargados:", motivosHP.length);
    }
  } catch (error) {
    console.error("Error al cargar motivos HP:", error);
  }
}

/**
 * Abrir modal para agregar HT
 */
function abrirModalHT() {
  document.getElementById("formHT")?.reset();
  $("#modalHT").modal("show");
}

/**
 * Abrir modal para agregar HP
 */
function abrirModalHP() {
  document.getElementById("formHP")?.reset();
  $("#modalHP").modal("show");
}

/**
 * Guardar actividad (HT o HP)
 */
async function guardarActividad(tipo) {
  const formId = tipo === "HT" ? "formHT" : "formHP";
  const alertId = tipo === "HT" ? "alertHT" : "alertHP";
  const modalId = tipo === "HT" ? "modalHT" : "modalHP";
  
  const form = document.getElementById(formId);
  const alertElement = document.getElementById(alertId);
  const btnSubmit = form.querySelector('button[type="submit"]');

  btnSubmit.disabled = true;
  btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
  alertElement.style.display = "none";

  try {
    const formData = new FormData();
    formData.append("action", "agregar");
    formData.append("reporte_id", reporteActualId);
    formData.append("tipo_hora", tipo);
    formData.append("hora_inicio", document.getElementById(`hora_inicio_${tipo.toLowerCase()}`).value);
    formData.append("hora_fin", document.getElementById(`hora_fin_${tipo.toLowerCase()}`).value);
    formData.append("observaciones", document.getElementById(`observaciones_${tipo.toLowerCase()}`).value);

    if (tipo === "HT") {
      formData.append("actividad_ht_id", document.getElementById("actividad_ht_id").value);
    } else {
      formData.append("motivo_hp_id", document.getElementById("motivo_hp_id").value);
    }

    const response = await fetch("../../api/reportes_detalle.php", {
      method: "POST",
      body: formData,
    });

    const data = await response.json();

    if (data.success) {
      $(`#${modalId}`).modal("hide");
      await cargarActividadesReporte();

      Swal.fire({
        icon: "success",
        title: `¡${tipo} agregada!`,
        text: data.message,
        timer: 2000,
        showConfirmButton: false,
      });
    } else {
      throw new Error(data.message);
    }
  } catch (error) {
    mostrarAlerta(alertElement, error.message, "danger");
  } finally {
    btnSubmit.disabled = false;
    btnSubmit.innerHTML = '<i class="fas fa-save"></i> Guardar';
  }
}

/**
 * Cargar datos del reporte para editar
 */
async function cargarDatosReporte() {
  try {
    await cargarActividadesHT();
    await cargarMotivosHP();
    await cargarActividadesReporte();
    await cargarCombustiblesReporte();

    console.log("✓ Datos del reporte cargados");
  } catch (error) {
    console.error("Error al cargar datos del reporte:", error);
  }
}

/**
 * Cargar actividades del reporte (HT y HP)
 */
async function cargarActividadesReporte() {
  try {
    const response = await fetch(
      `../../api/reportes_detalle.php?action=listar&reporte_id=${reporteActualId}`
    );
    const data = await response.json();

    if (data.success) {
      const container = document.getElementById("listaActividades");
      
      if (data.ht.length === 0 && data.hp.length === 0) {
        container.innerHTML =
          '<p class="text-muted text-center"><i class="fas fa-info-circle"></i> No hay actividades registradas.</p>';
        
        const btnEliminar = document.getElementById("btnEliminarReporte");
        if (btnEliminar) {
          btnEliminar.style.display = "inline-block";
        }
        return;
      }

      let html = '<div class="row"><div class="col-md-6">';
      
      // TABLA HT
      html += '<h5 class="text-success"><i class="fas fa-tools"></i> Horas Trabajadas (HT)</h5>';
      if (data.ht.length > 0) {
        html += '<table class="table table-sm table-bordered">';
        html += '<thead class="thead-light"><tr><th>Hora</th><th>Actividad</th><th>Horas</th><th>Acción</th></tr></thead><tbody>';
        
        data.ht.forEach((act) => {
          html += `<tr>
            <td>${act.hora_inicio} - ${act.hora_fin}</td>
            <td>${act.actividad_nombre}</td>
            <td><span class="badge badge-success">${parseFloat(act.horas_transcurridas).toFixed(2)} hrs</span></td>
            <td><button onclick="eliminarActividad(${act.id})" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button></td>
          </tr>`;
        });
        
        html += `</tbody><tfoot><tr class="font-weight-bold"><td colspan="2">TOTAL HT:</td><td colspan="2"><span class="badge badge-success">${data.totales.total_ht} hrs</span></td></tr></tfoot></table>`;
      } else {
        html += '<p class="text-muted">Sin horas trabajadas</p>';
      }
      
      html += '</div><div class="col-md-6">';
      
      // TABLA HP
      html += '<h5 class="text-warning"><i class="fas fa-pause-circle"></i> Horas Paradas (HP)</h5>';
      if (data.hp.length > 0) {
        html += '<table class="table table-sm table-bordered">';
        html += '<thead class="thead-light"><tr><th>Hora</th><th>Motivo</th><th>Horas</th><th>Acción</th></tr></thead><tbody>';
        
        data.hp.forEach((act) => {
          html += `<tr>
            <td>${act.hora_inicio} - ${act.hora_fin}</td>
            <td>${act.motivo_nombre}</td>
            <td><span class="badge badge-warning">${parseFloat(act.horas_transcurridas).toFixed(2)} hrs</span></td>
            <td><button onclick="eliminarActividad(${act.id})" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button></td>
          </tr>`;
        });
        
        html += `</tbody><tfoot><tr class="font-weight-bold"><td colspan="2">TOTAL HP:</td><td colspan="2"><span class="badge badge-warning">${data.totales.total_hp} hrs</span></td></tr></tfoot></table>`;
      } else {
        html += '<p class="text-muted">Sin horas paradas</p>';
      }
      
      html += '</div></div>';
      
      container.innerHTML = html;

      const btnEliminar = document.getElementById("btnEliminarReporte");
      if (btnEliminar) {
        btnEliminar.style.display = "none";
      }
    }
  } catch (error) {
    console.error("Error al cargar actividades:", error);
  }
}

/**
 * Cargar combustibles del reporte
 */
async function cargarCombustiblesReporte() {
  try {
    const response = await fetch(
      `../../api/reportes.php?action=obtener&id=${reporteActualId}`
    );
    const data = await response.json();

    if (data.success && data.reporte) {
      const combustibles = []; // Obtener de otra API si existe
      const container = document.getElementById("listaCombustible");

      if (!combustibles || combustibles.length === 0) {
        container.innerHTML =
          '<p class="text-muted text-center"><i class="fas fa-info-circle"></i> No hay abastecimientos registrados.</p>';
        return;
      }

      // Renderizar combustibles...
    }
  } catch (error) {
    console.error("Error al cargar combustibles:", error);
  }
}

/**
 * Eliminar actividad
 */
async function eliminarActividad(actividadId) {
  const result = await Swal.fire({
    title: "¿Eliminar actividad?",
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
    formData.append("action", "eliminar");
    formData.append("id", actividadId);

    const response = await fetch("../../api/reportes_detalle.php", {
      method: "POST",
      body: formData,
    });

    const data = await response.json();

    if (data.success) {
      await cargarActividadesReporte();
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

/**
 * Eliminar combustible
 */
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
      await cargarCombustiblesReporte();
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

/**
 * Mostrar alerta en un elemento
 */
function mostrarAlerta(elemento, mensaje, tipo) {
  elemento.className = `alert alert-${tipo}`;
  elemento.textContent = mensaje;
  elemento.style.display = "block";
}

/**
 * Eliminar reporte vacío
 */
async function eliminarReporte() {
  const result = await Swal.fire({
    title: "¿Eliminar reporte?",
    html:
      "<p>Este reporte no tiene actividades registradas.</p>" +
      '<p class="text-danger"><strong>Esta acción no se puede deshacer.</strong></p>',
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#d33",
    cancelButtonColor: "#6c757d",
    confirmButtonText: "Sí, eliminar",
    cancelButtonText: "Cancelar",
  });

  if (!result.isConfirmed) return;

  try {
    const formData = new FormData();
    formData.append("action", "eliminar");
    formData.append("reporte_id", reporteActualId);

    const response = await fetch("../../api/reportes.php", {
      method: "POST",
      body: formData,
    });

    const data = await response.json();

    if (data.success) {
      Swal.fire({
        icon: "success",
        title: "¡Eliminado!",
        text: data.message,
        confirmButtonText: "Ir a reportes",
      }).then(() => {
        window.location.href = "listar.php";
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

console.log("✓ Funciones de reportes V3.0 disponibles");