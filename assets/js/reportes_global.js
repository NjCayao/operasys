/**
 * OperaSys - Reportes Globales JS
 * Archivo: assets/js/reportes_global.js
 * Descripción: Lógica para ver todos los reportes con filtros y exportación
 */

$(document).ready(function () {
  cargarOperadores();
  cargarCategorias();
  cargarFases();
  inicializarTabla();

  // Event Listeners
  $("#btnAplicarFiltros").on("click", aplicarFiltros);
  $("#btnExportarExcel").on("click", exportarExcel);
  $("#btnExportarPDF").on("click", exportarPDF);
});

let tablaReportes;

/**
 * Cargar lista de operadores para el filtro
 */
function cargarOperadores() {
  $.ajax({
    url: "../../api/usuarios.php?action=listar",
    method: "GET",
    dataType: "json",
    success: function (response) {
      if (response.success) {
        let html = '<option value="">Todos</option>';
        response.data.forEach(function (user) {
          if (user.rol === "operador") {
            html += `<option value="${user.id}">${user.nombre_completo}</option>`;
          }
        });
        $("#filtro_operador").html(html);
      }
    },
    error: function () {
      console.error("Error al cargar operadores");
    },
  });
}

/**
 * Cargar fases de costo para el filtro
 */
function cargarFases() {
  $.ajax({
    url: "../../api/fases_costo.php?action=listar&para_select=1",
    method: "GET",
    dataType: "json",
    success: function (response) {
      if (response.success) {
        let html = '<option value="">Todas</option>';

        // La API devuelve "fases" cuando para_select=1
        const fases = response.fases || response.data || [];

        fases.forEach(function (fase) {
          if (fase.estado == 1) {
            html += `<option value="${fase.id}">${fase.codigo} - ${fase.descripcion}</option>`;
          }
        });

        $("#filtro_fase").html(html);
      }
    },
    error: function () {
      console.error("Error al cargar fases");
    },
  });
}

/**
 * Cargar categorías de equipos para el filtro
 */
function cargarCategorias() {
  $.ajax({
    url: "../../api/equipos.php?action=obtener_categorias",
    method: "GET",
    dataType: "json",
    success: function (response) {
      if (response.success) {
        let html = '<option value="">Todas</option>';
        response.data.forEach(function (cat) {
          html += `<option value="${cat.categoria}">${cat.categoria}</option>`;
        });
        $("#filtro_categoria").html(html);
      }
    },
    error: function () {
      console.error("Error al cargar categorías");
    },
  });
}

/**
 * Inicializar DataTable
 */
function inicializarTabla() {
  const rol = $("#tablaReportesGlobales").data("rol");

  tablaReportes = $("#tablaReportesGlobales").DataTable({
    ajax: {
      url: "../../api/reportes_global.php?action=listar",
      type: "GET",
      dataSrc: function (json) {
        if (json.success) {
          return json.data;
        }
        return [];
      },
    },
    columns: [
      { data: "id" },
      { data: "fecha" },
      { data: "operador" },
      {
        data: null,
        render: function (data) {
          return `<span class="badge badge-info">${data.equipo_codigo}</span><br>
                            <small class="text-muted">${data.equipo_categoria}</small>`;
        },
      },
      {
        data: "fases_usadas",
        render: function (data) {
          return data
            ? `<small>${data}</small>`
            : '<span class="text-muted">N/A</span>';
        },
      },
      {
        data: "total_actividades",
        render: function (data) {
          return `<span class="badge badge-primary">${data || 0}</span>`;
        },
      },
      {
        data: "total_horas",
        render: function (data) {
          return data ? parseFloat(data).toFixed(2) + " hrs" : "0.00 hrs";
        },
      },
      {
        data: null,
        render: function (data) {
          if (data.total_combustible > 0) {
            return `<span class="badge badge-success">
                                    <i class="fas fa-gas-pump"></i> ${parseFloat(
                                      data.total_galones
                                    ).toFixed(1)} gal
                                </span>`;
          }
          return '<span class="text-muted">-</span>';
        },
      },
      {
        data: "estado",
        render: function (data) {
          const badge = data === "finalizado" ? "success" : "warning";
          const texto = data === "finalizado" ? "Finalizado" : "Borrador";
          return `<span class="badge badge-${badge}">${texto}</span>`;
        },
      },
      {
        data: null,
        orderable: false,
        render: function (data) {
          let botones = `
                        <a href="../../modules/reportes/ver.php?id=${data.id}" 
                           class="btn btn-sm btn-info" 
                           title="Ver detalles">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="../../api/pdf.php?id=${data.id}"
                           class="btn btn-sm btn-danger" 
                           title="Descargar PDF"
                           target="_blank">
                            <i class="fas fa-file-pdf"></i>
                        </a>
                    `;

          // Solo admin puede editar y eliminar
          if (rol === "admin") {
            if (data.estado === "borrador") {
              botones += `
                                <a href="../../modules/reportes/editar.php?id=${data.id}" 
                                   class="btn btn-sm btn-warning" 
                                   title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                            `;
            }
            if (data.total_actividades == 0) {
                    botones += `
                <button onclick="eliminarReporte(${data.id})" 
                        class="btn btn-sm btn-danger" 
                        title="Eliminar">
                    <i class="fas fa-trash"></i>
                </button>
            `;
                    } else {
                    botones += `
                <button class="btn btn-sm btn-secondary" 
                        disabled
                        title="No se puede eliminar: tiene ${data.total_actividades} actividad(es)">
                    <i class="fas fa-lock"></i>
                </button>
            `;
            }
          }

          return botones;
        },
      },
    ],
    order: [[0, "desc"]],
    language: {
      url: "//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json",
      emptyTable: "No hay reportes registrados",
      zeroRecords: "No se encontraron resultados",
    },
    responsive: true,
    pageLength: 25,
    lengthMenu: [
      [10, 25, 50, 100, -1],
      [10, 25, 50, 100, "Todos"],
    ],
  });
}

/**
 * Aplicar filtros a la tabla
 */
function aplicarFiltros() {
  const filtros = {
    operador_id: $("#filtro_operador").val(),
    categoria: $("#filtro_categoria").val(),
    fase_costo_id: $("#filtro_fase").val(),
    fecha_desde: $("#filtro_fecha_desde").val(),
    fecha_hasta: $("#filtro_fecha_hasta").val(),
  };

  // Reconstruir URL con filtros
  let url = "../../api/reportes_global.php?action=listar";
  Object.keys(filtros).forEach((key) => {
    if (filtros[key]) {
      url += `&${key}=${filtros[key]}`;
    }
  });

  // Recargar tabla con filtros
  tablaReportes.ajax.url(url).load();

  Swal.fire({
    icon: "success",
    title: "Filtros aplicados",
    text: "La tabla se ha actualizado",
    timer: 1500,
    showConfirmButton: false,
  });
}

/**
 * Exportar a Excel
 */
function exportarExcel() {
  const filtros = new URLSearchParams({
    operador_id: $("#filtro_operador").val() || "",
    categoria: $("#filtro_categoria").val() || "",
    fase_costo_id: $("#filtro_fase").val() || "",
    fecha_desde: $("#filtro_fecha_desde").val() || "",
    fecha_hasta: $("#filtro_fecha_hasta").val() || "",
  });

  window.location.href = `../../api/reportes_global.php?action=exportar_excel&${filtros.toString()}`;

  Swal.fire({
    icon: "success",
    title: "Exportando...",
    text: "Se está generando el archivo Excel",
    timer: 2000,
    showConfirmButton: false,
  });
}

/**
 * Exportar a PDF
 */
function exportarPDF() {
  const filtros = new URLSearchParams({
    operador_id: $("#filtro_operador").val() || "",
    categoria: $("#filtro_categoria").val() || "",
    fase_costo_id: $("#filtro_fase").val() || "",
    fecha_desde: $("#filtro_fecha_desde").val() || "",
    fecha_hasta: $("#filtro_fecha_hasta").val() || "",
  });

  window.open(
    `../../api/reportes_global.php?action=exportar_pdf&${filtros.toString()}`,
    "_blank"
  );

  Swal.fire({
    icon: "success",
    title: "Exportando...",
    text: "Se está generando el archivo PDF",
    timer: 2000,
    showConfirmButton: false,
  });
}

/**
 * Eliminar reporte (solo admin)
 */
function eliminarReporte(id) {
  Swal.fire({
    title: "¿Está seguro?",
    text: "Esta acción no se puede deshacer",
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#d33",
    cancelButtonColor: "#3085d6",
    confirmButtonText: "Sí, eliminar",
    cancelButtonText: "Cancelar",
  }).then((result) => {
    if (result.isConfirmed) {
      $.ajax({
        url: "../../api/reportes.php",
        method: "POST",
        data: {
          action: "eliminar",
          reporte_id: id,
        },
        dataType: "json",
        success: function (response) {
          if (response.success) {
            Swal.fire({
              icon: "success",
              title: "Eliminado",
              text: response.message,
              timer: 1500,
              showConfirmButton: false,
            });
            tablaReportes.ajax.reload();
          } else {
            Swal.fire({
              icon: "error",
              title: "Error",
              text: response.message,
            });
          }
        },
        error: function () {
          Swal.fire({
            icon: "error",
            title: "Error",
            text: "No se pudo eliminar el reporte",
          });
        },
      });
    }
  });
}
