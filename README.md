# 🎸 MusiConecta

Plataforma web para contratar músicos solistas y bandas, con un **panel de análisis comparativo** que le muestra a cada músico cuánto gana, con qué servicios, en qué forma de trabajo (solista o banda), con qué repertorio, en qué tipo de evento y cómo se ubica frente a los músicos de su género.

> Proyecto de **ISWZ3101 – Ingeniería Web (UDLA)** · Sthefano Zambrano
> Laravel 13 (MVC) · PHP 8.3 · SQLite · Blade · Tailwind CSS 4 · Chart.js

**Demo en línea:** https://musiconecta.onrender.com
_(plan gratuito de Render: si el servicio estaba dormido, la primera carga tarda ~50 s)_

---

## Contenido

1. [Cuentas de prueba](#cuentas-de-prueba)
2. [Instalación local](#instalación-local)
3. [La administración que alimenta el Core](#la-administración-que-alimenta-el-core)
4. [Validación en back-end de un dato sensible: la cédula](#validación-en-back-end-de-un-dato-sensible-la-cédula)
5. [Dropdown dependiente Provincia → Ciudad](#dropdown-dependiente-provincia--ciudad)
6. [El Core: comparativas](#el-core-comparativas)
7. [Arquitectura MVC](#arquitectura-mvc)
8. [Modelo de datos](#modelo-de-datos)
9. [Pruebas](#pruebas)
10. [Despliegue](#despliegue)

---

## Cuentas de prueba

Contraseña de todas: `password`

| Rol | Correo | Qué ver |
|---|---|---|
| Administrador | `admin@musiconecta.test` | Músicos, canciones, ciudades, catálogos, comisión por mes |
| Músico | `andres@musiconecta.test` | **Mi panel** (Core), solicitudes, agenda, bandas, repertorio |
| Cliente | `maria@musiconecta.test` | Buscar músicos, solicitar, pagar anticipo/saldo, reseñar |

El seeder genera 14 meses de historial (≈2.500 contrataciones, 32 músicos, 2 bandas) con fechas relativas al día en que se ejecuta.

## Instalación local

Requisitos: PHP 8.3 (extensiones `pdo_sqlite`, `mbstring`, `intl`), Composer 2, Node 20+.

```bash
git clone https://github.com/Sthefanoz/musiconecta.git
cd musiconecta
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed   # crea database/database.sqlite con datos de demostración
npm run build
php artisan serve                  # http://127.0.0.1:8000
```

## La administración que alimenta el Core

El Core compara a cada músico con **los músicos de su mismo género principal y ciudad**, y reparte el ingreso de cada contratación **entre los estilos de las canciones pedidas**. Por eso el administrador gestiona exactamente esos datos:

| Módulo (rol administrador) | Ruta | Cómo alimenta al Core |
|---|---|---|
| **Músicos** (CRUD + verificación) | `/admin/musicos` | Género principal + ciudad definen el grupo de comparación (comparativa *g*). La cédula identifica a quién se le paga cada reparto. |
| **Canciones** (CRUD) | `/admin/canciones` | El estilo de cada canción reparte el ingreso en la comparativa *Repertorio solicitado* (*e*). Muestra cuántas veces se pidió cada una. |
| **Ciudades** (CRUD, ligadas a provincia) | `/admin/ciudades` | Segmentan la comparación con el género. |
| **Catálogos**: géneros, provincias, tipos de servicio, tipos de evento | `/admin/catalogos/{catálogo}` | Agrupan las comparativas *c* (servicio) y *f* (tipo de evento). |
| **Resumen** | `/admin` | Comisión ganada por la plataforma por mes y músicos con más contrataciones. |

Todas las rutas están protegidas por el middleware `rol:admin` (`app/Http/Middleware/VerificarRol.php`).

## Validación en back-end de un dato sensible: la cédula

La **cédula** del músico es el dato sensible del Core: identifica a la persona que recibe su parte del pago en cada contratación (`repartos`). Se valida **en el servidor, antes de grabar**, aunque el navegador no ejecute JavaScript (el formulario usa `novalidate`):

- Regla propia `app/Rules/CedulaEcuatoriana.php`:
  - exactamente 10 dígitos;
  - código de provincia 01–24 (o 30);
  - tercer dígito menor que 6 (persona natural);
  - **dígito verificador** con el algoritmo **módulo 10** (coeficientes 2,1,2,1,…; si el producto es > 9 se resta 9).
- Debe ser **única** (`Rule::unique('musicos','cedula')`) y la columna tiene índice `UNIQUE` en la base.
- Se normaliza (sin espacios ni guiones) antes de validar.
- Se aplica en `Admin\MusicoController` y en el perfil del propio músico (`Musico\PerfilController`).

Pruebas: `tests/Feature/AdminTest.php` envía cédulas inválidas directamente al servidor y comprueba que **no se graba nada**.

## Dropdown dependiente Provincia → Ciudad

Ninguna clave foránea se escribe a mano. Para la ciudad (que depende de la provincia):

1. El formulario muestra un `<select>` de **provincias**.
2. Al elegir una, `resources/js/app.js` (`iniciarUbicacion`) llama a
   `GET /ubicacion/provincias/{provincia}/ciudades` (`UbicacionController`) y recarga el `<select>` de **ciudades** solo con las de esa provincia.
3. En el servidor, `app/Support/Ubicacion.php` vuelve a comprobar que la ciudad **pertenezca** a la provincia (`Rule::exists('ciudades')->where('provincia_id', …)`), por si alguien manipula el formulario.

El componente (`resources/views/partials/ubicacion.blade.php`) se reutiliza en: alta/edición de músicos (admin), perfil del músico, bandas y solicitud de contratación. El resto de relaciones (género, estilo, tipo de servicio, tipo de evento, provincia de una ciudad) también se eligen con dropdowns.

## El Core: comparativas

`app/Services/ReporteMusicoService.php` — sobre las contrataciones **realizadas** dentro del rango de fechas:

| | Comparativa | Cálculo |
|---|---|---|
| a | Filtrado | Solo estado `realizada` en el rango |
| b | Ingreso neto | IN = M − c·M (c = 5 %); solista 100 %, banda IN·qᵢ/100 (Σqᵢ = 100) |
| c | Ingresos por mes y servicio | Suma por mes/servicio y variación (Iₘ − Iₘ₋₁)/Iₘ₋₁·100 |
| d | Solista vs. banda | Total, n_f, P_f = total/n_f, H_f = total/Σhoras |
| e | Repertorio solicitado | Ingreso del estilo = IN·s_{k,e}/s_k; ranking y canciones que faltan en el repertorio |
| f | Tipo de evento | Cantidad, ingreso, promedio y participación |
| g | Comparación con su género | Promedio del grupo, diferencia %, posición (% que ganó menos) y percentil 75 |

Cada comparativa termina con una **conclusión en lenguaje natural**. El flujo de contratación (`ContratacionService`) y la disponibilidad (`AgendaService`) generan los datos que el Core analiza.

## Arquitectura MVC

```
app/
├── Models/            Modelo: Musico, Banda, Servicio, Contratacion, Reparto, Pago, Resena,
│                      BloqueoAgenda, Cancion, Genero, Provincia, Ciudad, TipoServicio, TipoEvento
├── Enums/             Rol, EstadoContratacion (transiciones permitidas)
├── Services/          Lógica del Core: ContratacionService, AgendaService, ReporteMusicoService
├── Rules/             CedulaEcuatoriana
├── Support/           Ubicacion (reglas Provincia → Ciudad)
├── Http/Controllers/  Controlador: público, Musico/*, Admin/*
├── Http/Requests/     SolicitarContratacionRequest
└── Http/Middleware/   VerificarRol, PerfilMusicoCompleto
resources/views/       Vista: Blade + Tailwind
database/              Migraciones y seeders
```

## Modelo de datos

`users` · `musicos` (con `cedula` única) · `musico_genero` · `bandas` · `banda_musico` (instrumento, porcentaje) · `canciones` · `repertorio` · `servicios` · `contrataciones` · `contratacion_cancion` · `repartos` · `pagos` · `resenas` · `bloqueos_agenda` · catálogos `generos`, `provincias`, `ciudades` (→ provincia), `tipos_servicio`, `tipos_evento`.

Reglas de negocio: comisión 5 %, anticipo 30 % (`config/musiconecta.php`); flujo solicitada → aceptada → confirmada → realizada; la solicitud solo se registra si todos los integrantes están disponibles; los porcentajes de una banda deben sumar 100 %; pagos simulados.

## Pruebas

```bash
php artisan test
```

17 pruebas / 112 aserciones: reparto de banda, disponibilidad, transiciones, fórmulas del Core, percentil, cédula (módulo 10, duplicada, sin JS), Provincia → Ciudad, CRUD del admin y recorrido de todas las pantallas por rol.

## Despliegue

[![Deploy to Render](https://render.com/images/deploy-to-render-button.svg)](https://render.com/deploy?repo=https://github.com/Sthefanoz/musiconecta)

El repositorio incluye `Dockerfile`, `docker/start.sh` y `render.yaml`:

1. En [Render](https://render.com): botón de arriba, o **New → Blueprint** y elegir este repositorio.
2. Render construye la imagen (Vite + Composer + PHP 8.3/Apache), genera `APP_KEY`, crea la base SQLite, ejecuta migraciones y siembra los datos de demostración.

> En el plan gratuito el disco es efímero: al reiniciarse el servicio la base se vuelve a sembrar, lo cual es adecuado para una demo.
