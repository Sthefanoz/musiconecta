import Chart from 'chart.js/auto';

const PALETA = ['#5b4fd8', '#0ea5e9', '#16a34a', '#f59e0b', '#db2777', '#64748b', '#14b8a6', '#a855f7'];
const dinero = (v, d = 0) => '$' + Number(v).toLocaleString('es-EC', { minimumFractionDigits: d, maximumFractionDigits: d });

Chart.defaults.font.family = 'Inter, ui-sans-serif, system-ui, sans-serif';
Chart.defaults.color = '#64748b';
Chart.defaults.plugins.legend.labels.boxWidth = 14;

/**
 * Gráficos declarativos: <canvas data-chart='{"type":"bar", ...}' data-money="y">
 * data-money indica el eje en dinero (x | y | value para dona).
 */
function iniciarGraficos() {
    document.querySelectorAll('canvas[data-chart]').forEach((el) => {
        const cfg = JSON.parse(el.dataset.chart);
        cfg.options ??= {};
        cfg.options.maintainAspectRatio ??= false;

        (cfg.data.datasets || []).forEach((ds, i) => {
            if (cfg.type === 'doughnut') {
                ds.backgroundColor ??= PALETA;
            } else {
                ds.backgroundColor ??= PALETA[i % PALETA.length];
                ds.borderColor ??= PALETA[i % PALETA.length];
            }
        });

        const eje = el.dataset.money;
        if (eje) {
            if (eje !== 'value') {
                cfg.options.scales ??= {};
                cfg.options.scales[eje] ??= {};
                cfg.options.scales[eje].ticks = { callback: (v) => dinero(v) };
            }
            cfg.options.plugins ??= {};
            cfg.options.plugins.tooltip = {
                callbacks: {
                    label: (ctx) => {
                        const valor = eje === 'value' ? ctx.parsed : ctx.parsed[eje];
                        return `${ctx.dataset.label ?? ctx.label}: ${dinero(valor, 2)}`;
                    },
                },
            };
        }

        new Chart(el, cfg);
    });
}

/** Verificación de disponibilidad en vivo en el formulario de solicitud. */
function iniciarDisponibilidad() {
    const form = document.querySelector('form[data-disponibilidad]');
    if (!form) return;

    const caja = document.getElementById('disponibilidad');
    const monto = document.getElementById('resumen-monto');
    const total = document.getElementById('resumen-total');
    const anticipo = document.getElementById('resumen-anticipo');
    const horasTxt = document.getElementById('resumen-horas');
    const nCanciones = document.getElementById('resumen-canciones');
    const boton = form.querySelector('button[type=submit]');
    let temporizador;

    const consultar = async () => {
        const fecha = form.fecha_evento.value;
        const hora = form.hora_inicio.value;
        const horas = form.horas.value;
        if (!fecha || !hora || !horas) return;

        const url = new URL(form.dataset.disponibilidad, window.location.origin);
        url.search = new URLSearchParams({ fecha, hora, horas }).toString();
        try {
            const r = await fetch(url, { headers: { Accept: 'application/json' } });
            const d = await r.json();
            if (!r.ok) throw new Error(d.message || 'Revisa los datos.');
            caja.className = 'rounded-xl p-4 text-sm ' + (d.disponible ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800');
            caja.textContent = (d.disponible ? '✓ ' : '✗ ') + d.mensaje;
            monto.textContent = dinero(d.monto, 2);
            total.textContent = dinero(d.monto, 2);
            anticipo.textContent = dinero(d.anticipo, 2);
            horasTxt.textContent = horas;
            boton.disabled = !d.disponible;
        } catch (e) {
            caja.className = 'rounded-xl bg-amber-100 p-4 text-sm text-amber-800';
            caja.textContent = e.message;
        }
    };

    form.addEventListener('input', (e) => {
        if (e.target.name === 'canciones[]') {
            nCanciones.textContent = form.querySelectorAll('input[name="canciones[]"]:checked').length;
            return;
        }
        if (['fecha_evento', 'hora_inicio', 'horas'].includes(e.target.name)) {
            clearTimeout(temporizador);
            temporizador = setTimeout(consultar, 300);
        }
    });
    consultar();
}

/**
 * Dropdown dependiente Provincia → Ciudad: al elegir la provincia se piden sus ciudades
 * al servidor (GET /ubicacion/provincias/{id}/ciudades) y se recarga el select de ciudades.
 */
function iniciarUbicacion() {
    document.querySelectorAll('[data-ubicacion]').forEach((bloque) => {
        const provincia = bloque.querySelector('[data-provincia]');
        const ciudad = bloque.querySelector('[data-ciudad]');

        provincia.addEventListener('change', async () => {
            ciudad.innerHTML = '';
            ciudad.disabled = true;
            if (!provincia.value) {
                ciudad.add(new Option('Primero elige la provincia', ''));
                return;
            }
            ciudad.add(new Option('Cargando…', ''));
            try {
                const r = await fetch(bloque.dataset.url.replace('__ID__', provincia.value), { headers: { Accept: 'application/json' } });
                const ciudades = await r.json();
                ciudad.innerHTML = '';
                ciudad.add(new Option('Selecciona una ciudad…', ''));
                ciudades.forEach((c) => ciudad.add(new Option(c.nombre, c.id)));
                ciudad.disabled = false;
                ciudad.dispatchEvent(new Event('change', { bubbles: true }));
            } catch {
                ciudad.innerHTML = '';
                ciudad.add(new Option('No se pudieron cargar las ciudades', ''));
            }
        });
    });
}

document.addEventListener('DOMContentLoaded', () => {
    iniciarGraficos();
    iniciarDisponibilidad();
    iniciarUbicacion();
});
