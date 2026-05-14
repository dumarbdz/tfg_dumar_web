const {
  Document, Packer, Paragraph, TextRun, Table, TableRow, TableCell,
  Header, Footer, AlignmentType, HeadingLevel, BorderStyle, WidthType,
  ShadingType, VerticalAlign, PageNumber, PageBreak, LevelFormat,
  TableOfContents
} = require('docx');
const fs = require('fs');
const path = require('path');

const outPath = path.join(__dirname, '..', '..', 'MEMORIA_TFC_DumarBermudez.docx');

// ── helpers ──────────────────────────────────────────────────────────────────
function h1(text, bookmark) {
  return new Paragraph({
    heading: HeadingLevel.HEADING_1,
    pageBreakBefore: true,
    children: [new TextRun({ text, bold: true, size: 32, font: 'Arial' })],
  });
}
function h2(text) {
  return new Paragraph({
    heading: HeadingLevel.HEADING_2,
    children: [new TextRun({ text, bold: true, size: 28, font: 'Arial' })],
    spacing: { before: 240, after: 120 },
  });
}
function h3(text) {
  return new Paragraph({
    heading: HeadingLevel.HEADING_3,
    children: [new TextRun({ text, bold: true, size: 26, font: 'Arial' })],
    spacing: { before: 200, after: 100 },
  });
}
function p(text, opts = {}) {
  return new Paragraph({
    children: [new TextRun({ text, font: 'Arial', size: 24, ...opts })],
    spacing: { before: 0, after: 160 },
  });
}
function pBold(text) {
  return new Paragraph({
    children: [new TextRun({ text, bold: true, font: 'Arial', size: 24 })],
    spacing: { before: 0, after: 160 },
  });
}
function blank() {
  return new Paragraph({ children: [new TextRun('')], spacing: { before: 0, after: 0 } });
}

const border = { style: BorderStyle.SINGLE, size: 4, color: 'AAAAAA' };
const borders = { top: border, bottom: border, left: border, right: border };

function headerRow(cells, widths) {
  return new TableRow({
    tableHeader: true,
    children: cells.map((text, i) =>
      new TableCell({
        borders,
        width: { size: widths[i], type: WidthType.DXA },
        shading: { fill: '2E5FA3', type: ShadingType.CLEAR },
        margins: { top: 80, bottom: 80, left: 120, right: 120 },
        children: [new Paragraph({
          children: [new TextRun({ text, bold: true, color: 'FFFFFF', font: 'Arial', size: 22 })],
        })],
      })
    ),
  });
}
function dataRow(cells, widths, shade = false) {
  return new TableRow({
    children: cells.map((text, i) =>
      new TableCell({
        borders,
        width: { size: widths[i], type: WidthType.DXA },
        shading: { fill: shade ? 'F2F2F2' : 'FFFFFF', type: ShadingType.CLEAR },
        margins: { top: 60, bottom: 60, left: 120, right: 120 },
        children: [new Paragraph({
          children: [new TextRun({ text, font: 'Arial', size: 22 })],
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
      headerRow(headers, widths),
      ...rows.map((row, idx) => dataRow(row, widths, idx % 2 === 1)),
    ],
  });
}

// ── DOCUMENT ─────────────────────────────────────────────────────────────────
const doc = new Document({
  styles: {
    default: {
      document: { run: { font: 'Arial', size: 24 } },
    },
    paragraphStyles: [
      {
        id: 'Heading1', name: 'Heading 1', basedOn: 'Normal', next: 'Normal', quickFormat: true,
        run: { size: 32, bold: true, font: 'Arial', color: '1F3864' },
        paragraph: { spacing: { before: 360, after: 180 }, outlineLevel: 0 },
      },
      {
        id: 'Heading2', name: 'Heading 2', basedOn: 'Normal', next: 'Normal', quickFormat: true,
        run: { size: 28, bold: true, font: 'Arial', color: '2E5FA3' },
        paragraph: { spacing: { before: 280, after: 120 }, outlineLevel: 1 },
      },
      {
        id: 'Heading3', name: 'Heading 3', basedOn: 'Normal', next: 'Normal', quickFormat: true,
        run: { size: 26, bold: true, font: 'Arial', color: '2E5FA3' },
        paragraph: { spacing: { before: 200, after: 80 }, outlineLevel: 2 },
      },
    ],
  },
  numbering: {
    config: [
      {
        reference: 'bullets',
        levels: [{
          level: 0, format: LevelFormat.BULLET, text: '•', alignment: AlignmentType.LEFT,
          style: { paragraph: { indent: { left: 720, hanging: 360 } } },
        }],
      },
    ],
  },
  sections: [
    // ═══════════════════════════════════════
    // PORTADA (sin número de página)
    // ═══════════════════════════════════════
    {
      properties: {
        page: {
          size: { width: 11906, height: 16838 },
          margin: { top: 1440, right: 1440, bottom: 1440, left: 1440 },
        },
      },
      children: [
        blank(), blank(), blank(), blank(),
        new Paragraph({
          alignment: AlignmentType.CENTER,
          spacing: { before: 0, after: 480 },
          children: [new TextRun({ text: 'TRABAJO DE FIN DE CICLO', bold: true, size: 36, font: 'Arial', color: '1F3864' })],
        }),
        new Paragraph({
          alignment: AlignmentType.CENTER,
          spacing: { before: 0, after: 240 },
          children: [new TextRun({ text: '2.º DAW — Desarrollo de Aplicaciones Web', size: 26, font: 'Arial', color: '555555' })],
        }),
        blank(),
        new Paragraph({
          alignment: AlignmentType.CENTER,
          spacing: { before: 0, after: 120 },
          children: [new TextRun({ text: 'Mundial Store', bold: true, size: 52, font: 'Arial', color: '2E5FA3' })],
        }),
        new Paragraph({
          alignment: AlignmentType.CENTER,
          spacing: { before: 0, after: 480 },
          children: [new TextRun({ text: 'Tienda web de camisetas del Mundial 2026', size: 28, font: 'Arial', color: '555555' })],
        }),
        blank(), blank(),
        new Paragraph({
          alignment: AlignmentType.CENTER,
          spacing: { before: 0, after: 100 },
          children: [new TextRun({ text: 'Alumno: Dumar Bermudez', size: 26, font: 'Arial' })],
        }),
        new Paragraph({
          alignment: AlignmentType.CENTER,
          spacing: { before: 0, after: 100 },
          children: [new TextRun({ text: 'Tutor: Iker Jiménez', size: 26, font: 'Arial' })],
        }),
        new Paragraph({
          alignment: AlignmentType.CENTER,
          spacing: { before: 0, after: 100 },
          children: [new TextRun({ text: 'IES Enrique Tierno Galván', size: 26, font: 'Arial' })],
        }),
        new Paragraph({
          alignment: AlignmentType.CENTER,
          spacing: { before: 0, after: 100 },
          children: [new TextRun({ text: '18 de mayo de 2026', size: 26, font: 'Arial' })],
        }),
      ],
    },

    // ═══════════════════════════════════════
    // CUERPO CON CABECERA/PIE Y NUMERACIÓN
    // ═══════════════════════════════════════
    {
      properties: {
        page: {
          size: { width: 11906, height: 16838 },
          margin: { top: 1440, right: 1440, bottom: 1440, left: 1440 },
        },
      },
      headers: {
        default: new Header({
          children: [new Paragraph({
            border: { bottom: { style: BorderStyle.SINGLE, size: 6, color: '2E5FA3' } },
            children: [
              new TextRun({ text: 'Mundial Store — TFC 2.º DAW', font: 'Arial', size: 20, color: '555555' }),
            ],
          })],
        }),
      },
      footers: {
        default: new Footer({
          children: [new Paragraph({
            alignment: AlignmentType.CENTER,
            border: { top: { style: BorderStyle.SINGLE, size: 4, color: 'AAAAAA' } },
            children: [
              new TextRun({ text: 'Página ', font: 'Arial', size: 18, color: '888888' }),
              new TextRun({ children: [PageNumber.CURRENT], font: 'Arial', size: 18, color: '888888' }),
            ],
          })],
        }),
      },
      children: [
        // ── ÍNDICE ──────────────────────────────────────────
        new Paragraph({
          heading: HeadingLevel.HEADING_1,
          pageBreakBefore: false,
          children: [new TextRun({ text: 'Índice', bold: true, font: 'Arial', size: 32 })],
        }),
        new TableOfContents('', { hyperlink: true, headingStyleRange: '1-3' }),
        new Paragraph({ children: [new PageBreak()] }),

        // ── 1. INTRODUCCIÓN ─────────────────────────────────
        new Paragraph({ heading: HeadingLevel.HEADING_1, pageBreakBefore: false,
          children: [new TextRun({ text: '1. Introducción', bold: true, font: 'Arial', size: 32 })] }),

        new Paragraph({ heading: HeadingLevel.HEADING_2,
          children: [new TextRun({ text: '1.1. Descripción del proyecto', bold: true, font: 'Arial', size: 28 })] }),

        p('Mundial Store es una tienda web de camisetas de fútbol centrada en el Mundial 2026. La idea es sencilla: que cualquiera pueda entrar, ver lo que hay en el catálogo, añadir camisetas al carrito y tramitar una compra sin que el sitio le pida registrarse desde el primer momento. El registro o el login solo aparece cuando ya estás en el checkout y quieres confirmar el pedido, que es el momento en que tiene sentido pedirlo.'),
        p('Detrás de esa experiencia de usuario hay un backend en PHP puro con MySQL, sin framework. El sistema gestiona el catálogo con sus tallas y stock, el carrito en sesión, un flujo de checkout por pasos, y las cuentas de usuario con registro, login y recuperación de contraseña. También hay un panel de administración básico para gestionar productos.'),

        new Paragraph({ heading: HeadingLevel.HEADING_2,
          children: [new TextRun({ text: '1.2. Motivación', bold: true, font: 'Arial', size: 28 })] }),

        p('La elección del proyecto no es al azar. El Mundial 2026 estaba en el ambiente cuando arranqué a pensar en el TFC, y un e-commerce de camisetas me parecía el escenario perfecto para practicar con algo que se acerca bastante a un caso real: hay productos, tallas, stock, carrito, pago (simulado), emails… todo lo que tiene una tienda online de verdad.'),
        p('Además, quería trabajar sin apoyarme en un framework grande como Laravel. No porque Laravel sea malo, sino porque quería entender bien lo que pasa debajo: cómo funciona la sesión, cómo se gestiona la conexión a la base de datos con PDO, cómo se protegen los formularios… Todo eso queda mucho más claro cuando lo escribes tú mismo que cuando el framework lo hace por ti sin que tengas que pensar en ello.'),

        // ── 2. GESTIÓN Y ORGANIZACIÓN ───────────────────────
        new Paragraph({ heading: HeadingLevel.HEADING_1, pageBreakBefore: true,
          children: [new TextRun({ text: '2. Gestión y organización del proyecto', bold: true, font: 'Arial', size: 32 })] }),

        new Paragraph({ heading: HeadingLevel.HEADING_2,
          children: [new TextRun({ text: '2.1. Metodología', bold: true, font: 'Arial', size: 28 })] }),

        p('Para organizar el trabajo he seguido un modelo en cascada. Puede parecer un poco clásico, pero para este proyecto tenía sentido: los requisitos estaban bastante claros desde el principio (lo que tenía que hacer una tienda online ya lo sabía antes de escribir la primera línea de código), el equipo era yo solo, y el alcance estaba bien acotado.'),
        p('El modelo en cascada me ayudó a no saltarme pasos: primero analizar qué hacía falta, después diseñar cómo montarlo (estructura de carpetas, esquema de base de datos, flujo de navegación), luego implementar, y al final probar y documentar. En la práctica hubo algún retroceso puntual, sobre todo cuando en la implementación me di cuenta de que necesitaba ampliar la tabla de pedidos para guardar los datos de envío, pero en general el orden se mantuvo.'),

        new Paragraph({ heading: HeadingLevel.HEADING_2,
          children: [new TextRun({ text: '2.2. Planificación', bold: true, font: 'Arial', size: 28 })] }),

        p('La siguiente tabla recoge las fases del proyecto con la duración estimada y la real:'),
        blank(),

        makeTable(
          ['Fase', 'Duración estimada', 'Duración real', 'Observaciones'],
          [
            ['Análisis de requisitos', '1 semana', '1 semana', 'Sin desviación'],
            ['Diseño (BD + arquitectura)', '1 semana', '1 semana', 'Sin desviación'],
            ['Implementación', '6 semanas', '7 semanas', 'El checkout y el sistema de emails tomaron más de lo previsto'],
            ['Pruebas', '1 semana', '1 semana', 'Sin desviación'],
            ['Documentación', '1 semana', '1 semana', 'Sin desviación'],
            ['TOTAL', '10 semanas', '11 semanas', 'Desviación de 1 semana en implementación'],
          ],
          [2000, 1800, 1800, 3500]
        ),
        blank(),
        p('La mayor desviación fue en la fase de implementación. El flujo del checkout por pasos resultó más complejo de lo que había estimado, especialmente la parte de guardar el estado entre pasos en sesión y coordinar la transacción en base de datos al confirmar. El sistema de emails también dio trabajo: en Windows sin configuración SMTP, el mail() falla silenciosamente, y tuve que pensar cómo gestionar ese error sin que se perdiera el pedido ni pareciera que todo había fallado.'),

        new Paragraph({ heading: HeadingLevel.HEADING_2,
          children: [new TextRun({ text: '2.3. Ciclo de vida', bold: true, font: 'Arial', size: 28 })] }),

        p('El modelo en cascada que he seguido tiene estas fases, ejecutadas de forma secuencial:'),
        blank(),
        p('Análisis → Diseño → Implementación → Pruebas → Documentación'),
        blank(),
        p('Cada fase produce una salida que es la entrada de la siguiente. En el análisis, la lista de requisitos. En el diseño, el esquema de base de datos y la arquitectura de carpetas. En la implementación, el código. En las pruebas, el registro de resultados. Y en la documentación, esta memoria.'),
        p('Como ya he comentado, hubo un pequeño retroceso de la fase de implementación al diseño cuando necesité ampliar el esquema de la base de datos. En un proyecto con más personas o más largo, eso habría requerido un proceso de cambio de requisitos más formal, pero aquí lo resolví ajustando el esquema y creando un script de migración incremental para no perder los datos ya cargados.'),

        // ── 3. OBJETIVOS ────────────────────────────────────
        new Paragraph({ heading: HeadingLevel.HEADING_1, pageBreakBefore: true,
          children: [new TextRun({ text: '3. Objetivos', bold: true, font: 'Arial', size: 32 })] }),

        p('Cuando empecé a plantear el proyecto ya tenía bastante claro qué quería conseguir: una web donde la gente pudiera mirar camisetas de fútbol, buscar, meter cosas en el carrito sin tener que registrarse a la primera, y que el login apareciera solo cuando de verdad vas a pagar. También quería que el flujo se pareciera al diagrama que tenía de referencia, con checkout por pasos, pedido guardado en base de datos y un correo de resumen.'),

        new Paragraph({ heading: HeadingLevel.HEADING_2,
          children: [new TextRun({ text: '3.1. Objetivos generales', bold: true, font: 'Arial', size: 28 })] }),

        blank(),
        makeTable(
          ['Objetivo', 'Estado', 'Indicadores de consecución'],
          [
            ['OG1. Catálogo navegable con destacados, búsqueda y filtros', 'Alcanzado', 'Página de inicio con destacados; catálogo con búsqueda por texto y filtros por marca, modelo y talla'],
            ['OG2. Carrito para usuario invitado, autenticación solo al tramitar la compra', 'Alcanzado', 'Añadir al carrito sin sesión; redirección a login/registro en el checkout con parámetro next'],
            ['OG3. Checkout por pasos y pedido guardado en base de datos', 'Alcanzado', 'Flujo en tres pasos en sesión; tabla de pedidos con datos de envío y método de pago; pantalla de confirmación'],
            ['OG4. Notificación por correo sin bloquear la compra si el envío falla', 'Alcanzado', 'mail() tras registrar el pedido; mensaje diferenciado en confirmación si el correo no pudo enviarse'],
            ['OG5. Recuperación de contraseña con token de un solo uso', 'Alcanzado', 'Flujo olvidé contraseña → enlace → nuevo formulario; tabla password_resets con hash y caducidad'],
          ],
          [3000, 1200, 4900]
        ),
        blank(),

        new Paragraph({ heading: HeadingLevel.HEADING_2,
          children: [new TextRun({ text: '3.2. Objetivos específicos (técnicos)', bold: true, font: 'Arial', size: 28 })] }),

        blank(),
        makeTable(
          ['Objetivo', 'Estado', 'Notas'],
          [
            ['OE1. Separar presentación, lógica compartida y acceso a datos', 'Alcanzado', 'Scripts en public/, includes comunes, SQL y migraciones en sql/ y tools/'],
            ['OE2. Protección básica: sesiones seguras, CSRF, contraseñas hasheadas, rate limiting', 'Alcanzado', 'Tokens CSRF en formularios; bloqueo tras 5 intentos; password_hash(); httponly + samesite=Lax'],
            ['OE3. Despliegue y evolución del esquema sin perder datos', 'Alcanzado', 'schema.sql para instalación limpia; script de migración incremental para instalaciones existentes'],
          ],
          [3500, 1200, 4400]
        ),
        blank(),

        new Paragraph({ heading: HeadingLevel.HEADING_2,
          children: [new TextRun({ text: '3.3. Objetivos no alcanzados o parcialmente alcanzados', bold: true, font: 'Arial', size: 28 })] }),

        p('Para no vender humo, también dejo claro lo que no está o está a medias:'),
        new Paragraph({
          numbering: { reference: 'bullets', level: 0 },
          children: [new TextRun({ text: 'Integración con pasarela de pago real: no incluida en el alcance. El pago va simulado, porque el trabajo iba más por el flujo y la lógica que por enganchar con un banco.', font: 'Arial', size: 24 })],
          spacing: { before: 0, after: 100 },
        }),
        new Paragraph({
          numbering: { reference: 'bullets', level: 0 },
          children: [new TextRun({ text: 'Entrega de correo en todos los entornos: depende de la configuración del servidor. En Windows sin SMTP el mail() falla; el sistema lo contempla sin invalidar el pedido, pero el correo no llega.', font: 'Arial', size: 24 })],
          spacing: { before: 0, after: 160 },
        }),
        p('En conjunto, los objetivos funcionales y de arquitectura previstos para la versión entregada se consideran cumplidos, con esas salvedades.'),

        // ── 4. DESARROLLO ───────────────────────────────────
        new Paragraph({ heading: HeadingLevel.HEADING_1, pageBreakBefore: true,
          children: [new TextRun({ text: '4. Desarrollo de la aplicación', bold: true, font: 'Arial', size: 32 })] }),

        new Paragraph({ heading: HeadingLevel.HEADING_2,
          children: [new TextRun({ text: '4.1. Análisis — Requisitos', bold: true, font: 'Arial', size: 28 })] }),

        new Paragraph({ heading: HeadingLevel.HEADING_3,
          children: [new TextRun({ text: 'Requisitos funcionales', bold: true, font: 'Arial', size: 26 })] }),

        blank(),
        makeTable(
          ['Código', 'Descripción'],
          [
            ['RF01 — Catálogo', 'Visualizar productos activos, ficha de producto por identificador, stock por talla.'],
            ['RF02 — Búsqueda y filtrado', 'Texto libre y filtros por marca, modelo y talla con existencias disponibles.'],
            ['RF03 — Carrito', 'Altas, modificaciones de cantidad y eliminación de líneas respetando stock.'],
            ['RF04 — Checkout', 'Recogida de datos de envío, selección de método de pago, confirmación y registro del pedido.'],
            ['RF05 — Cuenta de usuario', 'Registro, inicio y cierre de sesión; recuperación de contraseña por email con token temporal.'],
            ['RF06 — Panel de admin', 'Gestión de productos (crear, editar, activar/desactivar) solo accesible para el rol administrador.'],
          ],
          [2000, 7100]
        ),
        blank(),

        new Paragraph({ heading: HeadingLevel.HEADING_3,
          children: [new TextRun({ text: 'Requisitos no funcionales', bold: true, font: 'Arial', size: 26 })] }),

        blank(),
        makeTable(
          ['Código', 'Descripción'],
          [
            ['RNF01 — Usabilidad', 'Navegación clara entre inicio, catálogo, producto, carrito y checkout; sin necesidad de cuenta hasta el pago.'],
            ['RNF02 — Mantenibilidad', 'Código PHP legible y separado por carpetas (public, includes, sql, tools).'],
            ['RNF03 — Integridad de datos', 'Transacciones en la creación del pedido; comprobación de stock antes de confirmar.'],
            ['RNF04 — Seguridad básica', 'CSRF en formularios mutables; contraseñas con password_hash(); rate limiting en login; sin exponer tokens en BD.'],
          ],
          [2000, 7100]
        ),
        blank(),

        new Paragraph({ heading: HeadingLevel.HEADING_3,
          children: [new TextRun({ text: 'Actores', bold: true, font: 'Arial', size: 26 })] }),

        p('Hay tres actores principales:'),
        new Paragraph({
          numbering: { reference: 'bullets', level: 0 },
          children: [new TextRun({ text: 'Visitante / cliente anónimo: navega, busca y añade al carrito sin necesidad de cuenta.', font: 'Arial', size: 24 })],
          spacing: { before: 0, after: 80 },
        }),
        new Paragraph({
          numbering: { reference: 'bullets', level: 0 },
          children: [new TextRun({ text: 'Cliente registrado: mismo flujo más tramitación de compra, consulta de pedidos y recuperación de contraseña.', font: 'Arial', size: 24 })],
          spacing: { before: 0, after: 80 },
        }),
        new Paragraph({
          numbering: { reference: 'bullets', level: 0 },
          children: [new TextRun({ text: 'Administrador: acceso al panel de gestión de productos; distinguido por el flag is_admin en la tabla de usuarios.', font: 'Arial', size: 24 })],
          spacing: { before: 0, after: 160 },
        }),

        new Paragraph({ heading: HeadingLevel.HEADING_2,
          children: [new TextRun({ text: '4.2. Diseño', bold: true, font: 'Arial', size: 28 })] }),

        new Paragraph({ heading: HeadingLevel.HEADING_3,
          children: [new TextRun({ text: '4.2.1. Arquitectura de la aplicación', bold: true, font: 'Arial', size: 26 })] }),

        p('No he usado un framework grande tipo Laravel; he ido a PHP "a pelo" pero intentando no mezclarlo todo en un solo archivo gigante. La idea es la típica de capas aunque sea sencilla: las páginas que se ven van en public/, lo compartido (arranque de sesión, conexión a la BD, funciones sueltas, cabecera y pie) en includes/, y el SQL de creación de tablas y scripts de instalación o migración en sql/ y tools/.'),
        p('Capa de presentación: ficheros en /public (*.php que generan HTML).'),
        p('Capa de aplicación: includes/bootstrap.php, includes/functions.php — sesión, carrito, CSRF, emails, redirecciones seguras.'),
        p('Capa de acceso a datos: PDO con MySQL/MariaDB, consultas preparadas.'),
        p('Esta organización prioriza la claridad para un proyecto acotado. Cada script bajo public/ actúa como punto de entrada HTTP; incluye cabecera y pie comunes. La conexión a la BD se hace a través de una única función get_pdo() tras la carga de configuración.'),

        new Paragraph({ heading: HeadingLevel.HEADING_3,
          children: [new TextRun({ text: '4.2.2. Diseño de la base de datos', bold: true, font: 'Arial', size: 26 })] }),

        p('El modelo de datos tiene seis tablas principales:'),
        blank(),
        makeTable(
          ['Tabla', 'Descripción'],
          [
            ['users', 'Identidad del cliente: email único, hash de contraseña, nombre, dirección guardada y flag is_admin.'],
            ['products', 'Catálogo: marca, modelo, slug, descripción, precio, imagen y flag de activo.'],
            ['product_stock', 'Stock por talla para cada producto. Clave compuesta (product_id, size).'],
            ['orders', 'Cabecera del pedido: usuario, total, estado, datos de envío completos y método de pago.'],
            ['order_items', 'Líneas del pedido: producto, talla, cantidad y precio unitario en el momento de la compra.'],
            ['password_resets', 'Tokens de recuperación de contraseña: hash del token, caducidad y marca de usado.'],
          ],
          [2000, 7100]
        ),
        blank(),
        p('Las claves foráneas y los tipos de columna se definieron para coherencia con InnoDB. El schema.sql permite instalación limpia desde cero; el script de migración incremental permite alinear instalaciones antiguas sin recrear la base.'),

        new Paragraph({ heading: HeadingLevel.HEADING_2,
          children: [new TextRun({ text: '4.3. Implementación', bold: true, font: 'Arial', size: 28 })] }),

        new Paragraph({ heading: HeadingLevel.HEADING_3,
          children: [new TextRun({ text: 'Herramientas y tecnologías', bold: true, font: 'Arial', size: 26 })] }),

        blank(),
        makeTable(
          ['Categoría', 'Tecnología', 'Uso'],
          [
            ['Lenguaje servidor', 'PHP 8.x', 'Lógica servidor, plantillas embebidas'],
            ['Base de datos', 'MySQL / MariaDB', 'Persistencia de datos'],
            ['Servidor de desarrollo', 'Servidor incorporado de PHP (php -S)', 'Pruebas en local'],
            ['Acceso a datos', 'PDO con consultas preparadas', 'Prevención de inyección SQL'],
            ['Cliente', 'HTML5, CSS3, JavaScript vanilla', 'Maquetación y validación ligera en formularios'],
            ['Diagrama de flujo', 'draw.io', 'Documentación del flujo de navegación'],
          ],
          [2000, 2800, 4300]
        ),
        blank(),

        new Paragraph({ heading: HeadingLevel.HEADING_3,
          children: [new TextRun({ text: 'Decisiones adoptadas', bold: true, font: 'Arial', size: 26 })] }),

        p('Algunas decisiones que tomé sobre la marcha y que creo que merece la pena explicar:'),
        new Paragraph({
          numbering: { reference: 'bullets', level: 0 },
          children: [new TextRun({ text: 'Carrito en sesión hasta confirmar el pedido. Me parecía más simple que crear tablas de carrito en base de datos para un proyecto de este tamaño. Si el usuario cierra el navegador, pierde el carrito, pero para un proyecto académico es una compensación aceptable.', font: 'Arial', size: 24 })],
          spacing: { before: 0, after: 80 },
        }),
        new Paragraph({
          numbering: { reference: 'bullets', level: 0 },
          children: [new TextRun({ text: 'Checkout guardado en sesión paso a paso. El borrador de datos de envío y pago se guarda en $_SESSION hasta que el usuario confirma, momento en que se escribe en la BD en una única transacción atómica.', font: 'Arial', size: 24 })],
          spacing: { before: 0, after: 80 },
        }),
        new Paragraph({
          numbering: { reference: 'bullets', level: 0 },
          children: [new TextRun({ text: 'Página de catálogo separada del inicio. En el inicio van los productos destacados; en el catálogo va la búsqueda y el listado completo. Mezclarlo todo en la misma vista resultaba confuso: cuando buscabas, los resultados quedaban muy abajo y parecía que no pasaba nada.', font: 'Arial', size: 24 })],
          spacing: { before: 0, after: 80 },
        }),
        new Paragraph({
          numbering: { reference: 'bullets', level: 0 },
          children: [new TextRun({ text: 'Rate limiting en login. Tras 5 intentos fallidos en un minuto, la cuenta queda bloqueada temporalmente. Los datos se guardan en sesión; sencillo pero suficiente para un proyecto de este nivel.', font: 'Arial', size: 24 })],
          spacing: { before: 0, after: 160 },
        }),

        new Paragraph({ heading: HeadingLevel.HEADING_3,
          children: [new TextRun({ text: 'Problemas encontrados y cómo se abordaron', bold: true, font: 'Arial', size: 26 })] }),

        p('Hubo tres problemas que me dieron más trabajo del que esperaba:'),
        new Paragraph({
          numbering: { reference: 'bullets', level: 0 },
          children: [new TextRun({ text: 'Correo en entornos de desarrollo. El mail() de PHP falla sin configuración SMTP, y en Windows eso es lo normal. Al principio me asustó porque pensaba que había roto el flujo; al final lo dejé capturando el error y avisando en pantalla sin revertir el pedido ya guardado.', font: 'Arial', size: 24 })],
          spacing: { before: 0, after: 80 },
        }),
        new Paragraph({
          numbering: { reference: 'bullets', level: 0 },
          children: [new TextRun({ text: 'Coherencia de esquema entre máquinas. Cuando añadí columnas a la tabla de pedidos, en un portátil que tenía la BD antigua me salía error. Por eso dejé documentado que hay que ejecutar el script de migración si la BD ya existía de antes.', font: 'Arial', size: 24 })],
          spacing: { before: 0, after: 80 },
        }),
        new Paragraph({
          numbering: { reference: 'bullets', level: 0 },
          children: [new TextRun({ text: 'Seguridad del parámetro next tras el login. Si el parámetro next no se valida, alguien podría manipular la URL para redirigir a un sitio externo. Lo fixé aceptando solo rutas internas relativas que empiecen por "/" y no por "//" (que en la URL es un protocolo relativo que apunta fuera).', font: 'Arial', size: 24 })],
          spacing: { before: 0, after: 160 },
        }),

        // ── 5. PRUEBAS ──────────────────────────────────────
        new Paragraph({ heading: HeadingLevel.HEADING_1, pageBreakBefore: true,
          children: [new TextRun({ text: '5. Realización de pruebas', bold: true, font: 'Arial', size: 32 })] }),

        new Paragraph({ heading: HeadingLevel.HEADING_2,
          children: [new TextRun({ text: '5.1. Descripción', bold: true, font: 'Arial', size: 28 })] }),

        p('Las pruebas las he hecho de forma manual, recorriendo los flujos que un usuario real podría seguir. No he montado un framework de tests automatizados (eso lo dejo como trabajo futuro), pero sí he comprobado sistemáticamente cada módulo: catálogo y búsqueda, carrito, checkout completo, autenticación, recuperación de contraseña y panel de admin.'),
        p('También hice algunas pruebas de seguridad básica: intentar inyectar SQL en los formularios de búsqueda y login, manipular el parámetro next, enviar formularios sin token CSRF. Y probé en Chrome y Firefox en Windows para asegurarme de que no había nada roto por diferencias entre navegadores.'),

        new Paragraph({ heading: HeadingLevel.HEADING_2,
          children: [new TextRun({ text: '5.2. Resultados', bold: true, font: 'Arial', size: 28 })] }),

        blank(),
        makeTable(
          ['Módulo', 'Caso de prueba', 'Resultado'],
          [
            ['Catálogo', 'Mostrar productos activos en inicio y catálogo', 'OK'],
            ['Catálogo', 'Buscar por texto y filtrar por marca, modelo y talla', 'OK'],
            ['Catálogo', 'Ver ficha de producto con tallas y stock disponible', 'OK'],
            ['Carrito', 'Añadir producto sin estar registrado', 'OK'],
            ['Carrito', 'Modificar cantidad y eliminar línea', 'OK'],
            ['Carrito', 'Comprobar que no se puede añadir más unidades de las que hay en stock', 'OK'],
            ['Checkout', 'Redirigir a login si se intenta tramitar sin sesión', 'OK'],
            ['Checkout', 'Completar los tres pasos (envío, pago, confirmación) y guardar pedido', 'OK'],
            ['Checkout', 'Correo de confirmación en entorno con SMTP', 'OK'],
            ['Checkout', 'Correo de confirmación en Windows sin SMTP (fallo esperado y documentado)', 'OK *'],
            ['Autenticación', 'Registro, login y logout', 'OK'],
            ['Autenticación', 'Bloqueo tras 5 intentos fallidos de login', 'OK'],
            ['Recuperación', 'Solicitar enlace, abrir en nuevo navegador, establecer nueva contraseña', 'OK'],
            ['Seguridad', 'Intentar inyección SQL en formularios con consultas preparadas', 'OK'],
            ['Seguridad', 'Enviar formulario sin token CSRF', 'OK'],
            ['Seguridad', 'Manipular parámetro next con URL externa (//evil.com)', 'OK'],
            ['Admin', 'Acceder al panel de admin con usuario sin permisos', 'OK'],
            ['Admin', 'Crear, editar y desactivar producto desde el panel', 'OK'],
          ],
          [2000, 4500, 2600]
        ),
        blank(),
        p('* El correo no llega en entorno sin SMTP, pero el pedido se guarda correctamente y en la pantalla de confirmación se informa al usuario. Este comportamiento es intencionado y está documentado.'),

        // ── 6. CONCLUSIONES ─────────────────────────────────
        new Paragraph({ heading: HeadingLevel.HEADING_1, pageBreakBefore: true,
          children: [new TextRun({ text: '6. Conclusiones', bold: true, font: 'Arial', size: 32 })] }),

        p('Mirando el proyecto desde el final, creo que he conseguido lo que me proponía. La tienda funciona: puedes entrar, buscar camisetas, añadir al carrito, registrarte justo cuando hace falta y completar una compra con todos sus pasos. El pedido queda guardado, los datos de envío también, y el sistema aguanta sin romperse aunque el correo no llegue.'),
        p('Lo que más me ha aportado ha sido trabajar sin framework. Hay momentos en los que Laravel te facilita mucho la vida, pero aquí he tenido que pensar yo cómo gestionar la sesión, cómo proteger los formularios, cómo hacer que el parámetro next no sea un vector de ataque… Eso lo entiendes de verdad cuando lo montas a mano.'),
        p('El checkout fue la parte más complicada. Coordinar tres pasos en sesión, asegurarte de que no se puede saltar ninguno, y al final escribir todo en la base de datos en una única transacción atómica, me llevó más tiempo del que había estimado. Pero también fue la parte en la que más aprendí.'),
        p('El tema del correo en Windows fue frustrante al principio, pero al final lo resolví de una forma limpia: capturar el error, avisar en pantalla y no revertir el pedido. En producción con un servidor con SMTP funciona; en local con Windows, no, y eso ya lo dejo documentado.'),
        p('En resumen: un proyecto que me ha servido para afianzar PHP, SQL, gestión de sesiones, seguridad básica y flujos de usuario reales. Con las limitaciones propias de un trabajo académico (sin pasarela de pago real, sin tests automatizados), pero con una base sólida sobre la que seguir construyendo.'),

        // ── 7. TRABAJO FUTURO ───────────────────────────────
        new Paragraph({ heading: HeadingLevel.HEADING_1, pageBreakBefore: true,
          children: [new TextRun({ text: '7. Trabajo futuro', bold: true, font: 'Arial', size: 32 })] }),

        p('Si continuara desarrollando el proyecto, estas serían las mejoras más interesantes:'),
        new Paragraph({
          numbering: { reference: 'bullets', level: 0 },
          children: [new TextRun({ text: 'Pasarela de pago real. Integrar Stripe o PayPal para que el flujo de pago sea completo y no simulado.', font: 'Arial', size: 24 })],
          spacing: { before: 0, after: 80 },
        }),
        new Paragraph({
          numbering: { reference: 'bullets', level: 0 },
          children: [new TextRun({ text: 'Sistema de valoraciones. Que los usuarios puedan puntuar y comentar los productos que han comprado, con moderación desde el panel de admin.', font: 'Arial', size: 24 })],
          spacing: { before: 0, after: 80 },
        }),
        new Paragraph({
          numbering: { reference: 'bullets', level: 0 },
          children: [new TextRun({ text: 'Panel de estadísticas. Visualización de ventas por período, productos más vendidos y stock bajo mínimos.', font: 'Arial', size: 24 })],
          spacing: { before: 0, after: 80 },
        }),
        new Paragraph({
          numbering: { reference: 'bullets', level: 0 },
          children: [new TextRun({ text: 'Tests automatizados con PHPUnit. Cubrir los flujos principales (añadir al carrito, checkout, autenticación) para detectar regresiones en futuros cambios.', font: 'Arial', size: 24 })],
          spacing: { before: 0, after: 80 },
        }),
        new Paragraph({
          numbering: { reference: 'bullets', level: 0 },
          children: [new TextRun({ text: 'Internacionalización. Soporte para múltiples idiomas, que encajaría bien dado el contexto del Mundial.', font: 'Arial', size: 24 })],
          spacing: { before: 0, after: 80 },
        }),
        new Paragraph({
          numbering: { reference: 'bullets', level: 0 },
          children: [new TextRun({ text: 'Versión móvil mejorada. El CSS ya es responsive, pero había cosas que podrían pulirse para una experiencia móvil más fluida.', font: 'Arial', size: 24 })],
          spacing: { before: 0, after: 160 },
        }),

        // ── 8. BIBLIOGRAFÍA ─────────────────────────────────
        new Paragraph({ heading: HeadingLevel.HEADING_1, pageBreakBefore: true,
          children: [new TextRun({ text: '8. Bibliografía', bold: true, font: 'Arial', size: 32 })] }),

        p('PHP Group. (2024). PHP 8 Manual. Recuperado de https://www.php.net/manual/es/'),
        p('Oracle Corporation. (2024). MySQL 8.0 Reference Manual. Recuperado de https://dev.mysql.com/doc/refman/8.0/en/'),
        p('Mozilla Developer Network. (2024). MDN Web Docs — HTML, CSS, JavaScript. Recuperado de https://developer.mozilla.org/es/'),
        p('OWASP Foundation. (2024). OWASP Top 10 2021. Recuperado de https://owasp.org/Top10/'),
        p('Stack Overflow Community. (2024). Stack Overflow. Recuperado de https://stackoverflow.com/'),
        p('draw.io (JGraph Ltd.). (2024). draw.io — Diagramming Software. Recuperado de https://app.diagrams.net/'),
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
