BEGIN;
CREATE TABLE IF NOT EXISTS configuracion_pagos (
  id SMALLINT PRIMARY KEY DEFAULT 1 CHECK (id = 1),
  banco VARCHAR(120),
  titular VARCHAR(160),
  instrucciones VARCHAR(500),
  qr_imagen VARCHAR(255),
  actualizado_por BIGINT REFERENCES usuarios(id),
  updated_at TIMESTAMPTZ NOT NULL DEFAULT now()
);
INSERT INTO configuracion_pagos(id,banco,titular,instrucciones)
VALUES(1,'Banco por configurar','Clínica Vida Salud','Escanea el QR y presenta el comprobante en recepción.')
ON CONFLICT(id) DO NOTHING;
COMMIT;
