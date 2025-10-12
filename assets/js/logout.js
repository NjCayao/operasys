/**
 * OperaSys - Logout con AJAX
 * Archivo: assets/js/logout.js
 */

function cerrarSesion() {
    Swal.fire({
        title: '¿Cerrar sesión?',
        text: "Se cerrará tu sesión actual",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sí, salir',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            // Mostrar loading
            Swal.fire({
                title: 'Cerrando sesión...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Hacer logout
            fetch('../../api/auth.php?action=logout', {
                method: 'POST'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Sesión cerrada',
                        text: 'Hasta pronto!',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.href = '../auth/login.php';
                    });
                } else {
                    // Forzar redirección aunque falle
                    window.location.href = '../auth/logout.php';
                }
            })
            .catch(error => {
                // Si hay error, usar método tradicional
                window.location.href = '../auth/logout.php';
            });
        }
    });
}
