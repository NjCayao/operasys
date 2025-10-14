/**
 * OperaSys - JavaScript de Reportes
 * Archivo: assets/js/reportes.js
 * Descripción: CRUD completo de reportes con actividades y combustible
 * MODIFICADO: Carga de equipos filtrados por categoría del operador
 */

// Variables globales
let reporteActualId = null;
let tiposTrabajo = [];
let fasesCosto = [];

document.addEventListener("DOMContentLoaded", function () {
  console.log("✓ Script de reportes cargado");

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
      const alertPaso1 = document.getElementById("alertPaso1");

      if (!equipoId) {
        mostrarAlerta(alertPaso1, "Debe seleccionar un equipo", "danger");
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
        formData.append("fecha", new Date().toISOString().split("T")[0]); // Fecha de hoy

        const response = await fetch("../../api/reportes.php", {
          method: "POST",
          body: formData,
        });

        const data = await response.json();

        if (data.success) {
          reporteActualId = data.reporte_id;

          // Mostrar nombre del equipo seleccionado
          const selectEquipo = document.getElementById("equipo_id");
          const equipoTexto =
            selectEquipo.options[selectEquipo.selectedIndex].text;
          document.getElementById("equipoSeleccionado").textContent =
            equipoTexto;

          // Ocultar paso 1, mostrar paso 2
          document.getElementById("paso1").style.display = "none";
          document.getElementById("paso2").style.display = "block";

          // Cargar catálogos
          await cargarTiposTrabajo();
          await cargarFasesCosto();
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
  // BOTONES MODALES
  // ============================================
  const btnAgregarActividad = document.getElementById("btnAgregarActividad");
  if (btnAgregarActividad) {
    btnAgregarActividad.addEventListener("click", function () {
      limpiarFormularioActividad();
      $("#modalActividad").modal("show");
    });
  }

  const btnAgregarCombustible = document.getElementById(
    "btnAgregarCombustible"
  );
  if (btnAgregarCombustible) {
    btnAgregarCombustible.addEventListener("click", function () {
      document.getElementById("formCombustible").reset();
      $("#modalCombustible").modal("show");
    });
  }

  // ============================================
  // FORMULARIO AGREGAR ACTIVIDAD
  // ============================================
  const formActividad = document.getElementById("formActividad");
  if (formActividad) {
    formActividad.addEventListener("submit", async function (e) {
      e.preventDefault();

      const alertActividad = document.getElementById("alertActividad");
      const btnSubmit = this.querySelector('button[type="submit"]');

      btnSubmit.disabled = true;
      btnSubmit.innerHTML =
        '<i class="fas fa-spinner fa-spin"></i> Guardando...';
      alertActividad.style.display = "none";

      try {
        const formData = new FormData();
        formData.append("action", "agregar_actividad");
        formData.append("reporte_id", reporteActualId);
        formData.append(
          "tipo_trabajo_id",
          document.getElementById("tipo_trabajo_id").value
        );
        formData.append(
          "fase_costo_id",
          document.getElementById("fase_costo_id").value
        );
        formData.append(
          "horometro_inicial",
          document.getElementById("horometro_inicial").value
        );
        formData.append(
          "horometro_final",
          document.getElementById("horometro_final").value
        );
        formData.append(
          "observaciones",
          document.getElementById("observaciones_actividad").value
        );

        const response = await fetch("../../api/reportes.php", {
          method: "POST",
          body: formData,
        });

        const data = await response.json();

        if (data.success) {
          $("#modalActividad").modal("hide");
          await cargarActividadesReporte();

          Swal.fire({
            icon: "success",
            title: "¡Actividad agregada!",
            text: data.message,
            timer: 2000,
            showConfirmButton: false,
          });
        } else {
          throw new Error(data.message);
        }
      } catch (error) {
        mostrarAlerta(alertActividad, error.message, "danger");
      } finally {
        btnSubmit.disabled = false;
        btnSubmit.innerHTML = '<i class="fas fa-save"></i> Guardar Actividad';
      }
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
        formData.append(
          "horometro",
          document.getElementById("horometro_combustible").value
        );
        formData.append("galones", document.getElementById("galones").value);
        formData.append(
          "observaciones",
          document.getElementById("observaciones_combustible").value
        );

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
        formData.append(
          "observaciones_generales",
          document.getElementById("observaciones_generales").value
        );

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
    // Verificar el rol del usuario desde un atributo data
    const tablaElement = document.getElementById("tablaReportes");
    const userRol = tablaElement.getAttribute("data-user-rol") || "operador";

    // Configurar columnas según el rol
    let columns;

    if (userRol === "admin" || userRol === "supervisor") {
      // Admin/Supervisor: ID, Fecha, Operador, Equipo, Actividades, Horas, Estado, Acciones
      columns = [
        { title: "ID", width: "5%" },
        { title: "Fecha", width: "10%" },
        { title: "Operador", width: "15%" },
        { title: "Equipo", width: "15%" },
        { title: "Actividades", width: "10%", className: "text-center" },
        { title: "Horas", width: "10%", className: "text-center" },
        { title: "Estado", width: "12%", className: "text-center" },
        {
          title: "Acciones",
          width: "15%",
          orderable: false,
          searchable: false,
          className: "text-center",
        },
      ];
    } else {
      // Operador: ID, Fecha, Equipo, Actividades, Horas, Estado, Acciones
      columns = [
        { title: "ID", width: "5%" },
        { title: "Fecha", width: "12%" },
        { title: "Equipo", width: "20%" },
        { title: "Actividades", width: "12%", className: "text-center" },
        { title: "Horas", width: "12%", className: "text-center" },
        { title: "Estado", width: "15%", className: "text-center" },
        {
          title: "Acciones",
          width: "15%",
          orderable: false,
          searchable: false,
          className: "text-center",
        },
      ];
    }

    const tabla = $("#tablaReportes").DataTable({
      ajax: {
        url: "../../api/reportes.php?action=listar",
        dataSrc: "data",
        error: function (xhr, error, thrown) {
          console.error("Error al cargar reportes:", error);
          console.error("Respuesta:", xhr.responseText);

          Swal.fire({
            icon: "error",
            title: "Error al cargar reportes",
            html:
              "<p>No se pudieron cargar los reportes.</p>" +
              "<p><small>Revisa la consola del navegador (F12) para más detalles.</small></p>",
            footer:
              '<a href="javascript:location.reload()">Click aquí para recargar</a>',
          });
        },
      },
      columns: columns,
      language: {
        decimal: "",
        emptyTable: "No hay reportes disponibles",
        info: "Mostrando _START_ a _END_ de _TOTAL_ reportes",
        infoEmpty: "Mostrando 0 a 0 de 0 reportes",
        infoFiltered: "(filtrado de _MAX_ reportes totales)",
        lengthMenu: "Mostrar _MENU_ reportes",
        loadingRecords: "Cargando...",
        processing: "Procesando...",
        search: "Buscar:",
        zeroRecords: "No se encontraron reportes",
        paginate: {
          first: "Primero",
          last: "Último",
          next: "Siguiente",
          previous: "Anterior",
        },
      },
      order: [[0, "desc"]],
      responsive: true,
      pageLength: 25,
      lengthMenu: [
        [10, 25, 50, 100],
        [10, 25, 50, 100],
      ],
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

      // Agrupar por categoría si hay más de una
      let categoriaActual = "";
      equipos.forEach((equipo) => {
        if (categoriaActual !== equipo.categoria) {
          if (categoriaActual !== "")
            selectEquipo.appendChild(document.createElement("optgroup")).label =
              "";
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

      // Mostrar información de filtrado
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
 * Cargar tipos de trabajo desde la API
 */
async function cargarTiposTrabajo() {
  try {
    const response = await fetch(
      "../../api/tipos_trabajo.php?action=listar&para_select=1"
    );
    const data = await response.json();

    if (data.success) {
      tiposTrabajo = data.tipos;
      const select = document.getElementById("tipo_trabajo_id");
      select.innerHTML =
        '<option value="">Seleccionar tipo de trabajo</option>';

      tiposTrabajo.forEach((tipo) => {
        if (tipo.estado == 1) {
          const option = document.createElement("option");
          option.value = tipo.id;
          option.textContent = tipo.nombre;
          select.appendChild(option);
        }
      });

      console.log("✓ Tipos de trabajo cargados:", tiposTrabajo.length);
    }
  } catch (error) {
    console.error("Error al cargar tipos de trabajo:", error);
  }
}

/**
 * Cargar fases de costo desde la API
 */
async function cargarFasesCosto() {
  try {
    const response = await fetch(
      "../../api/fases_costo.php?action=listar&para_select=1"
    );
    const data = await response.json();

    if (data.success) {
      fasesCosto = data.fases;
      const select = document.getElementById("fase_costo_id");
      select.innerHTML = '<option value="">Seleccionar fase de costo</option>';

      fasesCosto.forEach((fase) => {
        if (fase.estado == 1) {
          const option = document.createElement("option");
          option.value = fase.id;
          option.textContent = fase.codigo + " - " + fase.descripcion;
          select.appendChild(option);
        }
      });

      console.log("✓ Fases de costo cargadas:", fasesCosto.length);
    }
  } catch (error) {
    console.error("Error al cargar fases de costo:", error);
  }
}

/**
 * Cargar datos del reporte para editar
 */
async function cargarDatosReporte() {
  try {
    // Cargar catálogos primero
    await cargarTiposTrabajo();
    await cargarFasesCosto();

    // Cargar actividades y combustibles
    await cargarActividadesReporte();
    await cargarCombustiblesReporte();

    console.log("✓ Datos del reporte cargados");
  } catch (error) {
    console.error("Error al cargar datos del reporte:", error);
  }
}

/**
 * Cargar actividades del reporte
 */
async function cargarActividadesReporte() {
    try {
        const response = await fetch(`../../api/reportes.php?action=obtener_reporte&id=${reporteActualId}`);
        const data = await response.json();
        
        if (data.success) {
            const actividades = data.actividades;
            const container = document.getElementById('listaActividades');
            
            if (actividades.length === 0) {
                container.innerHTML = '<p class="text-muted text-center"><i class="fas fa-info-circle"></i> No hay actividades registradas.</p>';
                
                // Verificar si mostrar botón eliminar
                const btnEliminar = document.getElementById('btnEliminarReporte');
                if (btnEliminar) {
                    btnEliminar.style.display = 'inline-block';
                }
                return;
            }
            
            // AQUÍ ESTABA EL ERROR: faltaba declarar html
            let html = '<div class="table-responsive"><table class="table table-bordered table-hover">';
            html += '<thead class="thead-light"><tr>';
            html += '<th width="5%">#</th>';
            html += '<th>Tipo de Trabajo</th>';
            html += '<th>Fase de Costo</th>';
            html += '<th width="12%">Horómetro Inicial</th>';
            html += '<th width="12%">Horómetro Final</th>';
            html += '<th width="10%">Horas</th>';
            html += '<th>Observaciones</th>';
            html += '<th width="10%">Acción</th>';
            html += '</tr></thead><tbody>';
            
            actividades.forEach((act, index) => {
                html += `<tr>
                    <td class="text-center">${index + 1}</td>
                    <td>${act.tipo_trabajo}</td>
                    <td><strong>${act.fase_codigo}</strong><br><small class="text-muted">${act.fase_descripcion}</small></td>
                    <td class="text-center">${parseFloat(act.horometro_inicial).toFixed(1)}</td>
                    <td class="text-center">${parseFloat(act.horometro_final).toFixed(1)}</td>
                    <td class="text-center"><span class="badge badge-info">${parseFloat(act.horas_trabajadas).toFixed(2)} hrs</span></td>
                    <td>${act.observaciones || '<em class="text-muted">Sin observaciones</em>'}</td>
                    <td class="text-center">
                        <button onclick="eliminarActividad(${act.id})" class="btn btn-sm btn-danger" title="Eliminar">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>`;
            });
            
            html += '</tbody></table></div>';
            container.innerHTML = html;
            
            // Ocultar botón eliminar si hay actividades
            const btnEliminar = document.getElementById('btnEliminarReporte');
            if (btnEliminar) {
                btnEliminar.style.display = 'none';
            }
        }
        
    } catch (error) {
        console.error('Error al cargar actividades:', error);
    }
}

/**
 * Cargar combustibles del reporte
 */
async function cargarCombustiblesReporte() {
  try {
    const response = await fetch(
      `../../api/reportes.php?action=obtener_reporte&id=${reporteActualId}`
    );
    const data = await response.json();

    if (data.success) {
      const combustibles = data.combustibles;
      const container = document.getElementById("listaCombustible");

      if (combustibles.length === 0) {
        container.innerHTML =
          '<p class="text-muted text-center"><i class="fas fa-info-circle"></i> No hay abastecimientos registrados.</p>';
        return;
      }

      let html =
        '<div class="table-responsive"><table class="table table-bordered table-hover">';
      html += '<thead class="thead-light"><tr>';
      html += '<th width="10%">#</th>';
      html += "<th>Horómetro</th>";
      html += "<th>Galones</th>";
      html += "<th>Fecha/Hora</th>";
      html += "<th>Observaciones</th>";
      html += '<th width="10%">Acción</th>';
      html += "</tr></thead><tbody>";

      combustibles.forEach((comb, index) => {
        const fecha = new Date(comb.fecha_hora);
        const fechaFormato = fecha.toLocaleString("es-PE");

        html += `<tr>
                    <td class="text-center">${index + 1}</td>
                    <td class="text-center">${parseFloat(
                      comb.horometro
                    ).toFixed(1)}</td>
                    <td class="text-center"><strong>${parseFloat(
                      comb.galones
                    ).toFixed(2)}</strong> gal</td>
                    <td>${fechaFormato}</td>
                    <td>${
                      comb.observaciones ||
                      '<em class="text-muted">Sin observaciones</em>'
                    }</td>
                    <td class="text-center">
                        <button onclick="eliminarCombustible(${
                          comb.id
                        })" class="btn btn-sm btn-danger" title="Eliminar">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>`;
      });

      html += "</tbody></table></div>";
      container.innerHTML = html;
    }
  } catch (error) {
    console.error("Error al cargar combustibles:", error);
  }
}

/**
 * Limpiar formulario de actividad
 */
function limpiarFormularioActividad() {
  document.getElementById("formActividad").reset();

  // Solo limpiar actividad_id si existe (en editar.php)
  const actividadIdInput = document.getElementById("actividad_id");
  if (actividadIdInput) {
    actividadIdInput.value = "";
  }

  // Solo cambiar título si existe (en editar.php)
  const tituloModal = document.getElementById("tituloModalActividad");
  if (tituloModal) {
    tituloModal.textContent = "Agregar Actividad";
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
    formData.append("action", "eliminar_actividad");
    formData.append("actividad_id", actividadId);

    const response = await fetch("../../api/reportes.php", {
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
    formData.append("combustible_id", combustibleId);

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
 * Verificar si el reporte está vacío y mostrar botón eliminar
 */
async function verificarReporteVacio() {
  const btnEliminar = document.getElementById("btnEliminarReporte");

  if (!btnEliminar || !reporteActualId) return;

  try {
    const response = await fetch(
      `../../api/reportes.php?action=obtener_reporte&id=${reporteActualId}`
    );
    const data = await response.json();

    if (data.success) {
      const actividades = data.actividades || [];

      // Mostrar botón eliminar SOLO si NO hay actividades
      if (actividades.length === 0) {
        btnEliminar.style.display = "inline-block";
      } else {
        btnEliminar.style.display = "none";
      }
    }
  } catch (error) {
    console.error("Error al verificar reporte:", error);
  }
}

/**
 * Eliminar reporte vacío
 */
async function eliminarReporte() {
  const result = await Swal.fire({
    title: "¿Eliminar reporte?",
    html:
      "<p>Este reporte no tiene actividades registradas.</p>" +
      '<p class="text-danger"><strong>Esta acción no se puede deshacer.</strong></p>' +
      "<p>¿Está seguro que desea eliminarlo?</p>",
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

// Event listener para botón eliminar (solo en editar.php)
document.addEventListener("DOMContentLoaded", function () {
  const btnEliminarReporte = document.getElementById("btnEliminarReporte");

  if (btnEliminarReporte) {
    // Verificar si mostrar botón al cargar
    verificarReporteVacio();

    // Agregar event listener
    btnEliminarReporte.addEventListener("click", eliminarReporte);
  }
});

console.log("✓ Funciones de reportes disponibles");
