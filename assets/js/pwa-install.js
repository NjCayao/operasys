/**
 * OperaSys - Instalación PWA
 * Muestra prompt de instalación
 */

let deferredPrompt;
const btnInstall = document.getElementById('btnInstallPWA');
const sidebarInstall = document.getElementById('sidebarInstallPWA');

window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    deferredPrompt = e;
    
    // Mostrar botón/contenedor de instalación
    if (sidebarInstall) {
        sidebarInstall.style.display = 'block';
    }
});

if (btnInstall) {
    btnInstall.addEventListener('click', async () => {
        if (!deferredPrompt) return;
        
        deferredPrompt.prompt();
        const { outcome } = await deferredPrompt.userChoice;
        
        console.log(`Usuario ${outcome === 'accepted' ? 'aceptó' : 'rechazó'} instalar PWA`);
        
        if (outcome === 'accepted') {
            // Ocultar botón después de instalar
            if (sidebarInstall) sidebarInstall.style.display = 'none';
        }
        
        deferredPrompt = null;
    });
}

// Detectar si ya está instalada
window.addEventListener('appinstalled', () => {
    console.log('✓ OperaSys instalada correctamente');
    deferredPrompt = null;
    
    // Ocultar botón después de instalar
    if (sidebarInstall) {
        sidebarInstall.style.display = 'none';
    }
});

// Detectar si ya está corriendo como PWA instalada
if (window.matchMedia('(display-mode: standalone)').matches) {
    console.log('✓ Corriendo como PWA instalada');
    if (sidebarInstall) {
        sidebarInstall.style.display = 'none';
    }
}