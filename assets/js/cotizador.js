const formularioCotizador = document.getElementById('cotizador-form');

if (formularioCotizador) {

    const mensaje = document.getElementById('cotizador-mensaje');

    const distancia = document.getElementById('resultado-distancia');

    const precio = document.getElementById('resultado-precio');

    const comision = document.getElementById('resultado-comision');

    const pagoTransportista = document.getElementById(
        'resultado-transportista'
    );

    const boton = document.getElementById('cotizar-button');

    let temporizador = null;

    let numeroSolicitud = 0;

    const formatoMoneda = new Intl.NumberFormat('es-MX', {
        style: 'currency',
        currency: 'MXN'
    });

    async function calcularCotizacion() {

        if (!formularioCotizador.reportValidity()) {
            return;
        }

        const solicitudActual = ++numeroSolicitud;

        boton.disabled = true;
        boton.textContent = 'Calculando...';

        mensaje.textContent = 'Calculando tu cotización...';

        try {

            const respuesta = await fetch(formularioCotizador.action, {
                method: 'POST',
                body: new FormData(formularioCotizador),
                headers: {
                    'Accept': 'application/json'
                }
            });

            const datos = await respuesta.json();

            if (solicitudActual !== numeroSolicitud) {
                return;
            }

            if (!respuesta.ok || !datos.ok) {

                throw new Error(
                    datos.mensaje || 'No fue posible calcular el precio.'
                );

            }

            distancia.textContent = `${datos.distancia_km} km`;

            precio.textContent = formatoMoneda.format(datos.precio_total);

            comision.textContent = formatoMoneda.format(datos.comision);

            pagoTransportista.textContent = formatoMoneda.format(
                datos.pago_transportista
            );

            mensaje.textContent =
                `Cotización calculada para una unidad ${datos.unidad}.`;

        } catch (error) {

            if (solicitudActual !== numeroSolicitud) {
                return;
            }

            mensaje.textContent = error.message;

            distancia.textContent = '-- km';
            precio.textContent = '--';
            comision.textContent = '--';
            pagoTransportista.textContent = '--';

        } finally {

            if (solicitudActual === numeroSolicitud) {

                boton.disabled = false;
                boton.textContent = 'Calcular cotización';

            }

        }

    }

    formularioCotizador.addEventListener('submit', event => {

        event.preventDefault();

        clearTimeout(temporizador);

        calcularCotizacion();

    });

    formularioCotizador.querySelectorAll(
        'input, select'
    ).forEach(campo => {

        campo.addEventListener('change', () => {

            clearTimeout(temporizador);

            const origen = document.getElementById('origen_id').value;

            const destino = document.getElementById('destino_id').value;

            const unidad = document.getElementById('tipo_unidad_id').value;

            const peso = document.getElementById('peso_ton').value;

            if (!origen || !destino || !unidad || !peso) {
                return;
            }

            temporizador = setTimeout(() => {
                calcularCotizacion();
            }, 500);

        });

    });

}
