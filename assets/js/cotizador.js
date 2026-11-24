const formularioCotizador = document.getElementById('cotizador-form');

if (formularioCotizador) {
    const mensaje = document.getElementById('cotizador-mensaje');
    const distancia = document.getElementById('resultado-distancia');
    const precio = document.getElementById('resultado-precio');
    const comision = document.getElementById('resultado-comision');
    const pago = document.getElementById('resultado-transportista');
    const boton = document.getElementById('cotizar-button');
    const origenSelect = document.getElementById('origen_id');
    const destinoSelect = document.getElementById('destino_id');
    const unidadSelect = document.getElementById('tipo_unidad_id');
    const pesoInput = document.getElementById('peso_ton');
    const regresoInput = formularioCotizador.querySelector('[name="es_regreso"]');
    const moneda = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' });

    function limpiarResultado(texto) {
        mensaje.textContent = texto;
        distancia.textContent = '— km';
        precio.textContent = '—';
        comision.textContent = '—';
        pago.textContent = '—';
    }

    function calcularPrecioLocal() {
        if (!origenSelect.value || !destinoSelect.value || !unidadSelect.value || !pesoInput.value) {
            limpiarResultado('Completa los datos del transporte para calcular el precio.');
            return;
        }

        const origen = origenSelect.selectedOptions[0];
        const destino = destinoSelect.selectedOptions[0];
        const unidad = unidadSelect.selectedOptions[0];
        const peso = Number(pesoInput.value);
        const capacidad = Number(unidad.dataset.capacidad);

        if (origen.value === destino.value) {
            limpiarResultado('El origen y el destino deben ser diferentes.');
            return;
        }
        if (!Number.isFinite(peso) || peso <= 0 || peso > capacidad) {
            limpiarResultado(`El peso debe ser mayor que cero y no superar ${capacidad} toneladas.`);
            return;
        }

        const rad = grados => grados * Math.PI / 180;
        const radioTierra = 6371;
        const latOrigen = rad(Number(origen.dataset.lat));
        const latDestino = rad(Number(destino.dataset.lat));
        const diferenciaLat = latDestino - latOrigen;
        const diferenciaLng = rad(Number(destino.dataset.lng) - Number(origen.dataset.lng));
        const a = Math.sin(diferenciaLat / 2) ** 2
            + Math.cos(latOrigen) * Math.cos(latDestino) * Math.sin(diferenciaLng / 2) ** 2;
        const distanciaLinea = 2 * radioTierra * Math.asin(Math.sqrt(Math.min(1, Math.max(0, a))));
        const distanciaKm = distanciaLinea * 1.25;
        const subtotal = Number(unidad.dataset.base) + distanciaKm * Number(unidad.dataset.km);
        const factorPeso = 1 + 0.20 * (peso / capacidad);
        const factorRegreso = regresoInput && regresoInput.checked ? 0.80 : 1;
        const precioTotal = subtotal * factorPeso * factorRegreso;
        const comisionTotal = precioTotal * 0.10;
        const pagoTransportista = precioTotal - comisionTotal;

        distancia.textContent = `${distanciaKm.toFixed(2)} km`;
        precio.textContent = moneda.format(precioTotal);
        comision.textContent = moneda.format(comisionTotal);
        pago.textContent = moneda.format(pagoTransportista);
        mensaje.textContent = `Estimación calculada en tu navegador para ${unidad.textContent.trim()}. El servidor volverá a calcular el precio al publicar.`;
    }

    formularioCotizador.addEventListener('submit', (evento) => {
        evento.preventDefault();
        if (!formularioCotizador.reportValidity()) return;
        calcularPrecioLocal();
        if (mensaje.textContent.startsWith('El origen') || mensaje.textContent.startsWith('El peso')) return;
        boton.textContent = 'Cotización calculada';
        window.setTimeout(() => { boton.textContent = 'Calcular cotización'; }, 1600);
    });

    formularioCotizador.querySelectorAll('input, select').forEach((campo) => {
        campo.addEventListener('input', calcularPrecioLocal);
        campo.addEventListener('change', calcularPrecioLocal);
    });
}
