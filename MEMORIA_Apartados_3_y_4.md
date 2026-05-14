# Memoria del proyecto — Apartados 3 y 4 (borrador)

*Documento auxiliar redactado como ejemplo para incorporar al cuerpo de la memoria. Ajustar cifras, fechas y nombres propios a la versión final del trabajo.*

---

## 3. OBJETIVOS

En la introducción se plantearon los objetivos del proyecto **Mundial Store**: ofrecer una tienda web de camisetas de fútbol usable sin obligar al registro hasta el momento del pago, alinear el flujo con un diagrama de negocio acordado y persistir pedidos y datos de usuario de forma fiable.

A continuación se relaciona cada objetivo con el grado de consecución y evidencias en el producto entregado.

### Objetivos generales

| Objetivo | Estado | Indicadores de consecución |
|----------|--------|----------------------------|
| **OG1.** Publicar un catálogo navegable (inicio con destacados, búsqueda y listado con filtros) | **Alcanzado** | Página de inicio con productos destacados; página de catálogo con búsqueda por texto y filtros por marca, modelo y talla. |
| **OG2.** Permitir carrito de compra para usuario invitado y exigir autenticación solo al tramitar la compra | **Alcanzado** | Añadir al carrito y ver carrito sin sesión; redirección a login/registro en el checkout con parámetro `next` hacia el checkout. |
| **OG3.** Implementar un checkout por pasos (envío, método de pago, confirmación simulada) y guardar el pedido en base de datos | **Alcanzado** | Flujo en sesión con pasos secuenciales; tabla de pedidos ampliada con datos de envío y método de pago; pantalla de confirmación posterior al commit. |
| **OG4.** Notificar al usuario por correo electrónico el resumen del pedido sin bloquear la compra si el envío falla | **Alcanzado** | Uso de `mail()` tras registrar el pedido; mensaje diferenciado en confirmación si el correo no pudo enviarse (p. ej. entorno sin SMTP). |
| **OG5.** Ofrecer recuperación de contraseña con token de un solo uso y caducidad controlada | **Alcanzado** | Flujo «olvidé contraseña» → correo con enlace → formulario de nueva clave; tabla `password_resets` con hash del token y expiración. |

### Objetivos específicos (técnicos)

| Objetivo | Estado | Notas |
|----------|--------|--------|
| **OE1.** Separar presentación, lógica compartida y acceso a datos | **Alcanzado** | Scripts en `public/`, includes comunes (`bootstrap`, `header`, `footer`, `functions`), SQL centralizado y scripts de instalación/migración. |
| **OE2.** Mitigar riesgos básicos de seguridad (sesiones, CSRF en formularios, contraseñas con `password_hash`) | **Alcanzado** | Tokens CSRF en formularios sensibles; validación de URL `next` interna; tokens de reset almacenados como hash. |
| **OE3.** Facilitar despliegue y evolución del esquema de datos | **Alcanzado** | `schema.sql` para instalación limpia; script de migración incremental para bases ya existentes. |

### Objetivos no alcanzados o parcialmente alcanzados (transparencia)

- **Integración con pasarela de pago real:** no incluida en el alcance; el pago se simula a efectos académicos y de prueba de flujo.
- **Entrega de correo en todos los entornos:** depende de la configuración del servidor (p. ej. Windows sin SMTP); el sistema contempla el fallo sin invalidar el pedido.

En conjunto, los objetivos funcionales y de arquitectura previstos para la versión entregada se consideran **cumplidos**, con las salvedades explícitas anteriores.

---

## 4. DESARROLLO DE LA APLICACIÓN

### 4.1. Análisis — Requisitos

#### Requisitos funcionales (resumen)

1. **RF01 — Catálogo:** Visualizar productos activos, ficha por identificador, stock por talla.
2. **RF02 — Búsqueda y filtrado:** Texto libre y filtros por marca, modelo y talla con existencias.
3. **RF03 — Carrito:** Altas, modificaciones de cantidad y eliminación de líneas respetando stock disponible.
4. **RF04 — Checkout:** Recogida de datos de envío, selección de método de pago, confirmación y registro del pedido con líneas.
5. **RF05 — Cuenta de usuario:** Registro, inicio y cierre de sesión; recuperación de contraseña por email con token temporal.
6. **RF06 — Pedidos:** Consulta de confirmación tras la compra; referencia al identificador de pedido.

#### Requisitos no funcionales (resumen)

- **RNF01 — Usabilidad:** Navegación clara entre inicio, catálogo, producto, carrito y checkout.
- **RNF02 — Mantenibilidad:** Código PHP legible, separación por carpetas (`public`, `includes`, `sql`, `tools`).
- **RNF03 — Integridad de datos:** Uso de transacciones en la creación del pedido cuando afecta a varias tablas.
- **RNF04 — Seguridad básica:** Protección frente a CSRF en operaciones mutables; no almacenar contraseñas en claro.

#### Actores

- **Visitante / cliente anónimo:** navega, busca, añade al carrito.
- **Cliente registrado:** mismo flujo más tramitación de compra y recuperación de contraseña.
- **Sistema:** envío de correos, validación de stock, persistencia.

---

### 4.2. Diseño

#### 4.2.1. Arquitectura de la aplicación

Se ha seguido una **arquitectura en capas simplificada**, típica de aplicaciones PHP monolíticas sin framework MVC explícito:

```text
┌─────────────────────────────────────────────────────────┐
│  Capa de presentación (vistas + controladores ligeros)   │
│  Ficheros en /public (*.php que generan HTML)           │
└──────────────────────────┬──────────────────────────────┘
                           │
┌──────────────────────────▼──────────────────────────────┐
│  Capa de aplicación / utilidades                          │
│  includes/bootstrap.php, includes/functions.php           │
│  (sesión, carrito, CSRF, emails, redirecciones seguras)   │
└──────────────────────────┬──────────────────────────────┘
                           │
┌──────────────────────────▼──────────────────────────────┐
│  Capa de acceso a datos                                   │
│  PDO (MySQL/MariaDB), consultas preparadas               │
└──────────────────────────────────────────────────────────┘
```

- **Presentación:** cada script bajo `public/` actúa como punto de entrada HTTP; incluye cabecera y pie comunes para homogeneizar la interfaz.
- **Lógica compartida:** funciones de apoyo (usuario actual, carrito en sesión, construcción de URLs seguras para `next`, envío de correos) centralizadas para evitar duplicación.
- **Datos:** esquema relacional; conexión única vía función `get_pdo()` tras la carga de configuración.

Esta organización prioriza la **claridad para un proyecto acotado** frente a la sobrecarga de un framework completo, manteniendo posibilidad de migrar a un MVC en un trabajo futuro.

#### 4.2.2. Diseño de la base de datos

El modelo de datos principal incluye (entre otras):

- **users:** identidad del cliente (email único, hash de contraseña).
- **products** y **product_stock:** catálogo y unidades disponibles por talla.
- **orders** y **order_items:** cabecera y líneas del pedido; la cabecera incorpora campos de **envío** y **método de pago** acordes con el flujo de checkout.
- **password_resets:** gestión de tokens de recuperación (hash, caducidad, uso) sin exponer el token en la base de datos.

Las claves foráneas y los tipos de columna se definieron para coherencia con el motor elegido (p. ej. InnoDB). Las migraciones permiten alinear instalaciones antiguas con el esquema actual sin recrear la base desde cero.

*(Incluir en la memoria definitiva el diagrama entidad-relación o esquema exportado desde la herramienta CASE utilizada.)*

---

### 4.3. Implementación

#### Herramientas y tecnologías

| Categoría | Tecnología / herramienta | Uso |
|-----------|--------------------------|-----|
| Lenguaje | PHP 8.x | Lógica servidor, plantillas embebidas |
| Base de datos | MySQL / MariaDB | Persistencia |
| Servidor de desarrollo | Servidor incorporado de PHP (`php -S`) | Pruebas locales |
| Cliente | HTML5, CSS3, JavaScript vanilla | Maquetación y validación ligera en formularios |
| Control de versiones | Git (recomendado) | Historial de cambios *(ajustar si no se usó)* |
| Documentación de flujo | Diagrama draw.io | Referencia de negocio frente a la implementación |

#### Decisiones adoptadas

1. **Carrito en sesión** hasta confirmar el pedido, para no multiplicar tablas temporales en servidor.
2. **Checkout en pasos en sesión** (`checkout_step`, borrador de envío/pago) para guiar al usuario y validar antes de escribir en BD.
3. **Página de catálogo separada del inicio** para distinguir «escaparate» (destacados) de «listado/búsqueda» y mejorar la orientación del usuario.
4. **Redirección de URLs antiguas** de búsqueda sobre el inicio hacia el catálogo, si se documentó ese cambio en el proyecto.

#### Problemas encontrados y cómo se abordaron

- **Correo electrónico en entornos de desarrollo:** `mail()` puede fallar sin configuración SMTP; se captura el error y se informa en la pantalla de confirmación sin revertir el pedido ya guardado.
- **Coherencia de esquema entre máquinas:** se documentó la ejecución de scripts de instalación y migración para evitar errores por columnas ausentes (pedidos ampliados, tabla de resets).
- **Seguridad del parámetro `next` tras el login:** solo se aceptan rutas internas relativas que empiezan por `/` y no por `//`, reduciendo riesgo de redirección abierta.

---

*Fin del borrador de los apartados 3 y 4.*
