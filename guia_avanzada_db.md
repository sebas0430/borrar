# Guía de Implementación: Tablespaces y Particionamiento en PostgreSQL

Este documento describe paso a paso cómo mover datos a diferentes directorios (tablespaces) y cómo particionar una tabla por fechas para mejorar el rendimiento y la organización del disco.

---

## Paso 1: Preparación del Sistema de Archivos
PostgreSQL necesita directorios físicos donde guardará la información de los nuevos espacios de trabajo. Estos deben estar fuera del directorio de datos por defecto.

**Ejecutar en la terminal de Linux:**
```bash
# Crear los directorios para los tablespaces
sudo mkdir -p /var/lib/postgresql/ts_historia_1
sudo mkdir -p /var/lib/postgresql/ts_actual_2

# Dar permisos al usuario 'postgres' (dueño del motor)
sudo chown postgres:postgres /var/lib/postgresql/ts_historia_1
sudo chown postgres:postgres /var/lib/postgresql/ts_actual_2
```

---

## Paso 2: Creación de Tablespaces
Un tablespace permite definir localizaciones en el sistema de archivos donde se almacenarán los archivos que representan los objetos de la base de datos.

**Ejecutar como superusuario en PostgreSQL:**
```sql
CREATE TABLESPACE ts_archivo_muerto LOCATION '/var/lib/postgresql/ts_historia_1';
CREATE TABLESPACE ts_operacion_vacia LOCATION '/var/lib/postgresql/ts_actual_2';
```

---

## Paso 3: Diseño de la Tabla Particionada
El particionamiento permite dividir una tabla grande en trozos más pequeños basados en una columna (en este caso, la fecha `solicitado`).

**Implementación para la tabla `pedidos`:**
```sql
-- 1. Renombrar la tabla original para respaldo
ALTER TABLE pedidos RENAME TO pedidos_old;

-- 2. Crear la tabla 'maestra' particionada
CREATE TABLE pedidos (
    id SERIAL,
    cliente_id INTEGER,
    mesero_id INTEGER,
    solicitado TIMESTAMP WITH TIME ZONE,
    PRIMARY KEY (id, solicitado)
) PARTITION BY RANGE (solicitado);

-- 3. Crear las particiones y asignarlas a los Tablespaces
-- Datos del 2025 al disco 'historia' (más lento/barato)
CREATE TABLE pedidos_2025 PARTITION OF pedidos
    FOR VALUES FROM ('2025-01-01') TO ('2026-01-01')
    TABLESPACE ts_archivo_muerto;

-- Datos del 2026 al disco 'actual' (más rápido/SSD)
CREATE TABLE pedidos_2026 PARTITION OF pedidos
    FOR VALUES FROM ('2026-01-01') TO ('2027-01-01')
    TABLESPACE ts_operacion_vacia;
```

---

## Paso 4: Migración de Datos
Para pasar los datos de la tabla vieja a la nueva estructura particionada:

```sql
INSERT INTO pedidos (id, cliente_id, mesero_id, solicitado)
SELECT id, cliente_id, mesero_id, solicitado FROM pedidos_old;

-- Verificar la distribución
SELECT tableoid::regclass, count(*) FROM pedidos GROUP BY 1;
```

---

## Paso 5: Verificación
Puedes comprobar que los objetos están correctamente ubicados con:

```sql
-- Listar tablespaces creados
\db

-- Listar tablas y su respectivo tablespace
SELECT tablename, tablespace FROM pg_tables WHERE schemaname = 'public';
```

---

### Notas de Seguridad
*   **Superusuario:** Solo un superusuario (usualmente `postgres`) puede crear tablespaces por razones de seguridad del sistema de archivos.
*   **Indices:** Los índices creados en la tabla maestra se propagan automáticamente a las particiones.
