/**
 * OperaSys - JavaScript de Gestión de Usuarios
 * Archivo: assets/js/usuarios.js
 * Descripción: Funciones para listar, crear y gestionar usuarios
 */

let tablaUsuarios;

// Cargar usuarios al iniciar
$(document).ready(function () {
  cargarUsuarios();
});

// Mostrar/Ocultar categoría según rol
$(document).on("change", "#modalRol", function () {
  const rol = $(this).val();
  const categoriaContainer = $("#modalCategoriaContainer");
  const categoriaSelect = $("#modalCategoria");

  if (rol === "operador") {
    categoriaContainer.show();
    categoriaSelect.prop("required", true);
  } else {
    categoriaContainer.hide();
    categoriaSelect.prop("required", false);
    categoriaSelect.val("");
  }
});

// Validar DNI solo números
$(document).on("input", "#modalDni", function () {
  this.value = this.value.replace(/[^0-9]/g, "");
});

// Función para cargar usuarios
async function cargarUsuarios() {
  try {
    // Obtener filtro de rol de la URL
    const urlParams = new URLSearchParams(window.location.search);
    const filtroRol = urlParams.get("rol") || "";

    // Construir URL con filtro
    let apiUrl = "../../api/usuarios.php?action=listar";
    if (filtroRol) {
      apiUrl += "&rol=" + filtroRol;
    }

    console.log("API URL:", apiUrl); // Para debug

    const response = await fetch(apiUrl);
    const result = await response.json();

    console.log("Resultado:", result);

    if (result.success) {
      // Destruir tabla si existe
      if (tablaUsuarios) {
        tablaUsuarios.destroy();
      }

      // Llenar tabla
      const tbody = document.querySelector("#tablaUsuarios tbody");
      tbody.innerHTML = "";

      result.data.forEach((usuario, index) => {
        const rolBadge = {
          operador: '<span class="badge badge-primary">Operador</span>',
          supervisor: '<span class="badge badge-info">Supervisor</span>',
          admin: '<span class="badge badge-danger">Admin</span>',
        };

        const estadoBadge =
          usuario.estado == 1
            ? '<span class="badge badge-success">Activo</span>'
            : '<span class="badge badge-secondary">Inactivo</span>';

        const firmaBadge =
          usuario.firma == 1
            ? '<button class="btn btn-sm btn-info" onclick="verFirma(' +
              usuario.id +
              ')" title="Ver firma"><i class="fas fa-eye"></i></button>'
            : '<button class="btn btn-sm btn-warning" onclick="verFirma(' +
              usuario.id +
              ')" title="Capturar firma"><i class="fas fa-signature"></i></button>';

        const btnEditar =
          '<button class="btn btn-sm btn-warning" onclick="editarUsuario(' +
          usuario.id +
          ')" title="Editar"><i class="fas fa-edit"></i></button>';

        const btnEstado =
          usuario.estado == 1
            ? '<button class="btn btn-sm btn-danger" onclick="cambiarEstado(' +
              usuario.id +
              ', 0)" title="Desactivar"><i class="fas fa-times-circle"></i></button>'
            : '<button class="btn btn-sm btn-success" onclick="cambiarEstado(' +
              usuario.id +
              ', 1)" title="Activar"><i class="fas fa-check-circle"></i></button>';

        const fila = `
                    <tr>
                        <td>${index + 1}</td>
                        <td>${usuario.nombre_completo}</td>
                        <td>${usuario.dni}</td>
                        <td>${usuario.cargo}</td>
                        <td>${rolBadge[usuario.rol]}</td>
                        <td class="text-center">${firmaBadge}</td>
                        <td class="text-center">${estadoBadge}</td>
                        <td class="text-center">
                            ${btnEditar}
                            ${btnEstado}
                        </td>
                    </tr>
                `;

        tbody.innerHTML += fila;
      });

      // Inicializar DataTable
      tablaUsuarios = $("#tablaUsuarios").DataTable({
        responsive: true,
        language: {
          url: "../../vendor/adminlte/plugins/datatables/es-ES.json",
        },
        order: [[0, "desc"]],
      });
    }
  } catch (error) {
    console.error("Error:", error);
    Swal.fire("Error", "No se pudieron cargar los usuarios", "error");
  }
}

// Crear usuario
document
  .getElementById("formCrearUsuario")
  .addEventListener("submit", async function (e) {
    e.preventDefault();

    const formData = new FormData(this);
    const password = document.getElementById("modalPassword").value;
    const passwordConfirm = document.getElementById(
      "modalPasswordConfirm"
    ).value;
    const rol = document.getElementById("modalRol").value;
    const categoria = document.getElementById("modalCategoria").value;

    // Validar contraseñas
    if (password !== passwordConfirm) {
      Swal.fire("Error", "Las contraseñas no coinciden", "error");
      return;
    }

    // Construir cargo según rol
    let cargo = "";
    if (rol === "operador") {
      if (!categoria) {
        Swal.fire("Error", "Debe seleccionar una categoría de equipo", "error");
        return;
      }
      cargo = "Operador de " + categoria;
    } else if (rol === "supervisor") {
      cargo = "Supervisor";
    } else if (rol === "admin") {
      cargo = "Administrador";
    }

    // Establecer cargo en campo oculto
    document.getElementById("modalCargoHidden").value = cargo;

    formData.set("cargo", cargo);
    formData.append("action", "crear");

    try {
      const response = await fetch("../../api/usuarios.php", {
        method: "POST",
        body: formData,
      });

      const result = await response.json();

      if (result.success) {
        $("#modalCrearUsuario").modal("hide");
        this.reset();

        Swal.fire({
          icon: "success",
          title: "Éxito",
          text: result.message,
          showConfirmButton: false,
          timer: 1500,
        });

        cargarUsuarios();
      } else {
        Swal.fire("Error", result.message, "error");
      }
    } catch (error) {
      Swal.fire("Error", "No se pudo crear el usuario", "error");
    }
  });

// Editar usuario
function editarUsuario(id) {
  // Obtener filtro actual de la URL
  const urlParams = new URLSearchParams(window.location.search);
  const filtroRol = urlParams.get("rol") || "";

  // Construir URL manteniendo el filtro
  let url = "editar.php?id=" + id;
  if (filtroRol) {
    url += "&rol=" + filtroRol;
  }

  window.location.href = url;
}

// Cambiar estado
async function cambiarEstado(id, nuevoEstado) {
  const textoAccion = nuevoEstado == 1 ? "activar" : "desactivar";

  const confirmacion = await Swal.fire({
    title: "¿Estás seguro?",
    text: `¿Deseas ${textoAccion} este usuario?`,
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#3085d6",
    cancelButtonColor: "#d33",
    confirmButtonText: "Sí, " + textoAccion,
    cancelButtonText: "Cancelar",
  });

  if (confirmacion.isConfirmed) {
    const formData = new FormData();
    formData.append("action", "toggle_estado");
    formData.append("id", id);
    formData.append("estado", nuevoEstado);

    try {
      const response = await fetch("../../api/usuarios.php", {
        method: "POST",
        body: formData,
      });

      const result = await response.json();

      if (result.success) {
        Swal.fire({
          icon: "success",
          title: "Éxito",
          text: result.message,
          showConfirmButton: false,
          timer: 1500,
        });

        cargarUsuarios();
      } else {
        Swal.fire("Error", result.message, "error");
      }
    } catch (error) {
      Swal.fire("Error", "No se pudo cambiar el estado", "error");
    }
  }
}

// Ver firma (placeholder - necesita endpoint en API)
// Ver/Editar firma (solo admin)
let userIdFirma = null;

async function verFirma(id) {
    userIdFirma = id;
    
    try {
        // Obtener datos del usuario
        const response = await fetch('../../api/usuarios.php?action=obtener_firma&id=' + id);
        const result = await response.json();
        
        if (result.success && result.data.firma) {
            // Mostrar firma en el modal
            document.getElementById('imagenFirma').src = result.data.firma;
            $('#modalVerFirma').modal('show');
        } else {
            // No tiene firma, ir directo a capturar
            Swal.fire({
                icon: 'info',
                title: 'Sin firma',
                text: 'Este usuario aún no tiene firma registrada. ¿Deseas capturarla ahora?',
                showCancelButton: true,
                confirmButtonText: 'Sí, capturar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'firma.php?user_id=' + id;
                }
            });
        }
    } catch (error) {
        Swal.fire('Error', 'No se pudo obtener la firma', 'error');
    }
}

// Botón editar firma del modal
$(document).on('click', '#btnEditarFirma', function() {
    $('#modalVerFirma').modal('hide');
    window.location.href = 'firma.php?user_id=' + userIdFirma;
});
