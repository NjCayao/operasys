/**
 * OperaSys - JavaScript de Firma Digital
 * Archivo: assets/js/firma.js
 * Descripción: Captura de firma mediante canvas HTML5
 */

document.addEventListener("DOMContentLoaded", function () {
  const canvas = document.getElementById("canvasFirma");

  if (!canvas) return;

  const ctx = canvas.getContext("2d");
  const btnLimpiar = document.getElementById("btnLimpiar");
  const btnGuardar = document.getElementById("btnGuardar");
  const alertMessage = document.getElementById("alertMessage");
  const alertText = document.getElementById("alertText");
  const tieneFirma = document.getElementById("tieneFirma").value === "1";
  const userIdEditar = document.getElementById("userIdEditar").value;
  const esAdmin = document.getElementById("esAdmin").value === "1";
  const userIdSesion = document.getElementById("userIdSesion").value;

  let dibujando = false;
  let firmaCaptured = false;

  // Configurar estilo del trazo
  ctx.strokeStyle = "#000000";
  ctx.lineWidth = 2;
  ctx.lineCap = "round";
  ctx.lineJoin = "round";

  // ============================================
  // EVENTOS DEL MOUSE (Desktop)
  // ============================================

  canvas.addEventListener("mousedown", function (e) {
    dibujando = true;
    firmaCaptured = true;
    const rect = canvas.getBoundingClientRect();
    ctx.beginPath();
    ctx.moveTo(e.clientX - rect.left, e.clientY - rect.top);
  });

  canvas.addEventListener("mousemove", function (e) {
    if (!dibujando) return;

    const rect = canvas.getBoundingClientRect();
    ctx.lineTo(e.clientX - rect.left, e.clientY - rect.top);
    ctx.stroke();
  });

  canvas.addEventListener("mouseup", function () {
    dibujando = false;
  });

  canvas.addEventListener("mouseleave", function () {
    dibujando = false;
  });

  // ============================================
  // EVENTOS TÁCTILES (Mobile)
  // ============================================

  canvas.addEventListener("touchstart", function (e) {
    e.preventDefault();
    dibujando = true;
    firmaCaptured = true;
    const rect = canvas.getBoundingClientRect();
    const touch = e.touches[0];
    ctx.beginPath();
    ctx.moveTo(touch.clientX - rect.left, touch.clientY - rect.top);
  });

  canvas.addEventListener("touchmove", function (e) {
    e.preventDefault();
    if (!dibujando) return;

    const rect = canvas.getBoundingClientRect();
    const touch = e.touches[0];
    ctx.lineTo(touch.clientX - rect.left, touch.clientY - rect.top);
    ctx.stroke();
  });

  canvas.addEventListener("touchend", function (e) {
    e.preventDefault();
    dibujando = false;
  });

  // ============================================
  // BOTÓN LIMPIAR
  // ============================================

  btnLimpiar.addEventListener("click", function () {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    firmaCaptured = false;
    alertMessage.style.display = "none";
  });

  // ============================================
  // BOTÓN GUARDAR
  // ============================================

  btnGuardar.addEventListener("click", async function () {
    // Validar que se haya dibujado algo
    if (!firmaCaptured) {
      mostrarAlerta("Por favor dibuja tu firma antes de guardar", "danger");
      return;
    }

    // Validar que el canvas no esté vacío
    const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
    const pixelData = imageData.data;
    let isEmpty = true;

    for (let i = 0; i < pixelData.length; i += 4) {
      if (pixelData[i + 3] > 0) {
        // Canal alpha
        isEmpty = false;
        break;
      }
    }

    if (isEmpty) {
      mostrarAlerta("Por favor dibuja tu firma antes de guardar", "danger");
      return;
    }

    // Deshabilitar botón
    btnGuardar.disabled = true;
    btnGuardar.innerHTML =
      '<i class="fas fa-spinner fa-spin"></i> Guardando...';

    try {
      // Convertir canvas a base64
      const firmaBase64 = canvas.toDataURL("image/png");

      // Crear FormData
      const formData = new FormData();
      formData.append("action", "guardar_firma");
      formData.append("firma", firmaBase64);
      formData.append("user_id", userIdEditar);

      // Enviar al servidor
      const response = await fetch("../../api/usuarios.php", {
        method: "POST",
        body: formData,
      });

      const data = await response.json();

      if (data.success) {
        // Firma guardada exitosamente
        btnGuardar.innerHTML = '<i class="fas fa-check"></i> ¡Guardado!';
        btnGuardar.classList.remove("btn-success");
        btnGuardar.classList.add("btn-primary");

        mostrarAlerta(data.message, "success");

        // Redirigir después de 2 segundos
        setTimeout(() => {
          if (esAdmin && userIdEditar != userIdSesion) {
            // Admin editando a otro: regresar a lista
            window.location.href = 'listar.php';
          } else if (!tieneFirma) {
            // Primera vez: ir al dashboard
            window.location.href = '../admin/dashboard.php';
          } else {
            // Actualización propia: ir al perfil
            window.location.href = 'perfil.php';
          }
        }, 2000);
      } else {
        throw new Error(data.message || "Error al guardar firma");
      }
    } catch (error) {
      mostrarAlerta(error.message, "danger");

      // Restaurar botón
      btnGuardar.disabled = false;
      btnGuardar.innerHTML = '<i class="fas fa-save"></i> Guardar Firma';
    }
  });

  // ============================================
  // FUNCIÓN AUXILIAR: MOSTRAR ALERTAS
  // ============================================

  function mostrarAlerta(mensaje, tipo) {
    alertMessage.className = `alert alert-${tipo}`;
    alertText.textContent = mensaje;
    alertMessage.style.display = "block";

    // Auto-ocultar después de 5 segundos
    setTimeout(() => {
      alertMessage.style.display = "none";
    }, 5000);
  }
});