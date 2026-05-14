<?php
declare(strict_types=1);

/**
 * Genera MEMORIA_Apartados_3_y_4.docx (OOXML mínimo) sin dependencias externas.
 */
$outPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'MEMORIA_Apartados_3_y_4.docx';

$nsW = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';

$paragraphs = [
    '3. OBJETIVOS',
    '',
    'Cuando empecé a plantear el proyecto de la tienda (Mundial Store), en la introducción ya dejé más o menos claro qué quería conseguir: una web donde la gente pudiera mirar camisetas de fútbol, buscar, meter cosas en el carrito sin tener que registrarse a la primera, y que el login apareciera solo cuando de verdad vas a pagar. También quería que el flujo se pareciera al diagrama que teníamos de referencia, con checkout por pasos, pedido guardado en base de datos y un correo de resumen (aunque eso último en local a veces da guerra).',
    '',
    'Pues bien, mirando lo que hay al final del desarrollo, creo que la mayoría de esas cosas las he podido sacar adelante. El catálogo funciona: en el inicio salen destacados y la búsqueda te lleva al listado aparte, con filtros por marca, modelo y talla. El carrito lo puedes usar sin cuenta hasta que llegas al checkout, y ahí te pide iniciar sesión o registrarte, volviendo al checkout con el parámetro next para no perder el hilo.',
    '',
    'El checkout lo monté por pasos (envío, forma de pago y confirmación), y el pedido se guarda con los datos de envío y el método de pago en la tabla de pedidos. Después de confirmar intento mandar un mail con el resumen; si falla (por ejemplo en Windows sin SMTP configurado), el pedido igual se queda guardado y en la pantalla de confirmación lo dejo dicho para que no parezca que se ha perdido nada.',
    '',
    'También metí lo de recuperar contraseña con enlace por email y token que caduca, guardando el hash en base de datos en vez del token tal cual, que era una de las cosas que me pedían a nivel de seguridad básica.',
    '',
    'Lo que sí es verdad es que no he integrado un TPV real ni nada de eso: el pago va simulado, porque el trabajo iba más por el flujo y la lógica que por enganchar con un banco. Y el tema del correo depende mucho del servidor donde lo montes; en mi máquina de pruebas a veces no llegaba ningún mail y tuve que documentar que eso es normal si no hay SMTP.',
    '',
    'En resumen: los objetivos que me había marcado para esta versión los veo cumplidos, con esas limitaciones que ya comento para no vender humo.',
    '',
    '4. DESARROLLO DE LA APLICACIÓN',
    '',
    '4.1. Análisis y requisitos',
    '',
    'Antes de tirarme a programar, fui apuntando lo que tenía que hacer la web casi como una lista de la compra. Por un lado lo funcional: ver productos y tallas, buscar y filtrar, carrito, tramitar pedido con datos de envío, guardar pedido, usuarios con registro/login y recuperación de contraseña. Por otro lo no tan visible pero importante: que los formularios importantes lleven protección CSRF, que las contraseñas vayan hasheadas, y que si algo falla en el mail no se rompa todo el proceso de compra.',
    '',
    'Los actores son básicamente el visitante (puede comprar hasta el punto del login), el usuario registrado, y el sistema con lo de emails y base de datos.',
    '',
    '4.2. Diseño',
    '',
    'No he usado un framework grande tipo Laravel; he ido a PHP “a pelo” pero intentando no mezclarlo todo en un solo archivo gigante. La idea es la típica de capas aunque sea sencilla: las páginas que se ven van en public/, lo compartido (arranque de sesión, conexión a la BD, funciones sueltas, cabecera y pie) en includes/, y el SQL de creación de tablas y scripts de instalación o migración aparte en sql/ y tools/. Así cuando tengo que cambiar algo de sesión o del carrito no ando buscando en diez sitios distintos.',
    '',
    'La base de datos la fui montando con tablas de usuarios, productos, stock por talla, pedidos y líneas de pedido. Más adelante tuve que ampliar la tabla de pedidos para meter nombre y dirección de envío, código postal, ciudad, país y el método de pago, porque al principio el checkout era más simple. Para el reset de contraseña añadí una tabla de tokens con caducidad y marca de usado.',
    '',
    '(En la memoria en PDF seguramente pegue también el diagrama entidad-relación o una captura del esquema, que queda más claro que solo describirlo por escrito.)',
    '',
    '4.3. Implementación',
    '',
    'He trabajado con PHP 8 y MySQL (en local me vale MariaDB también). Para probar en el ordenador uso el servidor de desarrollo que trae PHP con php -S apuntando a la carpeta public. El front es HTML y CSS con un poco de JavaScript suelto para cosas como validar que las contraseñas coinciden o limpiar espacios en los filtros antes de enviar.',
    '',
    'Decisiones que tomé sobre la marcha: el carrito lo llevo en sesión hasta que el pedido se confirma, porque me parecía más simple que crear tablas de carrito en base de datos para un proyecto de este tamaño. El checkout lo fui guardando en sesión paso a paso para no escribir en la BD hasta tener algo coherente. También separé la página de inicio (destacados) de la página de catálogo/búsqueda porque me resultaba confuso mezclarlo todo en la misma vista: cuando buscabas, los resultados quedaban muy abajo y parecía que no pasaba nada.',
    '',
    'Problemas que me encontré: lo del mail() en Windows sin configuración SMTP, que al principio me asustó porque pensaba que había roto el flujo; al final lo dejé capturando el fallo y avisando en pantalla. Otro lío fue cuando cambié columnas de la base de datos y en un portátil viejo me salía error porque no había pasado el script de migración; por eso dejé documentado que hay que ejecutar el migrate si la BD ya existía de antes.',
    '',
    'También tuve cuidado con el parámetro next después del login para que solo acepte rutas internas y no te redirijan a sitios raros poniendo // en la URL.',
    '',
    'Y poco más a nivel técnico; el grueso del trabajo ha sido ir encajando el flujo con lo que pedía el diagrama y que la tienda se entienda sin tener que leer código.',
];

function xmlText(string $s): string
{
    return htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}

$bodyXml = '';
foreach ($paragraphs as $line) {
    if ($line === '') {
        $bodyXml .= '<w:p/>';
        continue;
    }
    $bodyXml .= '<w:p><w:r><w:t xml:space="preserve">' . xmlText($line) . '</w:t></w:r></w:p>';
}

$documentXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    . '<w:document xmlns:w="' . $nsW . '">'
    . '<w:body>'
    . $bodyXml
    . '<w:sectPr><w:pgSz w:w="11906" w:h="16838"/><w:pgMar w:top="1440" w:right="1440" w:bottom="1440" w:left="1440" w:header="708" w:footer="708" w:gutter="0"/></w:sectPr>'
    . '</w:body></w:document>';

$contentTypes = '<?xml version="1.0" encoding="UTF-8"?>'
    . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
    . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
    . '<Default Extension="xml" ContentType="application/xml"/>'
    . '<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>'
    . '</Types>';

$rels = '<?xml version="1.0" encoding="UTF-8"?>'
    . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
    . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>'
    . '</Relationships>';

$wordRels = '<?xml version="1.0" encoding="UTF-8"?>'
    . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"/>';

if (!class_exists('ZipArchive')) {
    fwrite(STDERR, "ZipArchive no disponible.\n");
    exit(1);
}

$zip = new ZipArchive();
if ($zip->open($outPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    fwrite(STDERR, "No se pudo crear: $outPath\n");
    exit(1);
}

$zip->addFromString('[Content_Types].xml', $contentTypes);
$zip->addFromString('_rels/.rels', $rels);
$zip->addFromString('word/_rels/document.xml.rels', $wordRels);
$zip->addFromString('word/document.xml', $documentXml);
$zip->close();

echo "Creado: $outPath\n";
