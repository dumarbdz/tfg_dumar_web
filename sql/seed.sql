SET NAMES utf8mb4;

-- Camisetas de fútbol — Mundial 2026 (tallas S, M, L)

-- EUROPA
INSERT INTO products (brand, model, slug, description, price, image_path, active) VALUES
('Europa', 'España',    'europa-espana',    'La Roja en su versión más icónica. Tejido técnico transpirable, escudo bordado en el pecho.',     89.99, '/images/europa-espana.svg',    1),
('Europa', 'Francia',   'europa-francia',   'Les Bleus en azul profundo. Corte ajustado con escudo del gallo galo bordado.',                    89.99, '/images/europa-francia.svg',   1),
('Europa', 'Alemania',  'europa-alemania',  'La Mannschaft en blanco clásico con detalles en negro y escudo del águila federal.',               89.99, '/images/europa-alemania.svg',  1),
('Europa', 'Inglaterra','europa-inglaterra','Los Three Lions en blanco puro. Escudo con tres leones y cuello redondo clásico.',                 89.99, '/images/europa-inglaterra.svg',1);

-- SUDAMÉRICA
INSERT INTO products (brand, model, slug, description, price, image_path, active) VALUES
('Sudamérica', 'Brasil',    'sudamerica-brasil',    'La Canarinha. Verde y amarillo, el conjunto más laureado del fútbol mundial.',                  89.99, '/images/sudamerica-brasil.svg',    1),
('Sudamérica', 'Argentina', 'sudamerica-argentina', 'La Albiceleste campeona del mundo en Qatar 2022. Rayas celestes y blancas eternas.',           89.99, '/images/sudamerica-argentina.svg', 1),
('Sudamérica', 'Uruguay',   'sudamerica-uruguay',   'La Celeste, dos veces campeona del mundo. Azul cielo con escudo de las cuatro estrellas.',     84.99, '/images/sudamerica-uruguay.svg',   1),
('Sudamérica', 'Colombia',  'sudamerica-colombia',  'Los Cafeteros en amarillo intenso con escudo tricolor bordado en el pecho.',                   84.99, '/images/sudamerica-colombia.svg',  1);

-- ÁFRICA
INSERT INTO products (brand, model, slug, description, price, image_path, active) VALUES
('África', 'Marruecos',       'africa-marruecos',       'Los Leones del Atlas, semifinalistas de Qatar 2022. Rojo intenso con estrella verde.',    79.99, '/images/africa-marruecos.svg',       1),
('África', 'Nigeria',         'africa-nigeria',         'Las Super Águilas en verde vibrante con diseño geométrico inspirado en el arte ibo.',     79.99, '/images/africa-nigeria.svg',         1),
('África', 'Senegal',         'africa-senegal',         'Los Leones de Teranga, campeones de África. Blanco y verde con estrellas doradas.',       79.99, '/images/africa-senegal.svg',         1),
('África', 'Costa de Marfil', 'africa-costa-de-marfil', 'Los Elefantes en naranja llama. Uno de los colores más reconocibles del fútbol africano.',79.99, '/images/africa-costa-de-marfil.svg', 1);

-- ASIA
INSERT INTO products (brand, model, slug, description, price, image_path, active) VALUES
('Asia', 'Japón',        'asia-japon',        'Los Samurai Blue, revelación de Qatar 2022. Azul marino con escudo del crisantemo.',          84.99, '/images/asia-japon.svg',        1),
('Asia', 'Corea del Sur','asia-corea-del-sur','Los Guerreros Taeguk en rojo pasión con detalles en azul de la bandera nacional.',           84.99, '/images/asia-corea-del-sur.svg',1),
('Asia', 'Arabia Saudí', 'asia-arabia-saudi', 'Las Águilas Verdes, sorpresa del Mundial con su victoria ante Argentina en Qatar 2022.',     79.99, '/images/asia-arabia-saudi.svg', 1),
('Asia', 'Australia',    'asia-australia',    'Los Socceroos en dorado y verde. Diseño moderno con escudo del canguro y la estrella federal.',79.99, '/images/asia-australia.svg',    1);

-- Stock por talla S, M, L para cada equipo (IDs 1-16 en orden de inserción)
INSERT INTO product_stock (product_id, size, quantity) VALUES
-- Europa: España (1), Francia (2), Alemania (3), Inglaterra (4)
(1,'S',4),(1,'M',8),(1,'L',5),
(2,'S',5),(2,'M',7),(2,'L',4),
(3,'S',3),(3,'M',6),(3,'L',5),
(4,'S',4),(4,'M',9),(4,'L',6),
-- Sudamérica: Brasil (5), Argentina (6), Uruguay (7), Colombia (8)
(5,'S',6),(5,'M',10),(5,'L',7),
(6,'S',5),(6,'M',9),(6,'L',6),
(7,'S',3),(7,'M',5),(7,'L',4),
(8,'S',4),(8,'M',6),(8,'L',3),
-- África: Marruecos (9), Nigeria (10), Senegal (11), Costa de Marfil (12)
(9,'S',4),(9,'M',7),(9,'L',5),
(10,'S',3),(10,'M',6),(10,'L',4),
(11,'S',5),(11,'M',8),(11,'L',5),
(12,'S',3),(12,'M',5),(12,'L',3),
-- Asia: Japón (13), Corea del Sur (14), Arabia Saudí (15), Australia (16)
(13,'S',4),(13,'M',7),(13,'L',5),
(14,'S',4),(14,'M',6),(14,'L',4),
(15,'S',3),(15,'M',5),(15,'L',3),
(16,'S',4),(16,'M',6),(16,'L',4);
