# UniMatch – Diseño Técnico Inicial

Este documento resume la estructura de datos y los módulos prioritarios para la evolución del sistema a partir de los requerimientos levantados con jefatura de contabilidad e ingresos.

## 1. Roles y Permisos

| Rol                    | Descripción                                       | Accesos principales                                                                                           |
|------------------------|---------------------------------------------------|---------------------------------------------------------------------------------------------------------------|
| `jefe_contabilidad`    | Supervisión y gobierno del sistema                | Tablero ejecutivo, gestión de usuarios/roles, configuración de formatos bancarios, reportes globales         |
| `encargado_ingresos`   | Operación diaria de ingresos                      | Carga de extractos, reportes de facturación y vouchers; conciliación y reportes operativos                    |
| `cajero`               | Consulta en ventanilla                            | Consulta solo lectura de transacciones/vouchers, descarga de comprobantes                                     |
| `estudiante`           | Autogestión de comprobantes                       | Subir y actualizar vouchers, revisar estados, consultar saldo a favor                                         |

Se controlará el acceso mediante middleware/guards y políticas por módulo. Las nuevas rutas se agruparán por prefijo `ingresos`, `jefatura`, `cajero`, `estudiante`.

## 2. Modelo de Datos Propuesto

### Catálogos
- **banks** (`id`, `name`, `short_code`, `status`, `format_config` JSON)
- **bank_accounts** (`id`, `bank_id`, `account_number`, `currency`, `active`)

### Captura de datos
- **bank_statements** (`id`, `bank_id`, `account_id`, `import_batch_id`, `source_name`, `statement_date`, `status`, `meta`)
- **bank_statement_lines** (`id`, `statement_id`, `operation_number`, `reference`, `description`, `operation_date`, `value_date`, `amount`, `currency`, `running_balance`, `raw_payload`)
- **invoice_batches** (`id`, `import_batch_id`, `source_name`, `issued_at`, `status`, `meta`)
- **invoices** (`id`, `invoice_batch_id`, `invoice_number`, `student_id`, `amount`, `currency`, `issued_at`, `status`, `raw_payload`)
- **voucher_batches** (`id`, `import_batch_id`, `source_name`, `uploaded_by`, `status`, `meta`)
- **payment_vouchers** (`id`, `voucher_batch_id`, `student_id`, `bank_id`, `operation_number`, `amount`, `paid_at`, `currency`, `status`, `reason`, `document_path`, `raw_payload`)

`import_batch_id` referencia una tabla común **import_batches** (`id`, `import_type`, `uploaded_by`, `uploaded_at`, `status`, `errors`, `summary_data`).

### Conciliación y seguimiento
- **transactions** (`id`, `bank_statement_line_id`, `voucher_id`, `invoice_id`, `student_id`, `status`, `notes`, `matched_at`, `matched_by`, `difference_amount`)
  - `status`: `pending`, `matched`, `no_facturado`, `demasia`, `rechazado`
- **transaction_logs** (`id`, `transaction_id`, `action`, `old_values`, `new_values`, `performed_by`, `performed_at`)

### Estudiantes y montos
- **students** (`id`, `code`, `full_name`, `program`, `email`, `meta`)
- **student_balances** (`id`, `student_id`, `currency`, `balance_amount`, `updated_at`)

## 3. Módulos Prioritarios

### 3.1 Ingesta de archivos (Encargado de ingresos)
1. **Carga de extracto**  
   - Subir archivo (CSV/XLSX) → se normaliza según banco → se crea `import_batch` → registros en `bank_statements` + `bank_statement_lines`.  
   - Validaciones: formato esperado, duplicados (por `operation_number + amount`), totales vs saldo.
2. **Carga de facturación**  
   - Similar proceso: `invoice_batches` + `invoices`.  
   - Validar número de factura único y monto positivo.
3. **Carga de vouchers**  
   - Lote o manual. Para manual se guarda `document_path` (storage).  
   - `status`: `recibido`, `validando`, `validado`, `rechazado`.

### 3.2 Conciliación
1. UI (módulo existente) consumirá endpoints `/api/ingresos/conciliacion`.  
2. Reglas iniciales:
   - Emparejar transacción con voucher por `operation_number` + `amount`.
   - Emparejar transacción con factura por `invoice_number` o `student_id + amount`.
   - Determinar estado `demasia` cuando `amount_transacción > amount_factura`.
3. Confirmar conciliación → crear/actualizar `transactions` y el `student_balance`.  
4. Seleccionar manualmente facturas/vouchers alternativos.  
5. Registrar en `transaction_logs` para auditoría.

### 3.3 Reportes
1. **Operativos** (encargado):  
   - Reporte por fecha de facturación (facturado vs no facturado).  
   - Operaciones sin factura.  
   - Pagos en demasía (saldo estudiante).  
   - Exportar CSV/PDF.
2. **Ejecutivos** (jefatura):  
   - Indicadores diarios/semana/mes: total facturado, pendientes, alertas.  
   - Saldos por banco (sumatoria statement lines – conciliaciones).  
   - Resumen por campus/programa (futuro).

### 3.4 Gestión de Configuración (Jefatura)
- CRUD de usuarios (salvando roles).  
- Configurar formato esperado por banco (mapeo columnas + reglas).  
- Parametrizar umbrales de alertas (ej. diferencia permitida).

### 3.5 Portales de lectura
- **Cajero**: búsqueda en tiempo real (nombre, matrícula, referencia); acceso a PDF de factura o comprobante.  
- **Estudiante**: historial de vouchers, estados, saldo a favor; actualización de voucher rechazado.

## 4. Endpoints / API (borrador)

| Método | Ruta                                        | Rol permitido                 | Descripción |
|--------|---------------------------------------------|-------------------------------|-------------|
| POST   | `/api/ingresos/import/extractos`            | Encargado                     | Carga extracto bancario |
| POST   | `/api/ingresos/import/facturacion`          | Encargado                     | Importa reporte de facturación |
| POST   | `/api/ingresos/import/vouchers`             | Encargado                     | Carga vouchers en lote |
| GET    | `/api/ingresos/conciliacion`                | Encargado/Jefe                | Listado de transacciones con filtros |
| POST   | `/api/ingresos/conciliacion/{id}/confirmar` | Encargado                     | Confirma la conciliación manual |
| GET    | `/api/reportes/operativos`                  | Encargado/Jefe                | Reportes filtrados, export |
| GET    | `/api/reportes/ejecutivos`                  | Jefe                          | Indicadores resumen |
| GET    | `/api/cajero/transacciones`                 | Cajero                        | Consulta readonly |
| GET    | `/api/estudiante/vouchers`                  | Estudiante                    | Historial propio |
| POST   | `/api/estudiante/vouchers`                  | Estudiante                    | Subir nuevo voucher |
| PUT    | `/api/estudiante/vouchers/{id}`             | Estudiante                    | Reemplazar voucher rechazado |

## 5. Pasos siguientes
1. Generar migraciones iniciales acorde al modelo superior (priorizar bancos, statements, invoices, vouchers, transactions).  
2. Crear controladores y servicios para importación de extractos/facturación, con validación de formatos.  
3. Implementar API de conciliación reutilizando la UI existente del panel de ingresos.  
4. Construir reportes operativos (tablas + export) y tablero ejecutivo (agregados).  
5. Añadir portales de consulta (cajero/estudiante) conectando a endpoints de lectura.  
6. Configurar políticas/middleware por rol antes de exponer cada ruta.
