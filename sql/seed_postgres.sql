-- Datos de ejemplo — Mundial Store (PostgreSQL)

INSERT INTO products (brand, model, slug, description, price, image_path, active) VALUES
('Europa', 'España',    'europa-espana',    'La Roja en su versión más icónica. Tejido técnico transpirable, escudo bordado en el pecho.',     89.99, '/images/europa-espana.svg',    TRUE),
('Europa', 'Francia',   'europa-francia',   'Les Bleus en azul profundo. Corte ajustado con escudo del gallo galo bordado.',                    89.99, '/images/europa-francia.svg',   TRUE),
('Europa', 'Alemania',  'europa-alemania',  'La Mannschaft en blanco clásico con detalles en negro y escudo del águila federal.',               89.99, '/images/europa-alemania.svg',  TRUE),
('Europa', 'Inglaterra','europa-inglaterra','Los Three Lions en blanco puro. Escudo con tres leones y cuello redondo clásico.',                 89.99, '/images/europa-inglaterra.svg',TRUE),
('Sudamérica', 'Brasil',    'sudamerica-brasil',    'La Canarinha. Verde y amarillo, el conjunto más laureado del fútbol mundial.',                  89.99, '/images/sudamerica-brasil.svg',    TRUE),
('Sudamérica', 'Argentina', 'sudamerica-argentina', 'La Albiceleste campeona del mundo en Qatar 2022. Rayas celestes y blancas eternas.',           89.99, '/images/sudamerica-argentina.svg', TRUE),
('Sudamérica', 'Uruguay',   'sudamerica-uruguay',   'La Celeste, dos veces campeona del mundo. Azul cielo con escudo de las cuatro estrellas.',     84.99, '/images/sudamerica-uruguay.svg',   TRUE),
('Sudamérica', 'Colombia',  'sudamerica-colombia',  'Los Cafeteros en amarillo intenso con escudo tricolor bordado en el pecho.',                   84.99, '/images/sudamerica-colombia.svg',  TRUE),
('África', 'Marruecos',       'africa-marruecos',       'Los Leones del Atlas, semifinalistas de Qatar 2022. Rojo intenso con estrella verde.',    79.99, '/images/africa-marruecos.svg',       TRUE),
('África', 'Nigeria',         'africa-nigeria',         'Las Super Águilas en verde vibrante con diseño geométrico inspirado en el arte ibo.',     79.99, '/images/africa-nigeria.svg',         TRUE),
('África', 'Senegal',         'africa-senegal',         'Los Leones de Teranga, campeones de África. Blanco y verde con estrellas doradas.',       79.99, '/images/africa-senegal.svg',         TRUE),
('África', 'Costa de Marfil', 'africa-costa-de-marfil', 'Los Elefantes en naranja llama. Uno de los colores más reconocibles del fútbol africano.',79.99, '/images/africa-costa-de-marfil.svg', TRUE),
('Asia', 'Japón',        'asia-japon',        'Los Samurai Blue, revelación de Qatar 2022. Azul marino con escudo del crisantemo.',          84.99, '/images/asia-japon.svg',        TRUE),
('Asia', 'Corea del Sur','asia-corea-del-sur','Los Guerreros Taeguk en rojo pasión con detalles en azul de la bandera nacional.',           84.99, '/images/asia-corea-del-sur.svg',TRUE),
('Asia', 'Arabia Saudí', 'asia-arabia-saudi', 'Las Águilas Verdes, sorpresa del Mundial con su victoria ante Argentina en Qatar 2022.',     79.99, '/images/asia-arabia-saudi.svg', TRUE),
('Asia', 'Australia',    'asia-australia',    'Los Socceroos en dorado y verde. Diseño moderno con escudo del canguro y la estrella federal.',79.99, '/images/asia-australia.svg',    TRUE);

INSERT INTO product_stock (product_id, size, quantity) VALUES
(1,'S',4),(1,'M',8),(1,'L',5),
(2,'S',5),(2,'M',7),(2,'L',4),
(3,'S',3),(3,'M',6),(3,'L',5),
(4,'S',4),(4,'M',9),(4,'L',6),
(5,'S',6),(5,'M',10),(5,'L',7),
(6,'S',5),(6,'M',9),(6,'L',6),
(7,'S',3),(7,'M',5),(7,'L',4),
(8,'S',4),(8,'M',6),(8,'L',3),
(9,'S',4),(9,'M',7),(9,'L',5),
(10,'S',3),(10,'M',6),(10,'L',4),
(11,'S',5),(11,'M',8),(11,'L',5),
(12,'S',3),(12,'M',5),(12,'L',3),
(13,'S',4),(13,'M',7),(13,'L',5),
(14,'S',4),(14,'M',6),(14,'L',4),
(15,'S',3),(15,'M',5),(15,'L',3),
(16,'S',4),(16,'M',6),(16,'L',4);
