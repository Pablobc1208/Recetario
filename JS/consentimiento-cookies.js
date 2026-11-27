// Gestor de consentimiento de cookies (CMP).
// El RGPD obliga a pedir permiso antes de guardar cookies que no sean
// estrictamente necesarias, así que la de la vista del listado espera al "sí".

/**
 * Configuración del banner y del panel de preferencias.
 * - necesarias: la sesión de PHP y la propia cookie del gestor. No se pueden desactivar.
 * - preferencias: la vista del listado (cuadrícula o lista) de JS/cookies.js.
 */
const configuracionCookies = {
    // Si el usuario rechaza o quita el permiso, la librería borra estas cookies
    categories: {
        necesarias: {
            enabled: true,
            readOnly: true
        },
        preferencias: {
            autoClear: {
                cookies: [
                    { name: 'vista_listado' }
                ]
            }
        }
    },

    guiOptions: {
        consentModal: {
            layout: 'box',
            position: 'bottom left'
        },
        preferencesModal: {
            layout: 'box'
        }
    },

    language: {
        default: 'es',
        translations: {
            es: {
                consentModal: {
                    title: 'Usamos cookies',
                    description: 'El Recetario usa una cookie necesaria para mantener tu sesión y, si nos das permiso, otra para recordar cómo prefieres ver el listado de recetas.',
                    acceptAllBtn: 'Aceptar todas',
                    acceptNecessaryBtn: 'Solo las necesarias',
                    showPreferencesBtn: 'Configurar'
                },
                preferencesModal: {
                    title: 'Preferencias de cookies',
                    acceptAllBtn: 'Aceptar todas',
                    acceptNecessaryBtn: 'Solo las necesarias',
                    savePreferencesBtn: 'Guardar mi elección',
                    closeIconLabel: 'Cerrar',
                    sections: [
                        {
                            title: 'Cookies necesarias',
                            description: 'Hacen que la web funcione (iniciar sesión y recordar tu elección sobre las cookies). No se pueden desactivar.',
                            linkedCategory: 'necesarias',
                            cookieTable: {
                                headers: {
                                    name: 'Cookie',
                                    description: 'Para qué sirve',
                                    duration: 'Duración'
                                },
                                body: [
                                    {
                                        name: 'PHPSESSID',
                                        description: 'Sesión de PHP: mantiene al usuario identificado.',
                                        duration: 'Hasta cerrar el navegador'
                                    },
                                    {
                                        name: 'cc_cookie',
                                        description: 'Guarda qué cookies has aceptado.',
                                        duration: '6 meses'
                                    }
                                ]
                            }
                        },
                        {
                            title: 'Cookies de preferencias',
                            description: 'Recuerdan cómo quieres ver el listado de recetas (cuadrícula o lista).',
                            linkedCategory: 'preferencias',
                            cookieTable: {
                                headers: {
                                    name: 'Cookie',
                                    description: 'Para qué sirve',
                                    duration: 'Duración'
                                },
                                body: [
                                    {
                                        name: 'vista_listado',
                                        description: 'Vista elegida en el listado de recetas.',
                                        duration: 'Hasta el 31 de diciembre del año que viene'
                                    }
                                ]
                            }
                        }
                    ]
                }
            }
        }
    }
};

// Arranca el gestor: la primera vez muestra el banner y luego recuerda la elección.
window.CookieConsent.run(configuracionCookies);
