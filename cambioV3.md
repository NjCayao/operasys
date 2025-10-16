# 📋 CHANGELOG - SISTEMA DE CONTROL DE PRODUCCIÓN V3.0

haber vamos por pasos siempre me tienes que decir donde corregir o que codigo cambiar o agregar antes o despues de que codigo? mientras vamos avanzando en esta fase no tiene que haber nada y ningun archivo con esto https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css

tenemos que cambiarlo

inclujo los js la palntilla admintle3 esta localmente ya que la aplicacion es para que funcione literalmente offline me dejo entender ahora si vallamos archivo por archivo probando todo y corrigiendo todo

vamos a ir modulo por modulo ok. no hagas suposiciones, siempre pregunta. . ha y no te olvides que siempre tenemos que poner este codigo para poder actualizar. 
e ir agregando su  versionado ejemplo:
$custom_js_file = 'assets/js/fases_costo.js?v=' . ASSETS_VERSION;


Fecha de Inicio: 16 de Octubre 2025
Desarrollador: Tu equipo + Claude
Objetivo: Migrar de sistema básico a planillas de control de producción profesional

🎯 RESUMEN EJECUTIVO
¿Qué vamos a cambiar?
ANTES (V2.0):

Reporte = múltiples actividades genéricas
Tipos de trabajo + Fases de costo
Sin diferenciación HT/HP
Sin control de combustible estimado

DESPUÉS (V3.0):

Reporte = Planilla de control diario profesional
HT (Horas Trabajadas) vs HP (Horas Paradas)
Control de consumo de combustible con estimaciones
Cálculo de eficiencia operativa
Registro de hora real + horómetro


# 📦 FASE 1: REDISEÑO DE BASE DE DATOS
Duración Estimada: 20 minutos
Riesgo: 🟡 Medio (requiere migración de datos)
1.1 Modificaciones a Tablas Existentes
✏️ Tabla equipos
sqlCAMBIOS:
+ consumo_promedio_hr DECIMAL(5,2)      -- Galones por hora
+ capacidad_tanque DECIMAL(8,2)         -- Capacidad total en galones
Propósito: Calcular consumo estimado automáticamente
Datos a cargar: Consumos por categoría (Excavadora: 8 gal/hr, etc.)

✏️ Tabla reportes
sqlCAMBIOS:
+ horometro_inicial DECIMAL(10,1)       -- Horómetro al inicio del día
+ horometro_final DECIMAL(10,1)         -- Horómetro al fin del día
+ horas_motor DECIMAL(5,2) [CALCULADO]  -- horometro_final - inicial
+ consumo_estimado DECIMAL(8,2) [CALCULADO] -- horas_motor × consumo_hr
+ total_abastecido DECIMAL(8,2)         -- Suma de abastecimientos
+ diferencia_combustible DECIMAL(8,2) [CALCULADO] -- abastecido - estimado

ELIMINADOS:
- (ninguno, solo se agregan campos)
Propósito: Control completo de horómetro y combustible del día

✏️ Tabla reportes_detalle (REDISEÑO COMPLETO)
sqlCAMBIOS MAYORES:
- tipo_trabajo_id      → ELIMINADO
- fase_costo_id        → ELIMINADO
- horometro_inicial    → ELIMINADO (ahora solo en reportes)
- horometro_final      → ELIMINADO (ahora solo en reportes)

NUEVOS CAMPOS:
+ tipo_hora ENUM('HT', 'HP')            -- Trabajada o Parada
+ hora_inicio TIME                      -- Hora real de inicio (07:00)
+ hora_fin TIME                         -- Hora real de fin (09:30)
+ horas_transcurridas DECIMAL(5,2) [CALCULADO] -- hora_fin - hora_inicio
+ actividad VARCHAR(150)                -- Descripción libre
+ partida VARCHAR(50) NULL              -- Solo para HT (FC-001, etc.)
+ observaciones TEXT
+ orden INT                             -- Orden cronológico

CONSTRAINT:
- Si tipo_hora = 'HT' → partida es obligatoria
- Si tipo_hora = 'HP' → partida es NULL
Propósito: Diferenciar claramente horas productivas de paradas

✏️ Tabla reportes_combustible
sqlCAMBIOS:
+ hora_abastecimiento TIME              -- Hora del abastecimiento

RENOMBRAR:
horometro (mantener, es el horómetro al momento del abastecimiento)
Propósito: Registro más preciso de cuándo se abastece

1.2 Nuevas Tablas
🆕 Tabla catalogo_actividades
sqlESTRUCTURA:
- id INT PRIMARY KEY
- tipo ENUM('HT', 'HP')                 -- Para qué tipo de hora
- nombre VARCHAR(150)                   -- "Excavación", "Charla seguridad"
- es_frecuente TINYINT(1)              -- Aparece en sugerencias
- estado TINYINT(1)
- fecha_creacion TIMESTAMP

DATOS INICIALES:
HT: Excavación, Carga, Transporte, Compactación, etc. (8 registros)
HP: Calentamiento, Charla, Almuerzo, Falla, Lluvia, etc. (11 registros)
Propósito: Autocompletado y estandarización de actividades

1.3 Renombrar Tablas
🔄 fases_costo → partidas
sqlACCIÓN: RENAME TABLE fases_costo TO partidas;

MANTENER:
- Estructura actual (id, codigo, descripcion, proyecto, estado)
- Datos existentes

AGREGAR (OPCIONAL):
+ metrado_estimado DECIMAL(10,2)       -- m3, m2, etc.
+ unidad VARCHAR(20)                   -- Unidad de medida
Propósito: Terminología más clara para obras civiles

❌ tipos_trabajo → ELIMINAR
sqlACCIÓN: DROP TABLE tipos_trabajo;
RAZÓN: Reemplazada por catalogo_actividades
CUIDADO: Verificar que no haya datos críticos antes de eliminar
```

---

### 📊 Resumen Fase 1

| Acción | Tabla | Estado |
|--------|-------|--------|
| ✏️ Modificar | equipos | +2 campos |
| ✏️ Modificar | reportes | +6 campos calculados |
| 🔄 Rediseñar | reportes_detalle | Estructura completa nueva |
| ✏️ Modificar | reportes_combustible | +1 campo |
| 🆕 Crear | catalogo_actividades | Nueva tabla |
| 🔄 Renombrar | fases_costo → partidas | Mismos datos |
| ❌ Eliminar | tipos_trabajo | Verificar primero |

**Entregables:**
- ✅ Script SQL completo: `migration_v3.0.sql`
- ✅ Script de rollback: `rollback_v3.0.sql`
- ✅ Documento de respaldo de datos


# FASE 2: ACTUALIZACIÓN DE APIs
**Duración Estimada:** 40 minutos  
**Riesgo:** 🟢 Bajo (no afecta datos)

### 2.1 APIs a Modificar

#### ✏️ `api/reportes.php`
```
ENDPOINTS ACTUALIZADOS:

GET /api/reportes.php?accion=obtener&id={id}
  NUEVOS CAMPOS EN RESPUESTA:
  + horometro_inicial
  + horometro_final
  + horas_motor
  + consumo_estimado
  + total_abastecido
  + diferencia_combustible

POST /api/reportes.php?accion=crear
  NUEVOS CAMPOS REQUERIDOS:
  + horometro_inicial (obligatorio)
  + horometro_final (obligatorio)
  
  VALIDACIONES:
  - horometro_final > horometro_inicial
  - diferencia < 24 horas (día laboral)

PUT /api/reportes.php?accion=actualizar
  PERMITE ACTUALIZAR:
  + horometro_final (mientras esté en borrador)
  + total_abastecido (recalculado automáticamente)
```

---

#### ✏️ `api/reportes_detalle.php` (NUEVO ARCHIVO)
```
SEPARADO DE reportes.php para mejor organización

POST /api/reportes_detalle.php?accion=agregar_actividad
  BODY:
  {
    "reporte_id": 123,
    "tipo_hora": "HT",
    "hora_inicio": "07:00",
    "hora_fin": "09:30",
    "actividad": "Excavación de plataforma",
    "partida": "FC-001",
    "observaciones": ""
  }
  
  VALIDACIONES:
  - hora_fin > hora_inicio
  - Si tipo_hora = 'HT' → partida obligatoria
  - No permitir solapamiento de horas
  
GET /api/reportes_detalle.php?accion=listar&reporte_id={id}
  RESPUESTA:
  {
    "ht": [...actividades HT...],
    "hp": [...actividades HP...],
    "totales": {
      "total_ht": 8.75,
      "total_hp": 1.25,
      "total_registrado": 10.0
    }
  }

DELETE /api/reportes_detalle.php?accion=eliminar&id={id}
  VALIDACIÓN:
  - Solo si reporte está en borrador
```

---

#### 🆕 `api/combustible.php` (NUEVO ARCHIVO)
```
POST /api/combustible.php?accion=agregar
  BODY:
  {
    "reporte_id": 123,
    "horometro": 1255.0,
    "hora_abastecimiento": "10:30",
    "galones": 45.5,
    "observaciones": ""
  }
  
  VALIDACIONES:
  - horometro entre horometro_inicial y horometro_final del reporte
  - galones <= capacidad_tanque del equipo

GET /api/combustible.php?accion=calcular_resumen&reporte_id={id}
  RESPUESTA:
  {
    "consumo_estimado": 80.0,
    "total_abastecido": 85.5,
    "diferencia": 5.5,
    "estado": "suficiente", // suficiente | normal | alerta
    "porcentaje_eficiencia": 106.9
  }

POST /api/combustible.php?accion=actualizar_total
  AUTOMÁTICO: Al agregar/eliminar abastecimientos
  ACCIÓN: Actualiza campo total_abastecido en reportes
```

---

#### ✏️ `api/equipos.php`
```
MODIFICACIONES MENORES:

GET /api/equipos.php?accion=listar
  NUEVOS CAMPOS EN RESPUESTA:
  + consumo_promedio_hr
  + capacidad_tanque

PUT /api/equipos.php?accion=actualizar_consumo
  SOLO ADMIN:
  {
    "id": 1,
    "consumo_promedio_hr": 8.5,
    "capacidad_tanque": 150
  }
```

---

#### 🆕 `api/actividades.php` (NUEVO ARCHIVO)
```
GET /api/actividades.php?tipo={HT|HP}&frecuentes=1
  RESPUESTA:
  [
    {"id": 1, "nombre": "Excavación de plataforma"},
    {"id": 2, "nombre": "Carga de material"},
    ...
  ]

POST /api/actividades.php?accion=crear
  SOLO ADMIN/SUPERVISOR:
  {
    "tipo": "HT",
    "nombre": "Nueva actividad personalizada",
    "es_frecuente": 1
  }
```

---

### 📊 Resumen Fase 2

| API | Tipo | Cambios |
|-----|------|---------|
| reportes.php | ✏️ Modificar | +6 campos, validaciones horometro |
| reportes_detalle.php | 🆕 Nuevo | CRUD actividades HT/HP |
| combustible.php | 🆕 Nuevo | Gestión abastecimientos |
| equipos.php | ✏️ Modificar | +2 campos |
| actividades.php | 🆕 Nuevo | Catálogo sugerencias |

**Entregables:**
- ✅ 3 archivos PHP nuevos
- ✅ 2 archivos PHP modificados
- ✅ Documentación de endpoints
- ✅ Colección Postman para testing

---

# 🎨 FASE 3: REDISEÑO DE FRONTEND
**Duración Estimada:** 60 minutos  
**Riesgo:** 🟡 Medio (cambios visuales importantes)

### 3.1 Módulo de Reportes

#### 🔄 `modules/reportes/crear.php` (REDISEÑO TOTAL)
```
NUEVA ESTRUCTURA:

┌─────────────────────────────────────┐
│ SECCIÓN 1: DATOS BÁSICOS            │
│ - Fecha (date picker)               │
│ - Equipo (dropdown)                 │
│ - Horómetro Inicial (number)        │
│ - Horómetro Final (number)          │
│ - Horas Motor (auto, readonly)      │
└─────────────────────────────────────┘

┌─────────────────────────────────────┐
│ SECCIÓN 2: HORAS TRABAJADAS (HT)    │
│ [Botón: + Agregar Actividad HT]     │
│                                     │
│ Tabla dinámica:                     │
│ - Hora Inicio (time picker)         │
│ - Hora Fin (time picker)            │
│ - HT (auto calculado)               │
│ - Actividad (dropdown + input)      │
│ - Partida (dropdown)                │
│ - Observaciones (textarea)          │
│ - [Eliminar]                        │
│                                     │
│ TOTAL HT: XX.XX horas               │
└─────────────────────────────────────┘

┌─────────────────────────────────────┐
│ SECCIÓN 3: HORAS PARADAS (HP)       │
│ [Botón: + Agregar Parada HP]        │
│                                     │
│ Tabla dinámica similar HT           │
│ (sin campo partida)                 │
│                                     │
│ TOTAL HP: XX.XX horas               │
└─────────────────────────────────────┘

┌─────────────────────────────────────┐
│ SECCIÓN 4: COMBUSTIBLE              │
│ [Consumo Estimado: XX gal]          │
│ [+ Registrar Abastecimiento]        │
│                                     │
│ Lista de abastecimientos            │
│ TOTAL ABASTECIDO: XX gal            │
│ DIFERENCIA: ±XX gal                 │
└─────────────────────────────────────┘

┌─────────────────────────────────────┐
│ SECCIÓN 5: RESUMEN                  │
│ Horas Motor | HT | HP | Eficiencia  │
│ [Guardar Borrador] [Finalizar]      │
└─────────────────────────────────────┘

VALIDACIONES EN TIEMPO REAL:
✅ Suma HT + HP = Horas Motor (alerta si no cuadra)
✅ Horas no se solapan
✅ Horometro_final > horometro_inicial
```

**Componentes reutilizables:**
- `modal_actividad_ht.php` - Modal agregar HT
- `modal_actividad_hp.php` - Modal agregar HP
- `modal_combustible.php` - Modal abastecimiento

---

#### 🔄 `modules/reportes/editar.php`
```
CAMBIOS:
- Misma estructura que crear.php
- Cargar datos existentes
- Solo editable si estado = 'borrador'
- Admin puede editar finalizados (con advertencia)
```

---

#### 🔄 `modules/reportes/ver.php`
```
VISTA DE SOLO LECTURA:

┌─────────────────────────────────────┐
│ ENCABEZADO                          │
│ Fecha | Equipo | Operador           │
│ Horómetro: XXXX → XXXX (XX hrs)    │
│ [Botón: Descargar PDF]              │
└─────────────────────────────────────┘

┌─────────────────────────────────────┐
│ HORAS TRABAJADAS (HT)               │
│ Tabla estática con todas las HT     │
│ TOTAL: XX.XX hrs                    │
└─────────────────────────────────────┘

┌─────────────────────────────────────┐
│ HORAS PARADAS (HP)                  │
│ Tabla estática con todas las HP     │
│ TOTAL: XX.XX hrs                    │
└─────────────────────────────────────┘

┌─────────────────────────────────────┐
│ CONTROL DE COMBUSTIBLE              │
│ Consumo Estimado vs Abastecido      │
│ Gráfico visual de diferencia        │
└─────────────────────────────────────┘

┌─────────────────────────────────────┐
│ RESUMEN Y MÉTRICAS                  │
│ Eficiencia | Productividad          │
└─────────────────────────────────────┘
```

---

#### 🔄 `modules/reportes/listar.php`
```
NUEVA COLUMNA EN DATATABLE:
+ Eficiencia (%)
+ Combustible (gal)

FILTROS ADICIONALES:
+ Rango de eficiencia
+ Alerta combustible (con diferencia negativa)
```

---

### 3.2 Módulo Admin

#### 🔄 `modules/admin/reportes_global.php`
```
NUEVAS COLUMNAS:
+ Horas Motor
+ HT Total
+ HP Total
+ Eficiencia Promedio
+ Combustible Estimado
+ Combustible Real
+ Diferencia

EXPORTACIÓN EXCEL:
+ Hoja adicional "Análisis Combustible"
+ Gráficos de eficiencia por equipo
```

---

#### 🆕 `modules/admin/actividades.php`
```
NUEVO MÓDULO:

Pestañas:
- [Actividades HT] [Actividades HP]

CRUD completo:
- Agregar nueva actividad
- Marcar como frecuente
- Desactivar (no eliminar)

DataTable con filtros
```

---

#### ✏️ `modules/admin/equipos.php` (módulo existente)
```
AGREGAR EN FORMULARIO:
+ Consumo Promedio/Hora (gal/hr)
+ Capacidad del Tanque (gal)

Mostrar en listado

❌ modules/admin/tipos_trabajo.php → ELIMINAR
❌ modules/admin/fases_costo.php → RENOMBRAR a partidas.php

📊 Resumen Fase 3
ArchivoAcciónComplejidadcrear.php🔄 Rediseño total🔴 Altaeditar.php🔄 Actualizar🟡 Mediaver.php🔄 Actualizar🟡 Medialistar.php✏️ Modificar🟢 Bajareportes_global.php✏️ Modificar🟡 Mediaactividades.php🆕 Crear🟡 Mediaequipos.php✏️ Modificar🟢 Bajapartidas.php🔄 Renombrar🟢 Baja
Entregables:

✅ 8 archivos PHP actualizados
✅ 3 modales reutilizables
✅ Componentes de validación


💻 FASE 4: LÓGICA JAVASCRIPT
Duración Estimada: 50 minutos
Riesgo: 🟡 Medio (lógica compleja)
4.1 Archivos Nuevos
🆕 assets/js/reportes.js (REESCRITURA COMPLETA)
javascriptFUNCIONES PRINCIPALES:

// Gestión de actividades HT
- agregarActividadHT()
- editarActividadHT(id)
- eliminarActividadHT(id)
- calcularTotalHT()

// Gestión de actividades HP
- agregarActividadHP()
- editarActividadHP(id)
- eliminarActividadHP(id)
- calcularTotalHP()

// Control de combustible
- agregarAbastecimiento()
- calcularConsumoEstimado()
- calcularDiferenciaCombustible()
- mostrarAlertaCombustible()

// Validaciones
- validarHorometros()
- validarHorasNoSolapadas()
- validarCuadreHoras() // HT + HP = Horas Motor

// Resumen
- actualizarResumenDiario()
- calcularEficiencia() // HT / Horas Motor * 100

// Guardado
- guardarBorrador()
- finalizarReporte()
Características especiales:

Autocompletado de actividades desde catálogo
Validación en tiempo real
Cálculos automáticos
Alertas visuales
Guardado autom ático cada 2 minutos


🆕 assets/js/actividades.js
javascript// CRUD de catálogo de actividades
- listarActividades(tipo) // HT o HP
- crearActividad()
- actualizarActividad(id)
- toggleFrecuente(id)
- desactivarActividad(id)

🆕 assets/js/combustible.js
javascript// Gestión de abastecimientos
- registrarAbastecimiento()
- validarCapacidadTanque()
- mostrarGraficoComparativo()

4.2 Archivos a Modificar
✏️ assets/js/reportes_global.js
javascriptAGREGAR:
- Columnas combustible en DataTable
- Filtros de eficiencia
- Exportación con datos de combustible
✏️ assets/js/equipos.js
javascriptAGREGAR:
- Campos consumo_promedio_hr
- Validación capacidad_tanque
```

---

### 📊 Resumen Fase 4

| Archivo | Acción | Líneas aprox. |
|---------|--------|---------------|
| reportes.js | 🔄 Reescritura | ~800 líneas |
| actividades.js | 🆕 Nuevo | ~300 líneas |
| combustible.js | 🆕 Nuevo | ~250 líneas |
| reportes_global.js | ✏️ Modificar | +100 líneas |
| equipos.js | ✏️ Modificar | +50 líneas |

**Entregables:**
- ✅ 3 archivos JS nuevos
- ✅ 2 archivos JS modificados
- ✅ Validaciones client-side completas

---

## 📄 FASE 5: GENERACIÓN DE PDF
**Duración Estimada:** 30 minutos  
**Riesgo:** 🟢 Bajo (ya existe base con FPDF)

### 5.1 Modificaciones

#### ✏️ `modules/reportes/generar_pdf.php`
```
NUEVA ESTRUCTURA DEL PDF:

PÁGINA 1:
┌─────────────────────────────────────┐
│ ENCABEZADO                          │
│ Logo | Empresa | Fecha              │
├─────────────────────────────────────┤
│ DATOS DEL REPORTE                   │
│ Equipo | Operador | Turno           │
│ Horómetro: XXXX → XXXX (XX hrs)    │
├─────────────────────────────────────┤
│ HORAS TRABAJADAS (HT)               │
│ Tabla con todas las actividades HT  │
│ TOTAL HT: XX.XX hrs                 │
├─────────────────────────────────────┤
│ HORAS PARADAS (HP)                  │
│ Tabla con todas las paradas         │
│ TOTAL HP: XX.XX hrs                 │
├─────────────────────────────────────┤
│ CONTROL DE COMBUSTIBLE              │
│ Consumo Estimado | Abastecido       │
│ Diferencia | Estado                 │
├─────────────────────────────────────┤
│ RESUMEN                             │
│ Eficiencia: XX% | Observaciones     │
├─────────────────────────────────────┤
│ FIRMAS                              │
│ Operador | Supervisor               │
└─────────────────────────────────────┘
Mejoras visuales:

Colores: Verde para HT, Naranja para HP
Iconos de estado de combustible
Gráfico de barras de eficiencia
Tabla resumen destacada


📊 Resumen Fase 5
ArchivoAcciónCambiosgenerar_pdf.php✏️ ModificarNueva estructura
Entregables:

✅ Template PDF actualizado
✅ Ejemplo de PDF generado


🔄 FASE 6: MIGRACIÓN DE DATOS
Duración Estimada: 15 minutos
Riesgo: 🔴 Alto (afecta datos existentes)
6.1 Script de Migración
sql-- 1. Respaldar datos actuales
CREATE TABLE reportes_backup AS SELECT * FROM reportes;
CREATE TABLE reportes_detalle_backup AS SELECT * FROM reportes_detalle;

-- 2. Asignar consumos a equipos existentes
UPDATE equipos SET 
  consumo_promedio_hr = CASE categoria
    WHEN 'Excavadora' THEN 8.0
    WHEN 'Cargador Frontal' THEN 12.0
    WHEN 'Rodillo' THEN 6.0
    WHEN 'Tractor' THEN 15.0
    ELSE 5.0
  END,
  capacidad_tanque = CASE categoria
    WHEN 'Excavadora' THEN 150
    WHEN 'Cargador Frontal' THEN 200
    WHEN 'Rodillo' THEN 100
    WHEN 'Tractor' THEN 300
    ELSE 80
  END;

-- 3. Convertir datos antiguos
-- (Si existen reportes anteriores, migrarlos al nuevo formato)
-- NOTA: Requiere análisis caso por caso

-- 4. Cargar catálogo de actividades
INSERT INTO catalogo_actividades (tipo, nombre, es_frecuente) VALUES
('HT', 'Excavación de plataforma', 1),
...
('HP', 'Charla de seguridad', 1);
6.2 Validación Post-Migración
sql-- Verificar integridad
SELECT COUNT(*) FROM reportes WHERE horas_motor IS NULL;
SELECT COUNT(*) FROM equipos WHERE consumo_promedio_hr = 0;
SELECT COUNT(*) FROM catalogo_actividades;

📊 Resumen Fase 6
Entregables:

✅ Script de respaldo
✅ Script de migración
✅ Script de validación
✅ Script de rollback


📱 FASE 7: PWA Y OFFLINE (OPCIONAL)
Duración Estimada: 20 minutos
Riesgo: 🟢 Bajo (ya existe base)
7.1 Actualizaciones IndexedDB
javascript// Agregar nuevas tablas a IndexedDB
- catalogo_actividades
- partidas (rename de fases_costo)

// Actualizar esquema de reportes
- Campos adicionales de combustible
```

---

## 🧪 FASE 8: TESTING Y VALIDACIÓN
**Duración Estimada:** 30 minutos  
**Riesgo:** 🟢 Bajo

### 8.1 Casos de Prueba
```
✅ TEST 1: Crear reporte nuevo con HT y HP
✅ TEST 2: Validar cálculo de consumo estimado
✅ TEST 3: Validar alerta cuando HT+HP ≠ Horas Motor
✅ TEST 4: Agregar múltiples abastecimientos
✅ TEST 5: Finalizar reporte (no editable)
✅ TEST 6: Editar reporte borrador
✅ TEST 7: Exportar PDF con nuevo formato
✅ TEST 8: Exportar Excel con columnas combustible
✅ TEST 9: CRUD actividades (admin)
✅ TEST 10: Permisos por rol
```

---

## 📊 CRONOGRAMA TOTAL
```
┌──────────┬─────────────────────────────┬──────────┬─────────┐
│ Fase     │ Descripción                 │ Duración │ Riesgo  │
├──────────┼─────────────────────────────┼──────────┼─────────┤
│ Fase 1   │ Base de Datos               │ 20 min   │ 🟡      │
│ Fase 2   │ APIs Backend                │ 40 min   │ 🟢      │
│ Fase 3   │ Frontend                    │ 60 min   │ 🟡      │
│ Fase 4   │ JavaScript                  │ 50 min   │ 🟡      │
│ Fase 5   │ PDF                         │ 30 min   │ 🟢      │
│ Fase 6   │ Migración                   │ 15 min   │ 🔴      │
│ Fase 7   │ PWA (Opcional)              │ 20 min   │ 🟢      │
│ Fase 8   │ Testing                     │ 30 min   │ 🟢      │
├──────────┼─────────────────────────────┼──────────┼─────────┤
│ TOTAL    │                             │ 4-5 hrs  │         │
└──────────┴─────────────────────────────┴──────────┴─────────┘

🎯 CHECKLIST DE IMPLEMENTACIÓN
Pre-implementación

 Hacer backup completo de la base de datos
 Respaldar archivos PHP actuales
 Informar a usuarios del mantenimiento
 Preparar entorno de desarrollo/testing

Durante implementación

 Ejecutar scripts SQL en orden
 Validar cada fase antes de continuar
 Probar en ambiente de desarrollo primero
 Documentar cualquier error encontrado

Post-implementación

 Verificar todos los reportes existentes
 Capacitar a usuarios en nuevo sistema
 Monitorear primeros días de uso
 Recopilar feedback de usuarios


📝 NOTAS IMPORTANTES
⚠️ Advertencias

NO ejecutar en producción sin backup
Validar consumos por categoría con tu equipo
Revisar reportes antiguos antes de migrar
Testing exhaustivo antes de desplegar

💡 Recomendaciones

Implementar fase por fase (no todo junto)
Primero en desarrollo, luego producción
Mantener versión antigua accesible 1 semana
Documentar todo cambio realizado


📞 SOPORTE POST-IMPLEMENTACIÓN
Posibles problemas y soluciones
Problema: Suma HT+HP no cuadra con Horas Motor
Solución: Validar que todas las actividades estén registradas
Problema: Consumo estimado muy diferente al real
Solución: Ajustar consumo_promedio_hr del equipo
Problema: Reportes antiguos no se ven bien
Solución: Ejecutar script de migración de datos