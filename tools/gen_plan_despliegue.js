const {
  Document, Packer, Paragraph, TextRun, Table, TableRow, TableCell,
  HeadingLevel, AlignmentType, BorderStyle, WidthType, ShadingType,
  VerticalAlign, TableOfContents, Header, Footer, PageNumber,
  LevelFormat, Bookmark, PageBreak
} = require("docx");
const fs = require("fs");
const path = require("path");

const BLUE_HEADER  = "1E3A5F";
const BLUE_LIGHT   = "D6E4F7";
const BLUE_MID     = "2E75B6";
const GREY_ROW     = "F2F5F9";
const WHITE        = "FFFFFF";
const TEXT_DARK    = "1A1A1A";

const border = { style: BorderStyle.SINGLE, size: 4, color: "BBCCE4" };
const borders = { top: border, bottom: border, left: border, right: border };
const noBorder = { style: BorderStyle.NONE, size: 0, color: "FFFFFF" };
const noBorders = { top: noBorder, bottom: noBorder, left: noBorder, right: noBorder };

function heading1(text, bookmarkId) {
  const children = bookmarkId
    ? [new Bookmark({ id: bookmarkId, children: [new TextRun({ text, bold: true, size: 32, color: WHITE, font: "Arial" })] })]
    : [new TextRun({ text, bold: true, size: 32, color: WHITE, font: "Arial" })];
  return new Paragraph({
    heading: HeadingLevel.HEADING_1,
    children,
    shading: { fill: BLUE_HEADER, type: ShadingType.CLEAR },
    spacing: { before: 360, after: 120 },
    indent: { left: 200 },
  });
}

function heading2(text) {
  return new Paragraph({
    heading: HeadingLevel.HEADING_2,
    children: [new TextRun({ text, bold: true, size: 26, color: BLUE_MID, font: "Arial" })],
    spacing: { before: 280, after: 80 },
    border: { bottom: { style: BorderStyle.SINGLE, size: 6, color: BLUE_MID, space: 1 } },
  });
}

function para(text, opts = {}) {
  return new Paragraph({
    spacing: { before: 60, after: 80 },
    children: [new TextRun({ text, size: 22, font: "Arial", color: TEXT_DARK, ...opts })],
  });
}

function bullet(text) {
  return new Paragraph({
    numbering: { reference: "bullets", level: 0 },
    spacing: { before: 40, after: 40 },
    children: [new TextRun({ text, size: 22, font: "Arial", color: TEXT_DARK })],
  });
}

function numbered(text) {
  return new Paragraph({
    numbering: { reference: "numbers", level: 0 },
    spacing: { before: 40, after: 40 },
    children: [new TextRun({ text, size: 22, font: "Arial", color: TEXT_DARK })],
  });
}

function spacer(before = 120) {
  return new Paragraph({ spacing: { before, after: 0 }, children: [new TextRun("")] });
}

function headerRow(cells, widths, totalWidth) {
  return new TableRow({
    tableHeader: true,
    children: cells.map((text, i) => new TableCell({
      borders,
      width: { size: widths[i], type: WidthType.DXA },
      shading: { fill: BLUE_HEADER, type: ShadingType.CLEAR },
      margins: { top: 100, bottom: 100, left: 150, right: 150 },
      verticalAlign: VerticalAlign.CENTER,
      children: [new Paragraph({
        alignment: AlignmentType.CENTER,
        children: [new TextRun({ text, bold: true, size: 22, color: WHITE, font: "Arial" })],
      })],
    })),
  });
}

function dataRow(cells, widths, shade = WHITE) {
  return new TableRow({
    children: cells.map((text, i) => new TableCell({
      borders,
      width: { size: widths[i], type: WidthType.DXA },
      shading: { fill: shade, type: ShadingType.CLEAR },
      margins: { top: 80, bottom: 80, left: 150, right: 150 },
      verticalAlign: VerticalAlign.CENTER,
      children: [new Paragraph({
        children: [new TextRun({ text, size: 21, font: "Arial", color: TEXT_DARK })],
      })],
    })),
  });
}

function makeTable(headers, rows, widths) {
  const total = widths.reduce((a, b) => a + b, 0);
  const tableRows = [headerRow(headers, widths, total)];
  rows.forEach((row, i) => {
    tableRows.push(dataRow(row, widths, i % 2 === 0 ? WHITE : GREY_ROW));
  });
  return new Table({
    width: { size: total, type: WidthType.DXA },
    columnWidths: widths,
    rows: tableRows,
  });
}

function stepBox(num, title) {
  return new Table({
    width: { size: 9026, type: WidthType.DXA },
    columnWidths: [9026],
    rows: [new TableRow({
      children: [new TableCell({
        borders: noBorders,
        width: { size: 9026, type: WidthType.DXA },
        shading: { fill: BLUE_LIGHT, type: ShadingType.CLEAR },
        margins: { top: 120, bottom: 120, left: 200, right: 200 },
        children: [new Paragraph({
          children: [
            new TextRun({ text: `PASO ${num}  `, bold: true, size: 24, color: BLUE_HEADER, font: "Arial" }),
            new TextRun({ text: `— ${title}`, size: 24, color: BLUE_HEADER, font: "Arial" }),
          ],
        })],
      })],
    })],
  });
}

// ─── DOCUMENT ────────────────────────────────────────────────────────────────
const doc = new Document({
  numbering: {
    config: [
      {
        reference: "bullets",
        levels: [{ level: 0, format: LevelFormat.BULLET, text: "•",
          alignment: AlignmentType.LEFT,
          style: { paragraph: { indent: { left: 640, hanging: 320 } } } }],
      },
      {
        reference: "numbers",
        levels: [{ level: 0, format: LevelFormat.DECIMAL, text: "%1.",
          alignment: AlignmentType.LEFT,
          style: { paragraph: { indent: { left: 640, hanging: 320 } } } }],
      },
    ],
  },
  styles: {
    default: { document: { run: { font: "Arial", size: 22, color: TEXT_DARK } } },
    paragraphStyles: [
      {
        id: "Heading1", name: "Heading 1", basedOn: "Normal", next: "Normal", quickFormat: true,
        run: { size: 32, bold: true, font: "Arial", color: WHITE },
        paragraph: { spacing: { before: 360, after: 120 }, outlineLevel: 0,
          shading: { fill: BLUE_HEADER, type: ShadingType.CLEAR } },
      },
      {
        id: "Heading2", name: "Heading 2", basedOn: "Normal", next: "Normal", quickFormat: true,
        run: { size: 26, bold: true, font: "Arial", color: BLUE_MID },
        paragraph: { spacing: { before: 280, after: 80 }, outlineLevel: 1 },
      },
    ],
  },
  sections: [{
    properties: {
      page: {
        size: { width: 11906, height: 16838 },
        margin: { top: 1200, right: 1200, bottom: 1200, left: 1200 },
      },
    },
    headers: {
      default: new Header({
        children: [new Paragraph({
          border: { bottom: { style: BorderStyle.SINGLE, size: 6, color: BLUE_MID, space: 1 } },
          spacing: { after: 120 },
          children: [
            new TextRun({ text: "Plan de Despliegue ", bold: true, size: 20, color: BLUE_HEADER, font: "Arial" }),
            new TextRun({ text: "— Vercel + Neon", size: 20, color: BLUE_MID, font: "Arial" }),
            new TextRun({ text: "     Mundial Store TFG", size: 18, color: "888888", font: "Arial" }),
          ],
        })],
      }),
    },
    footers: {
      default: new Footer({
        children: [new Paragraph({
          border: { top: { style: BorderStyle.SINGLE, size: 6, color: BLUE_MID, space: 1 } },
          alignment: AlignmentType.RIGHT,
          spacing: { before: 80 },
          children: [
            new TextRun({ text: "Pag. ", size: 18, color: "888888", font: "Arial" }),
            new TextRun({ children: [PageNumber.CURRENT], size: 18, color: "888888", font: "Arial" }),
          ],
        })],
      }),
    },
    children: [

      // ── PORTADA ──────────────────────────────────────────────────────────
      new Paragraph({
        alignment: AlignmentType.CENTER,
        spacing: { before: 1800, after: 200 },
        children: [new TextRun({ text: "MUNDIAL STORE", bold: true, size: 52, color: BLUE_HEADER, font: "Arial" })],
      }),
      new Paragraph({
        alignment: AlignmentType.CENTER,
        spacing: { before: 0, after: 160 },
        children: [new TextRun({ text: "Plan de Despliegue en Produccion", bold: true, size: 36, color: BLUE_MID, font: "Arial" })],
      }),
      new Paragraph({
        alignment: AlignmentType.CENTER,
        spacing: { before: 0, after: 600 },
        children: [new TextRun({ text: "Vercel + Neon — Acceso desde cualquier dispositivo", size: 26, color: "555555", font: "Arial" })],
      }),
      new Paragraph({
        alignment: AlignmentType.CENTER,
        spacing: { before: 0, after: 1600 },
        border: {
          bottom: { style: BorderStyle.SINGLE, size: 8, color: BLUE_MID, space: 1 },
          top:    { style: BorderStyle.SINGLE, size: 8, color: BLUE_MID, space: 1 },
        },
        children: [new TextRun({ text: "Trabajo de Fin de Grado  |  2025-2026", size: 22, color: "666666", font: "Arial" })],
      }),
      new Paragraph({ children: [new PageBreak()] }),

      // ── ÍNDICE ───────────────────────────────────────────────────────────
      new TableOfContents("Indice de contenidos", {
        hyperlink: true,
        headingStyleRange: "1-2",
        stylesWithLevels: [],
      }),
      new Paragraph({ children: [new PageBreak()] }),

      // ── 1. INTRODUCCIÓN ──────────────────────────────────────────────────
      heading1("1. Introduccion", "intro"),
      spacer(80),
      para("Este documento describe el plan completo para llevar la tienda web Mundial Store " +
           "— actualmente funcionando solo en local con PHP + MySQL/XAMPP — a produccion en la nube, " +
           "de forma que cualquier persona pueda acceder desde cualquier dispositivo: movil, tablet u ordenador."),
      spacer(60),
      para("Se utilizaran dos servicios gratuitos:"),
      bullet("Vercel: plataforma de hosting que desplegara la aplicacion PHP en la nube. " +
             "Tiene capa gratuita ilimitada para proyectos personales."),
      bullet("Neon: base de datos PostgreSQL serverless en la nube. " +
             "Tiene capa gratuita de 0,5 GB, mas que suficiente para un TFG."),

      spacer(200),

      // ── 2. STACK ─────────────────────────────────────────────────────────
      heading1("2. Stack actual vs Stack en produccion", "stack"),
      spacer(80),
      para("La siguiente tabla resume los cambios de infraestructura:"),
      spacer(80),
      makeTable(
        ["Pieza", "Local (ahora)", "Cloud (despues)"],
        [
          ["Servidor web",    "Apache / XAMPP",        "Vercel (PHP runtime)"],
          ["Base de datos",   "MySQL local",            "Neon (PostgreSQL)"],
          ["Sesiones PHP",    "Fichero en disco",       "PostgreSQL (tabla sessions)"],
          ["Imagenes",        "/public/images/",        "Vercel (estaticos, sin cambios)"],
          ["Email SMTP",      "Brevo (configurado)",    "Brevo (sin cambios)"],
        ],
        [3000, 3013, 3013]
      ),

      spacer(200),

      // ── 3. PASOS ─────────────────────────────────────────────────────────
      heading1("3. Pasos del plan", "pasos"),
      spacer(80),

      // PASO 1
      stepBox(1, "Crear la base de datos en Neon"),
      spacer(80),
      numbered("Crear una cuenta gratuita en neon.tech"),
      numbered("Crear un nuevo proyecto llamado mundial-store"),
      numbered("Convertir el archivo sql/schema.sql (MySQL) a sintaxis PostgreSQL. Cambios necesarios:"),
      spacer(40),
      makeTable(
        ["Sintaxis MySQL", "Sintaxis PostgreSQL"],
        [
          ["AUTO_INCREMENT",          "GENERATED ALWAYS AS IDENTITY"],
          ["Backticks (`nombre`)",    "Sin comillas (o \"comillas dobles\")"],
          ["TINYINT(1)",              "BOOLEAN"],
          ["LONGTEXT",                "TEXT"],
          ["DATETIME",                "TIMESTAMP"],
        ],
        [4513, 4513]
      ),
      spacer(60),
      numbered("Ejecutar el nuevo esquema en el panel SQL de Neon"),
      numbered("Ejecutar el seed.sql adaptado (datos de ejemplo: 16 camisetas)"),
      numbered("Copiar la connection string que Neon proporciona. Formato: postgresql://usuario:contrasena@host/basededatos?sslmode=require"),
      spacer(40),
      para("Archivo nuevo que se creara: sql/schema_postgres.sql", { italics: true, color: "555555" }),

      spacer(180),

      // PASO 2
      stepBox(2, "Adaptar la conexion PHP para usar PostgreSQL"),
      spacer(80),
      para("El archivo config/database.php actualmente se conecta a MySQL. Hay que cambiarlo " +
           "para que se conecte a PostgreSQL (Neon) leyendo las credenciales desde variables de entorno " +
           "en lugar de desde config.local.php."),
      spacer(60),
      para("Cambios concretos:"),
      bullet("El DSN cambia de \"mysql:host=...\" a \"pgsql:host=...\""),
      bullet("Las credenciales se leeran con getenv() o $_ENV en lugar de constantes PHP"),
      spacer(60),
      para("Tambien se actualizara config/config.example.php para documentar la variable DATABASE_URL y las demas variables de entorno."),
      spacer(40),
      para("Archivos a modificar: config/database.php, config/config.example.php", { italics: true, color: "555555" }),

      spacer(180),

      // PASO 3
      stepBox(3, "Solucionar las sesiones en entorno serverless"),
      spacer(80),
      para("Este es el paso mas importante y delicado. Vercel es una plataforma serverless: " +
           "cada peticion HTTP puede ejecutarse en una instancia de servidor diferente. " +
           "Esto significa que las sesiones PHP guardadas en ficheros del disco NO persisten entre peticiones."),
      spacer(60),
      para("Solucion: guardar las sesiones en la base de datos PostgreSQL.", { bold: true }),
      spacer(60),
      para("Como funciona:"),
      numbered("Se anade una tabla sessions al esquema con columnas: id (clave primaria), " +
               "data (datos de sesion serializados), last_activity (timestamp Unix)"),
      numbered("En includes/bootstrap.php se registra un handler de sesion personalizado con " +
               "session_set_save_handler(). Este handler lee y escribe en PostgreSQL en lugar de en disco."),
      spacer(60),
      para("Con esto, el carrito, el login y cualquier dato de sesion funcionaran correctamente " +
           "aunque cada peticion caiga en un servidor diferente."),
      spacer(40),
      para("Archivos a modificar: sql/schema_postgres.sql (anadir tabla sessions), includes/bootstrap.php", { italics: true, color: "555555" }),

      spacer(180),

      // PASO 4
      stepBox(4, "Crear el archivo de configuracion de Vercel (vercel.json)"),
      spacer(80),
      para("Vercel necesita un archivo vercel.json en la raiz del proyecto para saber como " +
           "enrutar las peticiones y que runtime usar para PHP."),
      spacer(60),
      para("El archivo vercel.json incluira:"),
      bullet("Runtime: vercel-php@0.6.0 (soporte de la comunidad para PHP en Vercel)"),
      bullet("Rutas equivalentes a las actuales de .htaccess de Apache, en formato JSON"),
      bullet("Cobertura de rutas: /api/*, /admin/*, /css/*, /js/*, /images/*, archivos .php sueltos y la raiz"),
      spacer(40),
      para("Archivo nuevo que se creara: vercel.json (en la raiz del proyecto)", { italics: true, color: "555555" }),

      spacer(180),

      // PASO 5
      stepBox(5, "Configurar las variables de entorno en Vercel"),
      spacer(80),
      para("Todas las credenciales y configuraciones sensibles se guardan como variables de entorno " +
           "en el panel de Vercel (Settings > Environment Variables), en lugar de en archivos locales."),
      spacer(80),
      makeTable(
        ["Variable de entorno", "Valor"],
        [
          ["DATABASE_URL", "Connection string de Neon (copiada en el Paso 1)"],
          ["API_KEY",       "Clave actual de la aplicacion"],
          ["SMTP_HOST",     "smtp-relay.brevo.com"],
          ["SMTP_PORT",     "587"],
          ["SMTP_USER",     "Usuario de Brevo"],
          ["SMTP_PASS",     "Contrasena de Brevo"],
          ["SMTP_FROM",     "pandaosobear@gmail.com"],
          ["ADMIN_EMAIL",   "pandaosobear@gmail.com"],
        ],
        [3800, 5226]
      ),

      spacer(180),

      // PASO 6
      stepBox(6, "Revisar y corregir consultas SQL incompatibles con PostgreSQL"),
      spacer(80),
      para("PostgreSQL y MySQL tienen pequenas diferencias de sintaxis. " +
           "Hay que revisar todos los archivos PHP con consultas SQL y corregir:"),
      spacer(60),
      makeTable(
        ["Sintaxis MySQL (a corregir)", "Equivalente PostgreSQL"],
        [
          ["Backticks en nombres",          "Eliminarlos (PostgreSQL no los acepta)"],
          ["LIMIT x, y",                    "LIMIT y OFFSET x"],
          ["INSERT IGNORE",                 "INSERT ... ON CONFLICT DO NOTHING"],
          ["LAST_INSERT_ID()",              "LASTVAL() o PDO lastInsertId()"],
          ["DATE_FORMAT(fecha, formato)",   "TO_CHAR(fecha, formato) de PostgreSQL"],
        ],
        [4513, 4513]
      ),
      spacer(60),
      para("Archivos a revisar: includes/functions.php, public/*.php, public/admin/*.php, public/api/*.php", { italics: true, color: "555555" }),

      spacer(180),

      // PASO 7
      stepBox(7, "Desplegar en Vercel"),
      spacer(80),
      para("Dos opciones para desplegar:"),
      spacer(40),
      para("Opcion A (recomendada): conectar el repositorio Git a Vercel"),
      bullet("Subir el proyecto a GitHub o GitLab"),
      bullet("En vercel.com: New Project > importar repositorio"),
      bullet("Cada push al repositorio despliega automaticamente"),
      spacer(80),
      para("Opcion B: CLI de Vercel desde la terminal"),
      bullet("Instalar: npm install -g vercel"),
      bullet("Ejecutar en la carpeta del proyecto: vercel deploy"),
      spacer(80),
      para("Resultado: Vercel asigna una URL publica del tipo mundial-store.vercel.app. " +
           "Esa URL es accesible desde cualquier dispositivo en el mundo.", { bold: true }),

      spacer(180),

      // PASO 8
      stepBox(8, "Migrar datos existentes de MySQL a Neon (opcional)"),
      spacer(80),
      para("Si en la base de datos local ya hay productos, usuarios u ordenes reales que se quieren conservar:"),
      numbered("Exportar con mysqldump desde XAMPP"),
      numbered("Convertir el volcado a formato PostgreSQL (herramienta pgloader o manualmente)"),
      numbered("Importar en Neon via su panel SQL"),

      spacer(200),

      // ── 4. CHECKLIST ─────────────────────────────────────────────────────
      heading1("4. Checklist de verificacion final", "checklist"),
      spacer(80),
      para("Antes de dar la web por operativa, verificar uno a uno estos puntos:"),
      spacer(80),
      makeTable(
        ["Que verificar", "Como comprobarlo"],
        [
          ["Base de datos conectada",      "Abrir /public/catalog.php — deben aparecer los productos"],
          ["Login y registro",             "Registrar usuario nuevo y hacer login con el"],
          ["Carrito persiste",             "Anadir producto, navegar, volver al carrito — sigue ahi"],
          ["Proceso de compra completo",   "Completar checkout con pago contrareembolso"],
          ["Panel de administracion",      "Entrar en /public/admin/ con cuenta administrador"],
          ["Email de confirmacion",        "Recibir correo tras completar un pedido"],
          ["Acceso desde movil",           "Abrir la URL en el movil — el diseno es responsive"],
        ],
        [3800, 5226]
      ),

      spacer(200),

      // ── 5. RIESGOS ───────────────────────────────────────────────────────
      heading1("5. Riesgos y notas importantes", "riesgos"),
      spacer(80),

      heading2("Sesiones serverless (critico)"),
      para("Es el punto mas delicado de toda la migracion. Si el handler de base de datos falla, " +
           "ningun usuario podra loguearse ni mantener el carrito. Hay que probarlo exhaustivamente " +
           "antes de dar la web por operativa."),

      spacer(100),
      heading2("Imagenes de productos"),
      para("Estan en formato SVG dentro del repositorio. Vercel las sirve como archivos estaticos " +
           "sin ningun cambio necesario."),

      spacer(100),
      heading2("Limites del plan gratuito"),
      makeTable(
        ["Servicio", "Limite gratuito", "Coste si se supera"],
        [
          ["Neon",   "0,5 GB almacenamiento / 190h compute al mes", "Desde $19/mes"],
          ["Vercel", "Ilimitado para proyectos personales sin equipo", "Solo si se usa equipo"],
        ],
        [2500, 4263, 2263]
      ),

      spacer(100),
      heading2("Dominio personalizado"),
      para("Vercel asigna un dominio gratuito (*.vercel.app). Si en el futuro se quiere un dominio " +
           "propio (ej. mundialstore.com), se puede conectar desde el panel de Vercel. " +
           "Coste aproximado: 10-15 EUR/ano."),

    ],
  }],
});

const outPath = path.join(__dirname, "..", "Plan_Despliegue_Vercel_Neon.docx");
Packer.toBuffer(doc).then(buf => {
  fs.writeFileSync(outPath, buf);
  console.log("OK: " + outPath);
}).catch(e => { console.error(e); process.exit(1); });
