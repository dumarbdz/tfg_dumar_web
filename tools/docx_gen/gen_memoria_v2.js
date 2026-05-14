const {
  Document, Packer, Paragraph, TextRun, Table, TableRow, TableCell,
  Header, Footer, AlignmentType, HeadingLevel, BorderStyle, WidthType,
  ShadingType, PageNumber, PageBreak, LevelFormat, TableOfContents
} = require('docx');
const fs = require('fs');
const path = require('path');

const outPath = path.join(__dirname, '..', '..', 'MEMORIA_TFG_DumarBermudez.docx');

// ── helpers ──────────────────────────────────────────────────────────────────
function p(text) {
  return new Paragraph({
    children: [new TextRun({ text, font: 'Arial', size: 24 })],
    spacing: { before: 0, after: 180 },
    alignment: AlignmentType.JUSTIFIED,
  });
}
function pCenter(text, opts = {}) {
  return new Paragraph({
    children: [new TextRun({ text, font: 'Arial', size: 24, ...opts })],
    alignment: AlignmentType.CENTER,
    spacing: { before: 0, after: 120 },
  });
}
function blank() {
  return new Paragraph({ children: [new TextRun('')], spacing: { before: 0, after: 0 } });
}
function bullet(text) {
  return new Paragraph({
    numbering: { reference: 'bullets', level: 0 },
    children: [new TextRun({ text, font: 'Arial', size: 24 })],
    spacing: { before: 0, after: 80 },
    alignment: AlignmentType.JUSTIFIED,
  });
}
function code(text) {
  return new Paragraph({
    children: [new TextRun({ text, font: 'Courier New', size: 20, color: '1F3864' })],
    spacing: { before: 60, after: 60 },
    indent: { left: 720 },
  });
}

const bdLine = { style: BorderStyle.SINGLE, size: 4, color: 'AAAAAA' };
const bdAll = { top: bdLine, bottom: bdLine, left: bdLine, right: bdLine };

function hRow(cells, widths) {
  return new TableRow({
    tableHeader: true,
    children: cells.map((text, i) =>
      new TableCell({
        borders: bdAll,
        width: { size: widths[i], type: WidthType.DXA },
        shading: { fill: '1F3864', type: ShadingType.CLEAR },
        margins: { top: 80, bottom: 80, left: 140, right: 140 },
        children: [new Paragraph({
          children: [new TextRun({ text, bold: true, color: 'FFFFFF', font: 'Arial', size: 22 })],
        })],
      })
    ),
  });
}
function dRow(cells, widths, alt = false) {
  return new TableRow({
    children: cells.map((text, i) =>
      new TableCell({
        borders: bdAll,
        width: { size: widths[i], type: WidthType.DXA },
        shading: { fill: alt ? 'EEF2F7' : 'FFFFFF', type: ShadingType.CLEAR },
        margins: { top: 60, bottom: 60, left: 140, right: 140 },
        children: [new Paragraph({
          children: [new TextRun({ text, font: 'Arial', size: 22 })],
          alignment: AlignmentType.JUSTIFIED,
        })],
      })
    ),
  });
}
function makeTable(headers, rows, widths) {
  const total = widths.reduce((a, b) => a + b, 0);
  return new Table({
    width: { size: total, type: WidthType.DXA },
    columnWidths: widths,
    rows: [
      hRow(headers, widths),
      ...rows.map((r, i) => dRow(r, widths, i % 2 === 1)),
    ],
  });
}

// ── DOCUMENT ─────────────────────────────────────────────────────────────────
const doc = new Document({
  styles: {
    default: { document: { run: { font: 'Arial', size: 24 } } },
    paragraphStyles: [
      {
        id: 'Heading1', name: 'Heading 1', basedOn: 'Normal', next: 'Normal', quickFormat: true,
        run: { size: 32, bold: true, font: 'Arial', color: '1F3864' },
        paragraph: { spacing: { before: 400, after: 200 }, outlineLevel: 0,
          border: { bottom: { style: BorderStyle.SINGLE, size: 6, color: '2E5FA3' } } },
      },
      {
        id: 'Heading2', name: 'Heading 2', basedOn: 'Normal', next: 'Normal', quickFormat: true,
        run: { size: 28, bold: true, font: 'Arial', color: '2E5FA3' },
        paragraph: { spacing: { before: 300, after: 120 }, outlineLevel: 1 },
      },
      {
        id: 'Heading3', name: 'Heading 3', basedOn: 'Normal', next: 'Normal', quickFormat: true,
        run: { size: 25, bold: true, font: 'Arial', color: '2E5FA3' },
        paragraph: { spacing: { before: 220, after: 80 }, outlineLevel: 2 },
      },
    ],
  },
  numbering: {
    config: [{
      reference: 'bullets',
      levels: [{
        level: 0, format: LevelFormat.BULLET, text: '•', alignment: AlignmentType.LEFT,
        style: { paragraph: { indent: { left: 720, hanging: 360 } } },
      }],
    }],
  },
  sections: [
    // ══════════════════════════════════════
    // PORTADA
    // ══════════════════════════════════════
    {
      properties: {
        page: { size: { width: 11906, height: 16838 }, margin: { top: 1440, right: 1440, bottom: 1440, left: 1440 } },
      },
      children: [
        blank(), blank(), blank(), blank(), blank(),
        pCenter('TRABAJO DE FIN DE CICLO', { bold: true, size: 34, color: '1F3864' }),
        pCenter('2.º DAW — Desarrollo de Aplicaciones Web', { size: 24, color: '555555' }),
        blank(), blank(),
        pCenter('Mundial Store', { bold: true, size: 56, color: '2E5FA3' }),
        pCenter('Tienda web de camisetas del Mundial 2026', { size: 28, color: '555555' }),
        blank(), blank(), blank(),
        pCenter('Alumno: Dumar Bermudez', { size: 24 }),
        pCenter('Tutor: Iker Jiménez', { size: 24 }),
        pCenter('IES Enrique Tierno Galván', { size: 24 }),
        pCenter('2.º DAW — Desarrollo de Aplicaciones Web', { size: 24 }),
        pCenter('18 de mayo de 2026', { size: 24, color: '555555' }),
      ],
    },
    // ══════════════════════════════════════
    // CUERPO
    // ══════════════════════════════════════
    {
      properties: {
        page: { size: { width: 11906, height: 16838 }, margin: { top: 1440, right: 1440, bottom: 1440, left: 1440 } },
      },
      headers: {
        default: new Header({ children: [new Paragraph({
          border: { bottom: { style: BorderStyle.SINGLE, size: 6, color: '2E5FA3' } },
          children: [new TextRun({ text: 'Mundial Store — TFC 2.º DAW — Dumar Bermudez', font: 'Arial', size: 18, color: '888888' })],
        })] }),
      },
      footers: {
        default: new Footer({ children: [new Paragraph({
          alignment: AlignmentType.CENTER,
          border: { top: { style: BorderStyle.SINGLE, size: 4, color: 'CCCCCC' } },
          children: [
            new TextRun({ text: 'Página ', font: 'Arial', size: 18, color: '888888' }),
            new TextRun({ children: [PageNumber.CURRENT], font: 'Arial', size: 18, color: '888888' }),
          ],
        })] }),
      },
      children: [

        // ─── ÍNDICE ───────────────────────────────────────────────────────────
        new Paragraph({ heading: HeadingLevel.HEADING_1, pageBreakBefore: false,
          children: [new TextRun({ text: 'Índice', bold: true, font: 'Arial', size: 32 })] }),
        new TableOfContents('', { hyperlink: true, headingStyleRange: '1-3' }),
        new Paragraph({ children: [new PageBreak()] }),

        // ─── 1. INTRODUCCIÓN ──────────────────────────────────────────────────
        new Paragraph({ heading: HeadingLevel.HEADING_1, pageBreakBefore: false,
          children: [new TextRun({ text: '1. Introducción', bold: true, font: 'Arial', size: 32 })] }),

        new Paragraph({ heading: HeadingLevel.HEADING_2,
          children: [new TextRun({ text: '1.1. Descripción del proyecto', bold: true, font: 'Arial', size: 28 })] }),

        p('Mundial Store es una tienda web de camisetas de fútbol centrada en el Mundial 2026. La idea central es que el usuario pueda entrar, mirar el catálogo, filtrar por selección y talla y añadir artículos al carrito sin necesidad de tener cuenta. El registro solo aparece cuando llega al checkout y quiere confirmar el pedido, que es el momento en que tiene sentido pedirlo.'),
        p('Técnicamente la aplicación está construida con PHP 8 y MySQL, sin framework. El código está organizado en carpetas con responsabilidades separadas: las páginas en public/, la lógica compartida (sesión, carrito, seguridad, correo) en includes/, el SQL en sql/ y los scripts de instalación y migración en tools/. La base de datos maneja usuarios, productos con stock por talla, pedidos con sus líneas, tokens de recuperación de contraseña e intentos de login para el rate limiting.'),
        p('Además de la tienda pública, el proyecto incluye un panel de administración para gestionar el catálogo, solo accesible para usuarios con el rol de administrador.'),

        new Paragraph({ heading: HeadingLevel.HEADING_2,
          children: [new TextRun({ text: '1.2. Motivación', bold: true, font: 'Arial', size: 28 })] }),

        p('Cuando empecé a pensar en el TFC tenía claro que quería hacer algo con funcionalidad real, no solo un ejercicio de maquetación. El fútbol me gusta y el Mundial 2026 estaba en el ambiente, así que una tienda de camisetas tenía sentido: hay productos, tallas, stock, carrito, checkout, correo… todo lo que tiene una tienda online de verdad, sin tener que inventarse el dominio.'),
        p('A nivel técnico, decidí no usar Laravel ni ningún otro framework. No porque sean malos, sino porque quería entender qué pasa por debajo: cómo se arranca una sesión, cómo se protege un formulario contra CSRF, cómo se hace una transacción que toca varias tablas a la vez. Todo eso queda oculto cuando el framework lo gestiona solo. El precio es que hay que escribir más código; la ganancia es que entiendes exactamente qué hace cada línea.'),

        // ─── 2. GESTIÓN Y ORGANIZACIÓN ────────────────────────────────────────
        new Paragraph({ heading: HeadingLevel.HEADING_1, pageBreakBefore: true,
          children: [new TextRun({ text: '2. Gestión y organización del proyecto', bold: true, font: 'Arial', size: 32 })] }),

        new Paragraph({ heading: HeadingLevel.HEADING_2,
          children: [new TextRun({ text: '2.1. Metodología', bold: true, font: 'Arial', size: 28 })] }),

        p('Para organizar el trabajo usé el modelo en cascada. La razón es sencilla: los requisitos de una tienda online están bastante claros desde el principio, trabajaba solo y el alcance estaba bien delimitado. En esas condiciones, la cascada da una estructura útil: primero analizas, luego diseñas, luego implementas, y sabes en cada momento en qué fase estás y qué te queda.'),
        p('Una metodología ágil con sprints habría tenido sentido con un equipo y unos requisitos más cambiantes, pero aquí solo añadiría burocracia innecesaria. Ágil para un solo desarrollador con requisitos cerrados no aporta mucho en la práctica.'),
        p('Hubo un retroceso puntual: a mitad de la implementación me di cuenta de que la tabla de pedidos necesitaba columnas para los datos de envío y el método de pago, que no había incluido en el esquema inicial. Lo resolví modificando el schema.sql y creando un script de migración incremental para no tener que recrear la base de datos desde cero.'),

        new Paragraph({ heading: HeadingLevel.HEADING_2,
          children: [new TextRun({ text: '2.2. Planificación y desviaciones', bold: true, font: 'Arial', size: 28 })] }),

        p('El desarrollo completo se realizó entre el 4 y el 14 de mayo de 2026: diez días naturales con el peso real del trabajo concentrado en los últimos dos días. La siguiente tabla recoge la distribución cronológica real, sin ajustar los tiempos:'),
        blank(),
        makeTable(
          ['Fecha', 'Actividad principal', 'Ficheros resultantes'],
          [
            ['4–5 mayo', 'Arranque del proyecto: análisis de requisitos, diseño del esquema entidad-relación y la estructura de carpetas, primeros ficheros de infraestructura (bootstrap, database, logout, migrate)', 'includes/bootstrap.php, config/database.php, tools/migrate_flow.php'],
            ['12 mayo', 'Seed de datos de productos y tallas, fichero de configuración de ejemplo, ajustes de esquema', 'sql/seed.sql, config/config.example.php'],
            ['13 mayo (00:00–03:00)', 'Módulo de contacto, panel de administración base, registro, login con rate limiting, logout, recuperación de contraseña, historial de pedidos', 'public/register.php, login.php, forgot_password.php, reset_password.php, orders.php, admin/'],
            ['13 mayo (03:00–14:00)', 'Funciones compartidas completas (CSRF, carrito, checkout draft, correo SMTP, safe_next_url), carrito, wishlist, confirmación de pedido, setup de tablas', 'includes/functions.php, cart.php, cart_add.php, order_confirm.php, setup_tables.php'],
            ['13 mayo (12:00–15:00)', 'Checkout completo con transacción atómica, catálogo con búsqueda y filtros, ficha de producto, página de inicio, CSS', 'checkout.php, catalog.php, product.php, index.php, css/style.css'],
            ['13–14 mayo', 'Pruebas funcionales y de seguridad, corrección de incidencias, diagrama de flujo en draw.io, redacción de la memoria', 'Diagrama_Tienda_Flujo.drawio, MEMORIA_TFG_DumarBermudez.docx'],
          ],
          [1600, 4800, 2700]
        ),
        blank(),
        p('El proyecto duró exactamente 10 días naturales: del 4 al 14 de mayo. En la práctica el peso del desarrollo se concentró en el 13 de mayo, donde en una sesión de trabajo muy larga se implementaron prácticamente todos los módulos. El día 4 sirvió para arrancar la estructura y el día 12 para preparar los datos; el 13 fue el día de construcción real y el 14 se dedicó a pruebas y documentación.'),
        p('La parte que tomó más tiempo dentro de ese día fue el checkout: coordinar los tres pasos con borrador en sesión, la validación reactiva del paso activo y la transacción con SELECT … FOR UPDATE sobre el stock resultó más compleja de lo previsto. El cliente SMTP también llevó su tiempo —implementarlo sobre sockets con STARTTLS en lugar de usar mail() fue una decisión que resolvió el problema en Windows pero añadió código que había que probar con cuidado.'),

        new Paragraph({ heading: HeadingLevel.HEADING_2,
          children: [new TextRun({ text: '2.3. Herramientas utilizadas', bold: true, font: 'Arial', size: 28 })] }),

        p('Las herramientas que usé durante el desarrollo son las siguientes:'),
        blank(),
        makeTable(
          ['Herramienta', 'Versión / variante', 'Uso en el proyecto'],
          [
            ['PHP', '8.x', 'Lenguaje principal del servidor. Declaraciones strict_types en todos los archivos.'],
            ['MySQL / MariaDB', '8.0 / 10.x', 'Base de datos relacional. En local se usó MariaDB; el SQL es compatible con ambos motores.'],
            ['Servidor de desarrollo PHP', 'php -S localhost:8080 -t public', 'Servidor integrado de PHP para pruebas locales. No requiere Apache ni Nginx.'],
            ['PDO', 'Extensión de PHP', 'Acceso a la base de datos con consultas preparadas en todos los puntos de entrada de datos.'],
            ['draw.io', 'Aplicación web', 'Creación del diagrama de flujo de navegación de la tienda.'],
            ['Git', '—', 'Control de versiones del código fuente.'],
            ['Visual Studio Code', '—', 'Editor de código utilizado durante el desarrollo.'],
          ],
          [2000, 2200, 4900]
        ),
        blank(),

        // ─── 3. OBJETIVOS ─────────────────────────────────────────────────────
        new Paragraph({ heading: HeadingLevel.HEADING_1, pageBreakBefore: true,
          children: [new TextRun({ text: '3. Objetivos', bold: true, font: 'Arial', size: 32 })] }),

        p('Antes de ponerme a programar apunté lo que tenía que hacer la aplicación. A continuación recojo esos objetivos con el estado en que han quedado y dónde se puede comprobar cada uno en el código.'),

        new Paragraph({ heading: HeadingLevel.HEADING_2,
          children: [new TextRun({ text: '3.1. Objetivos generales', bold: true, font: 'Arial', size: 28 })] }),

        blank(),
        makeTable(
          ['Código', 'Objetivo', 'Estado', 'Evidencia en el producto'],
          [
            ['OG1', 'Catálogo navegable con página de inicio (destacados) y página de búsqueda/listado con filtros', 'Alcanzado', 'index.php muestra productos con flag destacado; catalog.php admite búsqueda por texto y filtros por selección, modelo y talla con stock > 0'],
            ['OG2', 'Carrito de compra para usuario invitado; autenticación exigida únicamente al tramitar la compra', 'Alcanzado', 'cart_items() lee de $_SESSION sin comprobar sesión de usuario; checkout.php redirige a /login.php?next=/checkout.php si current_user() devuelve null'],
            ['OG3', 'Checkout por pasos (envío, método de pago, confirmación) con pedido guardado en BD de forma atómica', 'Alcanzado', 'checkout_draft_get/merge() gestiona el borrador en sesión; la confirmación ejecuta BEGIN/INSERT/UPDATE/COMMIT con SELECT … FOR UPDATE sobre el stock'],
            ['OG4', 'Correo de confirmación de pedido sin bloquear la compra si el envío falla', 'Alcanzado', 'send_order_confirmation_email() se llama tras el COMMIT; si smtp_send() devuelve false, el pedido sigue guardado y order_confirm.php informa al usuario'],
            ['OG5', 'Recuperación de contraseña con token de un solo uso, caducidad de 1 hora y hash almacenado en BD', 'Alcanzado', 'forgot_password.php genera token con bin2hex(random_bytes(32)), guarda SHA-256 en password_resets con expires_at = NOW() + 1h; reset_password.php marca used_at'],
            ['OG6', 'Panel de administración de productos accesible solo para el rol admin', 'Alcanzado', 'require_admin() en todos los scripts del admin comprueba is_admin; devuelve 403 si el usuario no tiene el flag activado'],
          ],
          [700, 2800, 1100, 4500]
        ),
        blank(),

        new Paragraph({ heading: HeadingLevel.HEADING_2,
          children: [new TextRun({ text: '3.2. Objetivos técnicos y de seguridad', bold: true, font: 'Arial', size: 28 })] }),

        blank(),
        makeTable(
          ['Código', 'Objetivo', 'Estado', 'Notas'],
          [
            ['OT1', 'Separación en capas: presentación (public/), lógica compartida (includes/), datos (sql/)', 'Alcanzado', 'Toda la lógica reutilizable está en includes/functions.php; los scripts de public/ son puntos de entrada HTTP que incluyen bootstrap.php'],
            ['OT2', 'Protección CSRF en formularios con operaciones mutables mediante hash_equals()', 'Alcanzado', 'csrf_token() y csrf_verify() en functions.php; todos los formularios POST incluyen campo _csrf'],
            ['OT3', 'Contraseñas almacenadas con password_hash() y verificadas con password_verify()', 'Alcanzado', 'Sin excepción en registro y login; nunca se guarda la contraseña en claro ni reversible'],
            ['OT4', 'Sesiones con httponly y samesite=Lax; regeneración de ID tras login exitoso', 'Alcanzado', 'session_start() con esas opciones en bootstrap.php; session_regenerate_id(true) en login.php tras autenticación correcta'],
            ['OT5', 'Rate limiting en login: bloqueo por IP tras 5 intentos fallidos durante 1 minuto', 'Alcanzado', 'Tabla intentos_login con lógica INSERT … ON DUPLICATE KEY UPDATE; al llegar a 5 intentos se envía email de alerta al titular de la cuenta'],
            ['OT6', 'Validación del parámetro next para prevenir open redirect', 'Alcanzado', 'safe_next_url() acepta únicamente rutas que empiecen por "/" y no por "//"; cualquier otro valor se descarta y se redirige al inicio'],
            ['OT7', 'Consultas preparadas en todos los puntos de entrada de datos', 'Alcanzado', 'Uso exclusivo de PDO con $pdo->prepare() + execute(); sin concatenación de variables en SQL'],
          ],
          [700, 2500, 1100, 4800]
        ),
        blank(),

        new Paragraph({ heading: HeadingLevel.HEADING_2,
          children: [new TextRun({ text: '3.3. Objetivos no alcanzados', bold: true, font: 'Arial', size: 28 })] }),

        p('Para no dar una imagen engañosa del proyecto, dejo claro lo que no está o no está completo:'),
        bullet('Pasarela de pago real: el pago va simulado. El sistema deja elegir entre tarjeta, Bizum y transferencia, pero no hay movimiento económico real. Integrar Stripe o un TPV bancario habría añadido una complejidad y unos costes de certificación que van más allá del objetivo académico de este TFC.'),
        bullet('Correo garantizado en todos los entornos: el cliente SMTP funciona cuando el servidor está configurado, pero en Windows sin SMTP el envío falla. El sistema lo gestiona sin comprometer el pedido, pero el correo no llega. Esto es una limitación conocida y documentada, no un error.'),
        blank(),

        // ─── 4. DESARROLLO ────────────────────────────────────────────────────
        new Paragraph({ heading: HeadingLevel.HEADING_1, pageBreakBefore: true,
          children: [new TextRun({ text: '4. Desarrollo de la aplicación', bold: true, font: 'Arial', size: 32 })] }),

        new Paragraph({ heading: HeadingLevel.HEADING_2,
          children: [new TextRun({ text: '4.1. Análisis — Requisitos del sistema', bold: true, font: 'Arial', size: 28 })] }),

        new Paragraph({ heading: HeadingLevel.HEADING_3,
          children: [new TextRun({ text: 'Requisitos funcionales', bold: true, font: 'Arial', size: 25 })] }),

        blank(),
        makeTable(
          ['Código', 'Descripción'],
          [
            ['RF01', 'El sistema mostrará en la página de inicio los productos marcados como destacados, con imagen, nombre, precio y acceso a la ficha.'],
            ['RF02', 'El catálogo permitirá búsqueda por texto libre (nombre y selección) y filtrado por selección, modelo y talla; solo se mostrarán combinaciones con stock disponible.'],
            ['RF03', 'La ficha de producto mostrará descripción, precio, tallas disponibles y permitirá añadir al carrito seleccionando talla y cantidad, sin necesidad de sesión activa.'],
            ['RF04', 'El carrito, accesible sin autenticación, mostrará las líneas con imagen, nombre, talla, cantidad y precio; permitirá modificar cantidades y eliminar líneas con actualización de totales.'],
            ['RF05', 'El proceso de checkout constará de tres pasos secuenciales: datos de envío, método de pago (tarjeta / Bizum / transferencia) y confirmación; el usuario podrá retroceder a cualquier paso anterior.'],
            ['RF06', 'Al confirmar el pedido, el sistema lo registrará en la base de datos de forma atómica, decrementará el stock y enviará un correo de confirmación al cliente y una copia al administrador.'],
            ['RF07', 'El sistema permitirá el registro de nuevas cuentas con email único y contraseña, y el inicio y cierre de sesión.'],
            ['RF08', 'El flujo de recuperación de contraseña generará un token de uso único con caducidad de una hora, enviará el enlace al email del usuario y permitirá establecer una nueva contraseña.'],
            ['RF09', 'Los usuarios registrados podrán consultar el historial de sus pedidos y guardar una dirección de envío por defecto para agilizar futuros checkouts.'],
            ['RF10', 'Los usuarios administradores tendrán acceso a un panel para crear, editar y activar/desactivar productos del catálogo.'],
          ],
          [800, 8300]
        ),
        blank(),

        new Paragraph({ heading: HeadingLevel.HEADING_3,
          children: [new TextRun({ text: 'Requisitos no funcionales', bold: true, font: 'Arial', size: 25 })] }),

        blank(),
        makeTable(
          ['Código', 'Descripción'],
          [
            ['RNF01', 'Usabilidad: la navegación entre inicio, catálogo, ficha, carrito y checkout debe ser clara y no requerir registro hasta el pago.'],
            ['RNF02', 'Seguridad: protección CSRF en formularios con estado mutable, contraseñas hasheadas, rate limiting en login, sesiones seguras (httponly, samesite=Lax, regeneración de ID) y validación de redirecciones externas.'],
            ['RNF03', 'Integridad de datos: uso de transacciones y bloqueos de fila (SELECT … FOR UPDATE) al crear pedidos para prevenir condiciones de carrera sobre el stock.'],
            ['RNF04', 'Mantenibilidad: separación funcional por carpetas (public/, includes/, sql/, tools/) que permita localizar y modificar cualquier componente sin afectar al resto.'],
            ['RNF05', 'Compatibilidad: funcionamiento correcto en Chrome y Firefox; generación de HTML semántico con atributos de accesibilidad básicos (role, aria-label).'],
          ],
          [800, 8300]
        ),
        blank(),

        new Paragraph({ heading: HeadingLevel.HEADING_3,
          children: [new TextRun({ text: 'Actores del sistema', bold: true, font: 'Arial', size: 25 })] }),

        p('La aplicación tiene tres tipos de usuario:'),
        bullet('Visitante: puede navegar, buscar y añadir al carrito sin tener cuenta. Es el punto de entrada natural para cualquiera que llegue a la tienda.'),
        bullet('Cliente registrado: hace todo lo anterior más tramitar la compra, ver el historial de pedidos y guardar una dirección de envío para futuros checkouts. La cuenta se puede crear en cualquier momento, también durante el propio checkout.'),
        bullet('Administrador: accede al panel de gestión del catálogo además de todo lo anterior. Se distingue por el campo es_admin en la tabla de usuarios.'),
        blank(),

        new Paragraph({ heading: HeadingLevel.HEADING_2,
          children: [new TextRun({ text: '4.2. Diseño', bold: true, font: 'Arial', size: 28 })] }),

        new Paragraph({ heading: HeadingLevel.HEADING_3,
          children: [new TextRun({ text: '4.2.1. Arquitectura de la aplicación', bold: true, font: 'Arial', size: 25 })] }),

        p('No usé un framework MVC, pero sí quise que el código tuviera cierta estructura en lugar de mezclar lógica y HTML en un solo fichero enorme. Lo organicé en tres capas informales:'),
        bullet('Presentación: los ficheros de public/ son los puntos de entrada HTTP. Cada uno gestiona la petición, ejecuta las consultas o validaciones necesarias y genera el HTML, incluyendo la cabecera y el pie comunes.'),
        bullet('Lógica compartida: en includes/ están bootstrap.php (que arranca la sesión con las opciones de seguridad) y functions.php (con todas las funciones reutilizables: carrito, CSRF, autenticación, checkout, correo y redirecciones seguras). Cualquier página que necesite algo de eso simplemente lo incluye.'),
        bullet('Acceso a datos: toda la comunicación con la base de datos pasa por PDO con consultas preparadas. La conexión se obtiene mediante get_pdo() y no hay ningún punto del código donde se concatenen variables directamente en SQL.'),
        p('No añadí abstracciones adicionales como repositorios o servicios inyectados. Para un proyecto de este tamaño habría sido sobredimensionado y habría complicado el código sin aportar nada real.'),

        new Paragraph({ heading: HeadingLevel.HEADING_3,
          children: [new TextRun({ text: '4.2.2. Diseño de la base de datos', bold: true, font: 'Arial', size: 25 })] }),

        p('La base de datos tiene siete tablas. Usé InnoDB en todas porque necesitaba claves foráneas y, sobre todo, transacciones para el checkout.'),
        blank(),
        makeTable(
          ['Tabla', 'Columnas principales', 'Relaciones'],
          [
            ['usuarios', 'id, email (UNIQUE), contrasena_hash, nombre, dir_nombre, dir_linea1, dir_postal, dir_ciudad, dir_pais, es_admin', '—'],
            ['productos', 'id, continente (marca/selección), seleccion (modelo), slug (UNIQUE), descripcion, precio, imagen_path, activo', '—'],
            ['stock', 'id, producto_id, talla, cantidad; UNIQUE (producto_id, talla)', 'FK → productos'],
            ['pedidos', 'id, usuario_id, total, estado, envio_nombre, envio_linea1, envio_postal, envio_ciudad, envio_pais, metodo_pago, created_at', 'FK → usuarios'],
            ['lineas_pedido', 'id, pedido_id, producto_id, talla, cantidad, precio_unitario', 'FK → pedidos, FK → productos'],
            ['password_resets', 'id, user_id, token_hash (CHAR 64, SHA-256), expires_at, used_at', 'FK → usuarios (CASCADE DELETE)'],
            ['intentos_login', 'ip (PK VARCHAR), intentos, bloqueado_hasta', '—'],
          ],
          [1800, 4200, 3100]
        ),
        blank(),
        p('El esquema no salió completo a la primera. La tabla de pedidos empezó más simple y tuve que ampliarla durante la implementación para añadir los campos de envío y el método de pago, lo que generó la necesidad del script tools/migrate_flow.php para actualizar instalaciones existentes sin perder los datos.'),
        p('La tabla intentos_login no tiene clave foránea hacia usuarios de forma intencionada: el bloqueo se aplica por IP antes de comprobar si el email existe, para no revelar si una cuenta está o no está registrada.'),

        new Paragraph({ heading: HeadingLevel.HEADING_3,
          children: [new TextRun({ text: '4.2.3. Flujo de navegación', bold: true, font: 'Arial', size: 25 })] }),

        p('El flujo principal está documentado en el diagrama draw.io adjunto. A grandes rasgos funciona así:'),
        bullet('El usuario llega al inicio, ve los productos destacados y desde ahí puede ir al catálogo, a la ficha de un producto o al carrito.'),
        bullet('Puede añadir al carrito sin tener cuenta. El carrito muestra las líneas y permite modificar cantidades o eliminarlas.'),
        bullet('Al intentar acceder al checkout sin sesión, el sistema redirige al login con el parámetro next=/checkout.php para volver al punto correcto una vez autenticado.'),
        bullet('El checkout avanza por tres pasos según el estado del borrador guardado en sesión. El paso activo se determina en cada petición evaluando si los datos de envío y pago del borrador están completos, sin necesidad de guardar el número de paso explícitamente.'),
        bullet('Al confirmar, la transacción incluye un SELECT … FOR UPDATE sobre el stock para bloquear las filas y evitar que dos usuarios compren el último artículo a la vez.'),
        bullet('Tras el COMMIT, el carrito y el borrador se borran de sesión y el usuario va a la pantalla de confirmación con el número de pedido.'),
        blank(),

        new Paragraph({ heading: HeadingLevel.HEADING_2,
          children: [new TextRun({ text: '4.3. Implementación', bold: true, font: 'Arial', size: 28 })] }),

        new Paragraph({ heading: HeadingLevel.HEADING_3,
          children: [new TextRun({ text: '4.3.1. Gestión de sesión y seguridad transversal', bold: true, font: 'Arial', size: 25 })] }),

        p('Todo script de public/ empieza incluyendo bootstrap.php, que arranca la sesión con cookie_httponly y cookie_samesite=Lax. Esto evita que JavaScript acceda a la cookie de sesión y limita su envío en peticiones cross-site. Cuando el login tiene éxito, llamo a session_regenerate_id(true) para regenerar el identificador y prevenir ataques de fijación de sesión.'),
        p('La protección CSRF la implementé con dos funciones en functions.php: csrf_token() genera un token de 64 caracteres con random_bytes() y lo guarda en sesión; csrf_verify() lo compara con el que viene en el formulario usando hash_equals(), que evita ataques de temporización. Todos los formularios POST tienen un campo oculto _csrf con el token.'),
        p('Para escapar la salida HTML usé la función h(), que aplica htmlspecialchars() con ENT_QUOTES | ENT_SUBSTITUTE en UTF-8. La uso en todos los puntos donde imprimo datos del usuario o de la base de datos.'),

        new Paragraph({ heading: HeadingLevel.HEADING_3,
          children: [new TextRun({ text: '4.3.2. Sistema de autenticación y rate limiting', bold: true, font: 'Arial', size: 25 })] }),

        p('El login sigue esta secuencia en login.php:'),
        bullet('Primero verifica el token CSRF del formulario.'),
        bullet('Consulta la tabla intentos_login por la IP del cliente. Si hay un registro con bloqueado_hasta en el futuro, deniega el acceso mostrando los segundos que quedan.'),
        bullet('Si no está bloqueada, busca el usuario por email y verifica la contraseña con password_verify(). Si es correcta, borra los intentos, regenera el ID de sesión y redirige.'),
        bullet('Si falla, incrementa el contador con INSERT … ON DUPLICATE KEY UPDATE. Al llegar a 5 intentos, establece el bloqueo de 1 minuto y envía un correo de alerta de seguridad al titular de la cuenta.'),
        p('El registro usa password_hash() con PASSWORD_DEFAULT, que en PHP 8 es bcrypt con salt automático. Las contraseñas nunca se guardan en claro ni de forma reversible.'),
        p('Para la recuperación de contraseña, genero el token con bin2hex(random_bytes(32)) y guardo en la base de datos su hash SHA-256, no el token en claro. El enlace que llega al usuario tiene el token original. En reset_password.php calculo el hash del token recibido y lo busco en la tabla; si existe, no ha expirado y no ha sido usado, permito establecer la nueva contraseña y marco used_at para que no se pueda reutilizar.'),

        new Paragraph({ heading: HeadingLevel.HEADING_3,
          children: [new TextRun({ text: '4.3.3. Carrito y gestión de sesión', bold: true, font: 'Arial', size: 25 })] }),

        p('El carrito vive en $_SESSION[\'cart\'] como un array asociativo indexado por la clave producto_id|talla. Toda la lógica de lectura y modificación está en functions.php mediante cart_items(), cart_key() y cart_set_qty(), que validan los tipos antes de operar para evitar estados raros en sesión.'),
        p('Guardarlo en sesión en vez de en base de datos fue una decisión consciente: para el tamaño de este proyecto simplifica mucho el código y no hace falta sincronizar nada. La contrapartida es que el carrito se pierde si expira la sesión o el usuario cambia de dispositivo, pero eso es perfectamente aceptable aquí y es el comportamiento habitual de muchas tiendas en modo invitado.'),

        new Paragraph({ heading: HeadingLevel.HEADING_3,
          children: [new TextRun({ text: '4.3.4. Checkout por pasos y transacción atómica', bold: true, font: 'Arial', size: 25 })] }),

        p('El borrador del checkout (datos de envío y método de pago) se guarda en $_SESSION[\'checkout\'] mediante las funciones checkout_draft_get(), checkout_draft_merge() y checkout_clear_draft(). El paso activo no se guarda explícitamente: se recalcula en cada petición evaluando si el borrador tiene los campos de envío y pago completos. Esto simplifica mucho la lógica porque no hay que gestionar transiciones de estado a mano.'),
        p('Al recibir el POST de confirmación, checkout.php ejecuta esta secuencia dentro de una transacción:'),
        bullet('1. beginTransaction()'),
        bullet('2. Para cada línea del carrito: SELECT cantidad FROM stock WHERE producto_id = ? AND talla = ? FOR UPDATE — bloquea la fila para evitar que otro proceso reduzca el stock concurrentemente.'),
        bullet('3. Si el stock es suficiente para todas las líneas: INSERT en pedidos con los datos de envío y método de pago.'),
        bullet('4. INSERT en lineas_pedido e UPDATE de stock para cada línea.'),
        bullet('5. Si el usuario marcó "guardar dirección": UPDATE en usuarios con los datos de envío.'),
        bullet('6. commit()'),
        p('Si cualquier paso falla, se ejecuta rollBack() y se muestra el error al usuario. La base de datos queda intacta. El correo se envía después del commit, de modo que si falla el envío el pedido ya está guardado y no se revierte.'),

        new Paragraph({ heading: HeadingLevel.HEADING_3,
          children: [new TextRun({ text: '4.3.5. Cliente SMTP propio', bold: true, font: 'Arial', size: 25 })] }),

        p('No usé la función mail() de PHP porque en Windows no funciona sin configurar sendmail, lo que en desarrollo local es un problema constante. En su lugar implementé un cliente SMTP mínimo en smtp_send() dentro de functions.php. Lo que hace es:'),
        bullet('Abrir una conexión TCP al servidor configurado en config/config.local.php.'),
        bullet('Hacer el handshake SMTP: EHLO, STARTTLS, EHLO de nuevo, AUTH LOGIN con usuario y contraseña en base64.'),
        bullet('Enviar el mensaje codificado en base64 con las cabeceras MIME necesarias, incluyendo el asunto en formato encoded-word (=?UTF-8?B?…?=) para que los caracteres especiales no den problemas.'),
        bullet('Cerrar el socket en un bloque finally, tanto si todo fue bien como si no.'),
        p('El correo de confirmación de pedido va al cliente y, si hay una dirección de admin configurada, también le llega una copia. El de recuperación de contraseña incluye el enlace con el token y avisa de que caduca en una hora.'),

        new Paragraph({ heading: HeadingLevel.HEADING_3,
          children: [new TextRun({ text: '4.3.6. Decisiones de diseño adicionales', bold: true, font: 'Arial', size: 25 })] }),

        p('Hay otras decisiones que vale la pena explicar:'),
        bullet('Inicio separado del catálogo: index.php muestra solo los productos destacados y catalog.php tiene la búsqueda y el listado completo. Al principio lo tenía todo junto y no funcionaba bien: al buscar, los resultados aparecían muy abajo y no era evidente que algo había cambiado. Separarlos mejoró mucho la experiencia.'),
        bullet('Dirección guardada en el checkout: si el usuario tiene una dirección guardada y el borrador de envío está vacío, checkout.php la carga automáticamente. Puede modificarla y decidir si quiere actualizarla para el futuro.'),
        bullet('Simulación de fallo de pago: en el paso de confirmación hay un checkbox para simular un pago rechazado. Es una pequeña ayuda para probar esa rama del flujo sin necesitar una pasarela real.'),
        blank(),

        // ─── 5. PRUEBAS ───────────────────────────────────────────────────────
        new Paragraph({ heading: HeadingLevel.HEADING_1, pageBreakBefore: true,
          children: [new TextRun({ text: '5. Realización de pruebas', bold: true, font: 'Arial', size: 32 })] }),

        new Paragraph({ heading: HeadingLevel.HEADING_2,
          children: [new TextRun({ text: '5.1. Estrategia de pruebas', bold: true, font: 'Arial', size: 28 })] }),

        p('Las pruebas las hice de forma manual, siguiendo los flujos que haría un usuario real. No usé PHPUnit ni ningún framework de testing (eso se queda como trabajo futuro), pero sí fui sistemático: probé cada módulo por separado y luego en conjunto.'),
        p('Hice tres tipos de pruebas:'),
        bullet('Funcionales: recorrer cada caso de uso con entradas correctas e incorrectas para verificar que el sistema responde bien en ambos casos.'),
        bullet('De seguridad básica: intentar inyectar SQL en búsqueda y login, enviar formularios sin token CSRF, manipular el parámetro next con una URL externa y acceder a rutas de admin sin permisos.'),
        bullet('De compatibilidad: verificar que todo funciona igual en Chrome y Firefox en Windows.'),

        new Paragraph({ heading: HeadingLevel.HEADING_2,
          children: [new TextRun({ text: '5.2. Resultados', bold: true, font: 'Arial', size: 28 })] }),

        blank(),
        makeTable(
          ['Módulo', 'Caso de prueba', 'Entrada / acción', 'Resultado esperado', 'Resultado'],
          [
            ['Catálogo', 'Mostrar inicio con destacados', 'Acceder a /', 'Productos destacados visibles', 'OK'],
            ['Catálogo', 'Búsqueda por texto', 'Buscar "España"', 'Listado filtrado por nombre', 'OK'],
            ['Catálogo', 'Filtro por talla sin stock', 'Filtrar talla sin unidades', 'No aparece ningún resultado', 'OK'],
            ['Carrito', 'Añadir sin sesión', 'Añadir desde ficha sin login', 'Artículo en carrito; sin redirección a login', 'OK'],
            ['Carrito', 'Superar stock disponible', 'Añadir más unidades de las existentes', 'Cantidad limitada al stock real', 'OK'],
            ['Carrito', 'Eliminar línea', 'Pulsar "Eliminar" en una línea', 'Línea desaparece; total actualizado', 'OK'],
            ['Checkout', 'Acceso sin sesión', 'Ir a /checkout.php sin login', 'Redirección a /login.php?next=/checkout.php', 'OK'],
            ['Checkout', 'Paso 1: datos de envío', 'Rellenar y enviar formulario', 'Paso 2 visible; datos guardados en sesión', 'OK'],
            ['Checkout', 'Paso 2: método de pago', 'Seleccionar "Bizum"', 'Paso 3 visible; método guardado en sesión', 'OK'],
            ['Checkout', 'Confirmar pedido', 'Enviar confirmación con carrito válido', 'Pedido en BD; stock decrementado; correo enviado (con SMTP)', 'OK'],
            ['Checkout', 'Simular pago rechazado', 'Marcar checkbox "simular fallo"', 'Error mostrado; pedido NO guardado', 'OK'],
            ['Checkout', 'Correo en entorno sin SMTP', 'Confirmar pedido en Windows sin SMTP', 'Pedido guardado; aviso en pantalla de que el correo no pudo enviarse', 'OK *'],
            ['Auth', 'Registro', 'Crear cuenta con email nuevo', 'Sesión activa; redirección al destino previsto', 'OK'],
            ['Auth', 'Login correcto', 'Email y contraseña válidos', 'Sesión activa; ID regenerado; redirección', 'OK'],
            ['Auth', 'Login con credenciales erróneas', '5 intentos fallidos seguidos', 'Bloqueo por 1 minuto; email de alerta enviado', 'OK'],
            ['Auth', 'Logout', 'Pulsar "Cerrar sesión"', 'Sesión destruida; redirección al inicio', 'OK'],
            ['Recuperación', 'Solicitar enlace', 'Introducir email registrado', 'Email con enlace recibido en buzón', 'OK'],
            ['Recuperación', 'Usar token caducado', 'Acceder al enlace tras 1 hora', 'Mensaje de token inválido o expirado', 'OK'],
            ['Recuperación', 'Reusar token ya usado', 'Acceder al enlace tras haberlo usado', 'Mensaje de token inválido o expirado', 'OK'],
            ['Seguridad', 'Inyección SQL en búsqueda', 'Introducir \' OR 1=1-- en el buscador', 'Consulta tratada como texto; sin resultados anómalos', 'OK'],
            ['Seguridad', 'Formulario sin CSRF', 'Enviar POST sin campo _csrf', 'Error "Sesión inválida"', 'OK'],
            ['Seguridad', 'Open redirect via next', 'login.php?next=//evil.com', 'Redirige al inicio, no al dominio externo', 'OK'],
            ['Seguridad', 'Acceso a admin sin permisos', 'Acceder a /admin/ con usuario normal', 'Respuesta 403 con mensaje de acceso denegado', 'OK'],
            ['Admin', 'Crear producto', 'Rellenar formulario de nuevo producto', 'Producto visible en catálogo', 'OK'],
            ['Admin', 'Desactivar producto', 'Pulsar "Desactivar" en un producto', 'Producto no visible en catálogo público', 'OK'],
          ],
          [1100, 1900, 2100, 2100, 800]
        ),
        blank(),
        p('* En entornos sin SMTP el correo no llega, pero el pedido sí se guarda. La pantalla de confirmación informa al usuario de que el correo no pudo enviarse. Es un comportamiento intencionado y documentado.'),

        // ─── 6. CONCLUSIONES ──────────────────────────────────────────────────
        new Paragraph({ heading: HeadingLevel.HEADING_1, pageBreakBefore: true,
          children: [new TextRun({ text: '6. Conclusiones', bold: true, font: 'Arial', size: 32 })] }),

        p('Mirando el resultado, creo que he conseguido lo que me propuse. La tienda funciona de principio a fin: puedes buscar una camiseta, añadirla al carrito, crear una cuenta justo cuando vas a pagar, completar los tres pasos del checkout y recibir la confirmación. El pedido queda en la base de datos, el stock se descuenta de forma atómica y si algo falla en el correo el pedido no se pierde.'),
        p('Lo que más me ha aportado ha sido, precisamente, lo que más trabajo me dio. El checkout fue la parte más compleja: coordinar el borrador en sesión, calcular el paso activo de forma reactiva y hacer que la transacción con SELECT … FOR UPDATE funcionara bien no fue rápido. Hubo bastante depuración. Lo mismo con el cliente SMTP: implementar el handshake ESMTP con STARTTLS sobre sockets enseña cosas que no aprendes configurando una librería ya hecha.'),
        p('Trabajar sin framework ha valido la pena para lo que quería aprender. El código es más largo de lo que sería en Laravel, pero sé exactamente qué hace cada parte: por qué la sesión arranca con esas opciones, qué aporta hash_equals en la verificación CSRF, para qué sirve el FOR UPDATE antes de tocar el stock. Eso no lo tendrías tan claro si el framework lo resolviera solo.'),
        p('Las limitaciones principales son conocidas: el pago es simulado y el correo depende de la configuración del servidor. No son errores, son decisiones de alcance documentadas. Para llevar esto a producción, lo primero sería integrar una pasarela real y configurar un servicio de correo transaccional.'),
        p('En definitiva, diez días de trabajo concentrado que han producido una aplicación funcional con una base técnica que entiendo y podría mantener.'),

        // ─── 7. TRABAJO FUTURO ────────────────────────────────────────────────
        new Paragraph({ heading: HeadingLevel.HEADING_1, pageBreakBefore: true,
          children: [new TextRun({ text: '7. Trabajo futuro', bold: true, font: 'Arial', size: 32 })] }),

        p('Si siguiera desarrollando el proyecto, estas son las mejoras que más sentido tienen, ordenadas por impacto:'),
        blank(),
        makeTable(
          ['Mejora', 'Descripción', 'Impacto'],
          [
            ['Pasarela de pago real', 'Integración con Stripe o Redsys para procesar pagos reales. Requiere certificado SSL en producción y gestión del webhook de confirmación.', 'Alto'],
            ['Tests automatizados', 'Cobertura con PHPUnit de los flujos principales: añadir al carrito, checkout, autenticación y recuperación de contraseña. Permitiría detectar regresiones al modificar el código.', 'Alto'],
            ['Cola de correo', 'Desacoplar el envío de correo del flujo de petición HTTP mediante una cola de trabajos (p. ej. con una tabla de jobs en la BD procesada por un cron). Eliminaría el bloqueo en caso de SMTP lento.', 'Medio'],
            ['Sistema de valoraciones', 'Permitir a los clientes puntuar y comentar los productos comprados, con moderación desde el panel de admin.', 'Medio'],
            ['Panel de estadísticas', 'Visualización en el admin de ventas por período, productos más vendidos y alertas de stock bajo mínimos.', 'Medio'],
            ['Migración a framework', 'Migración a Laravel para obtener routing limpio, ORM, migraciones integradas, autenticación y correo ya resueltos. Recomendable si el proyecto escala en equipo.', 'Bajo'],
            ['Internacionalización', 'Soporte para múltiples idiomas mediante gettext o un sistema propio de cadenas. Encajaría bien dado el contexto internacional del Mundial.', 'Bajo'],
          ],
          [2000, 5000, 1400]
        ),
        blank(),

        // ─── 8. BIBLIOGRAFÍA ──────────────────────────────────────────────────
        new Paragraph({ heading: HeadingLevel.HEADING_1, pageBreakBefore: true,
          children: [new TextRun({ text: '8. Bibliografía', bold: true, font: 'Arial', size: 32 })] }),

        p('PHP Group. (2024). PHP 8 Manual. Recuperado de https://www.php.net/manual/es/'),
        p('PHP Group. (2024). PDO — PHP Data Objects. En PHP Manual. Recuperado de https://www.php.net/manual/es/book.pdo.php'),
        p('PHP Group. (2024). password_hash. En PHP Manual. Recuperado de https://www.php.net/manual/es/function.password-hash.php'),
        p('Oracle Corporation. (2024). MySQL 8.0 Reference Manual — InnoDB Locking and Transaction Model. Recuperado de https://dev.mysql.com/doc/refman/8.0/en/innodb-locking-transaction-model.html'),
        p('Mozilla Developer Network. (2024). HTTP cookies. Recuperado de https://developer.mozilla.org/es/docs/Web/HTTP/Cookies'),
        p('Mozilla Developer Network. (2024). HTML: HyperText Markup Language. Recuperado de https://developer.mozilla.org/es/docs/Web/HTML'),
        p('OWASP Foundation. (2021). OWASP Top 10 — 2021. Recuperado de https://owasp.org/Top10/'),
        p('OWASP Foundation. (2022). Cross-Site Request Forgery Prevention Cheat Sheet. Recuperado de https://cheatsheetseries.owasp.org/cheatsheets/Cross-Site_Request_Forgery_Prevention_Cheat_Sheet.html'),
        p('Internet Engineering Task Force. (2011). RFC 6409 — Message Submission for Mail. Recuperado de https://datatracker.ietf.org/doc/html/rfc6409'),
        p('Internet Engineering Task Force. (2001). RFC 3207 — SMTP Service Extension for Secure SMTP over Transport Layer Security. Recuperado de https://datatracker.ietf.org/doc/html/rfc3207'),
        p('JGraph Ltd. (2024). draw.io — Diagramming Software. Recuperado de https://app.diagrams.net/'),
      ],
    },
  ],
});

Packer.toBuffer(doc).then(buffer => {
  fs.writeFileSync(outPath, buffer);
  console.log('Creado: ' + outPath);
}).catch(err => {
  console.error('Error:', err);
  process.exit(1);
});
