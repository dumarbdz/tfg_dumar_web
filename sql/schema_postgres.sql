-- Esquema PostgreSQL para Neon — nombres y columnas según el código PHP

DROP TABLE IF EXISTS sessions;
DROP TABLE IF EXISTS intentos_login;
DROP TABLE IF EXISTS carrito;
DROP TABLE IF EXISTS favoritos;
DROP TABLE IF EXISTS valoraciones;
DROP TABLE IF EXISTS lineas_pedido;
DROP TABLE IF EXISTS pedidos;
DROP TABLE IF EXISTS stock;
DROP TABLE IF EXISTS productos;
DROP TABLE IF EXISTS recuperaciones_password;
DROP TABLE IF EXISTS usuarios;

CREATE TABLE usuarios (
  id SERIAL PRIMARY KEY,
  email VARCHAR(255) NOT NULL UNIQUE,
  nombre VARCHAR(100) NOT NULL,
  contrasena_hash VARCHAR(255) NOT NULL,
  es_admin BOOLEAN NOT NULL DEFAULT FALSE,
  dir_nombre    VARCHAR(200) DEFAULT NULL,
  dir_linea1    VARCHAR(255) DEFAULT NULL,
  dir_postal    VARCHAR(32)  DEFAULT NULL,
  dir_ciudad    VARCHAR(120) DEFAULT NULL,
  dir_pais      VARCHAR(120) DEFAULT NULL,
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE recuperaciones_password (
  id SERIAL PRIMARY KEY,
  usuario_id INTEGER NOT NULL,
  token_hash CHAR(64) NOT NULL UNIQUE,
  expira_en TIMESTAMP NOT NULL,
  usado_en TIMESTAMP DEFAULT NULL,
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_rp_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
);
CREATE INDEX idx_rp_usuario ON recuperaciones_password(usuario_id);
CREATE INDEX idx_rp_expira ON recuperaciones_password(expira_en);

CREATE TABLE productos (
  id SERIAL PRIMARY KEY,
  continente VARCHAR(50) NOT NULL CHECK (continente IN ('Europa','Sudamérica','África','Asia')),
  seleccion VARCHAR(150) NOT NULL,
  slug VARCHAR(200) NOT NULL UNIQUE,
  descripcion TEXT,
  precio DECIMAL(10,2) NOT NULL,
  imagen VARCHAR(255) DEFAULT NULL,
  activo BOOLEAN NOT NULL DEFAULT TRUE
);
CREATE INDEX idx_productos_continente ON productos(continente);

CREATE TABLE stock (
  id SERIAL PRIMARY KEY,
  producto_id INTEGER NOT NULL,
  talla VARCHAR(10) NOT NULL,
  cantidad INTEGER NOT NULL DEFAULT 0,
  UNIQUE (producto_id, talla),
  CONSTRAINT fk_stock_producto FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE
);

CREATE TABLE pedidos (
  id SERIAL PRIMARY KEY,
  usuario_id INTEGER NOT NULL,
  total DECIMAL(10,2) NOT NULL,
  estado VARCHAR(20) NOT NULL DEFAULT 'pendiente' CHECK (estado IN ('pendiente','completado','enviado','cancelado')),
  envio_nombre VARCHAR(200) NOT NULL DEFAULT '',
  envio_linea1 VARCHAR(255) NOT NULL DEFAULT '',
  envio_postal VARCHAR(32) NOT NULL DEFAULT '',
  envio_ciudad VARCHAR(120) NOT NULL DEFAULT '',
  envio_pais VARCHAR(120) NOT NULL DEFAULT '',
  metodo_pago VARCHAR(50) NOT NULL DEFAULT '',
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_pedidos_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

CREATE TABLE lineas_pedido (
  id SERIAL PRIMARY KEY,
  pedido_id INTEGER NOT NULL,
  producto_id INTEGER NOT NULL,
  talla VARCHAR(10) NOT NULL,
  cantidad INTEGER NOT NULL,
  precio_unitario DECIMAL(10,2) NOT NULL,
  CONSTRAINT fk_lp_pedido FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE,
  CONSTRAINT fk_lp_producto FOREIGN KEY (producto_id) REFERENCES productos(id)
);

CREATE TABLE valoraciones (
  id SERIAL PRIMARY KEY,
  producto_id INTEGER NOT NULL,
  usuario_id INTEGER NOT NULL,
  puntuacion SMALLINT NOT NULL CHECK (puntuacion BETWEEN 1 AND 5),
  comentario TEXT DEFAULT NULL,
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE (producto_id, usuario_id),
  CONSTRAINT fk_val_producto FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE,
  CONSTRAINT fk_val_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
);

CREATE TABLE carrito (
  usuario_id  INTEGER      NOT NULL,
  producto_id INTEGER      NOT NULL,
  talla       VARCHAR(10)  NOT NULL,
  cantidad    SMALLINT     NOT NULL CHECK (cantidad > 0),
  PRIMARY KEY (usuario_id, producto_id, talla),
  CONSTRAINT fk_cart_usuario  FOREIGN KEY (usuario_id)  REFERENCES usuarios(id)  ON DELETE CASCADE,
  CONSTRAINT fk_cart_producto FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE
);

CREATE TABLE favoritos (
  usuario_id INTEGER NOT NULL,
  producto_id INTEGER NOT NULL,
  PRIMARY KEY (usuario_id, producto_id),
  CONSTRAINT fk_fav_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  CONSTRAINT fk_fav_producto FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE
);

CREATE TABLE intentos_login (
  ip VARCHAR(45) PRIMARY KEY,
  intentos SMALLINT NOT NULL DEFAULT 1,
  bloqueado_hasta TIMESTAMP DEFAULT NULL,
  actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE OR REPLACE FUNCTION fn_intentos_login_touch()
RETURNS TRIGGER AS $$
BEGIN
  NEW.actualizado_en = CURRENT_TIMESTAMP;
  RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trg_intentos_login_touch
BEFORE UPDATE ON intentos_login
FOR EACH ROW EXECUTE FUNCTION fn_intentos_login_touch();

-- Tabla para sesiones PHP en entorno serverless (Vercel)
CREATE TABLE sessions (
  id TEXT PRIMARY KEY,
  data TEXT NOT NULL DEFAULT '',
  last_activity BIGINT NOT NULL
);
