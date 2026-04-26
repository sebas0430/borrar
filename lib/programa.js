/**
 * @brief Función asíncrona genérica para realizar peticiones planas (AJAX) al backend.
 * @param {string} opcion El nombre de la función / acción a ejecutar (ej. 'desplegar_menu').
 * @param {object} parametros Objeto con parámetros adicionales para enviar al servidor.
 * @return {Promise} Promesa que se resuelve tras actualizar la UI.
 * @pre Debe existir la constante global APP_TOKEN.
 * @post Actualiza el DOM (etiqueta <article>) con la respuesta JSON del servidor.
*/
async function fn_peticion_plana(opcion, parametros = {}) {
    let url = new URL(window.location.href);
    url.searchParams.set('opcion', opcion);
    url.searchParams.set('formato', 'json');
    
    // Agregamos el token de seguridad a todos los payloads para validación CSRF
    let bodyData = new FormData();
    if (typeof APP_TOKEN !== 'undefined') {
        bodyData.append('token', APP_TOKEN);
    }
    
    for (let key in parametros) {
        bodyData.append(key, parametros[key]);
    }

    try {
        const respuesta = await fetch(url, {
            method: 'POST',
            body: bodyData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        if (!respuesta.ok) {
            const errorJson = await respuesta.json();
            alert("Error de Seguridad/Servidor: " + (errorJson.error || respuesta.statusText));
            return false;
        }

        const datos = await respuesta.json();
        
        // Arquitectura plana: solo reemplazamos el contenido visual sin recargar
        if (datos.html !== undefined) {
            const cajaContenido = document.querySelector('article');
            if (cajaContenido) {
                cajaContenido.innerHTML = datos.html;
            }
        }
        return datos;
    } catch (e) {
        console.error("Error en petición plana:", e);
        alert("Hubo un error al comunicar con el servidor.");
        return false;
    }
}

/**
 * @brief Intercepta los clicks de navegación para usar peticiones planas.
 * @param {string} opcion Acción solicitada.
 * @param {object} params Parámetros extra.
 */
function navegarA(opcion, params = {}) {
    // Detenemos los polling de fondo si navegamos a otra pantalla
    if (window.intervaloRefresco) {
        clearInterval(window.intervaloRefresco);
    }
    fn_peticion_plana(opcion, params);
}

/**
 * @brief Refresca automáticamente una sección para mantenerla en vivo (Polling AJAX).
 * @param {string} opcion Vista a mantener viva.
 * @param {number} ms Milisegundos entre cada ping.
 * @pre Debe estar detenible el interval previo.
 * @post Dispara peticiones planas recurrentemente.
 */
function fn_refrescar_automatico(opcion, ms = 3000) {
    if (window.intervaloRefresco) {
        clearInterval(window.intervaloRefresco);
    }
    window.intervaloRefresco = setInterval(() => {
        fn_peticion_plana(opcion);
    }, ms);
}
