# Documentación Técnica — Plataforma de Gestión de Eventos Musicales (Núñez and Son / JAVNX)

Este documento describe la arquitectura, estructura de datos, modelos, servicios, controladores y componentes Livewire de la aplicación de gestión integral de bodas y eventos musicales (**Núñez and Son** / **JAVNX**).

---

## 1. Arquitectura General y Stack Tecnológico

- **Framework Backend:** Laravel 11 / 12 (PHP 8.2+)
- **Frontend Reactivo:** Livewire 3 + Alpine.js + Tailwind CSS
- **Base de Datos:** MySQL / MariaDB
- **Generación de Documentos:**
  - **PDF:** `barryvdh/laravel-dompdf` (Presupuestos, Contratos, Facturas/Recibos, Dossier de Tarifas, Escaletas Musicales, Hojas de Carga / Packing List, Carteles QR e Informes de Facturación).
  - **Excel (`.xlsx`):** Generador nativo OpenXML (`ZipArchive`) en `BillingReportService` con múltiples pestañas sin dependencias externas pesadas.
- **Integraciones Externas:**
  - **WhatsApp Business (Evolution API v2):** Envío de propuestas, contratos, recibos, cuestionarios musicales y recepción de mensajes entrantes mediante Webhook en tiempo real.
  - **Álbum de Fotos QR (`fotos.nunezandson.com`):** Aprovisionamiento automático de galerías para invitados y diseño de carteles PDF con QR (`FotosEventosQrService` + `QrPosterPdfService`).
  - **Captación Automática de Leads (IMAP & Webhooks):** Lectura automática de solicitudes entrantes desde portales nupciales (*Bodas.net*, *Zankyou*, correo electrónico y formulario web `/presupuesto`).
  - **Música y Catálogo (Spotify API / iTunes / Deezer / Nube):** Búsqueda de canciones con preescucha de audio, importación de playlists de Spotify y análisis de historiales de sesión DJ (Rekordbox / VirtualDJ / Serato).

---

## 2. Mapa de Modelos (`app/Models/`)

Todos los modelos Eloquent se encuentran en `app/Models/` y cuentan con bloques PHPDoc en cada relación, *accessor* (`get...Attribute`) y método de negocio:

| Modelo | Tabla | Responsabilidad Principal |
| :--- | :--- | :--- |
| **`Event`** | `events` | Entidad central del sistema. Gestiona fechas, horarios (`ceremony_time`, `cocktail_time`, `banquet_time`, `dance_start_time`), marca (`nunez_and_son`, `javnx`, `mago_leugim`), estado, desglose financiero (`financial_breakdown`), resumen de proveedores externos (`external_provider_summary`), detección de horarios desde notas (`extractScheduleTimesFromText`), progreso del checklist de producción y tokens de acceso público (`public_token`). |
| **`Quote`** | `quotes` | Presupuestos y propuestas económicas asociadas a un evento. Soporta múltiples revisiones por evento, aceptación/rechazo online, cálculo de señal dinámica (`signal_amount`) y sincronización automática del estado del evento (`syncEventStatusAfterQuoteChange`). |
| **`QuoteItem`** | `quote_items` | Líneas individuales de servicio dentro de un presupuesto (`service_name`, `description`, `price`, `quantity`). |
| **`Contract`** | `contracts` | Contratos legales de servicios vinculados a un evento (`contract_number`, `content`, `status`, datos de firma digital IP/User-Agent y firma manuscrita en Base64). |
| **`Invoice`** | `invoices` | Documentos de cobro: soporta tanto **Facturas con IVA** (`factura`) como **Recibos / Justificantes de Señal o Pago sin IVA** (`recibo`). Incluye serie automática, método de pago (`bizum`, `transfer`, `cash`, `card`) y estado. |
| **`EventProviderBooking`** | `event_provider_bookings` | Reservas y control de pagos (1º pago de señal + 2º pago de liquidación) a empresas proveedoras externas de **Fotomatón, Plataforma 360º y Mesa Dulce**, incluyendo PVP repercutido al cliente (`client_price`) y coste del proveedor (`provider_total_cost`). |
| **`ClientMeeting`** | `client_meetings` | Citas, reuniones presenciales/videollamadas y notas de seguimiento comercial con los novios o clientes. |
| **`EventMusicRequest`** | `event_music_requests` | Peticiones musicales en vivo realizadas por los invitados durante la fiesta mediante el código QR de cabina, con sistema de votos (*likes*). |
| **`EventDjHistory`** | `event_dj_histories` | Historiales reales de canciones pinchadas por el DJ en cada evento (importados desde Rekordbox, VirtualDJ, Serato o CSV/TXT). |
| **`Dossier`** | `dossiers` | Cuestionario musical y escaleta personalizada rellenada por los novios (canciones de entrada, ceremonia, cóctel, corte de tarta, baile nupcial, canciones imprescindibles y vetadas). |
| **`Equipment`** | `equipment` | Inventario de equipos de sonido, iluminación, cabinas, microfonía y efectos especiales, vinculado a eventos mediante `event_equipment`. |
| **`Playlist` / `Track`** | `playlists` / `tracks` | Listas musicales recomendadas e inspiración por momentos de la boda. |
| **`WhatsAppMessage`** | `whatsapp_messages` | Registro histórico bidireccional de mensajes de WhatsApp enviados y recibidos de cada evento/cliente. |
| **`Setting`** | `settings` | Configuración global del sistema, datos fiscales por marca, tarifas del configurador online, plantillas y proveedores de Fotomatón/360º (`getPhotoboothProviders`). |
| **`User`** | `users` | Usuarios del sistema con roles diferenciados: `admin` (administración total), `dj` (cabina y eventos asignados), `assistant` (montaje y apoyo técnico) y `client` (clientes/novios). |

---

## 3. Servicios de Negocio (`app/Services/`)

La lógica de negocio reutilizable e integraciones están desacopladas en `app/Services/`:

1. **`BillingReportService`**:
   - **`buildReportData(array $filters)`**: Construye el informe financiero completo filtrando por **Temporada** (año de celebración del evento), año/trimestre de cobro, método de pago, estado de cobro, marca y tipo de documento. Calcula KPIs de ingresos, señales, liquidaciones, **gasto de proveedores externos (Fotomatón/360º)** y **beneficio neto descontando proveedores**.
   - **`generateXlsx(array $reportData)`**: Genera un libro Excel (`.xlsx`) nativo con 4 pestañas (*Control por Eventos*, *Libro de Cobros*, *Pagos Fotomatón 360* y *Resumen e Impuestos*).
2. **`EvolutionWhatsAppService`**:
   - Conecta con Evolution API v2 para comprobar estado de instancia, normalizar teléfonos, enviar mensajes de texto y documentos PDF adjuntos (presupuestos, contratos, facturas/recibos) y registrar el historial en base de datos.
3. **`WhatsAppTemplateService`**:
   - Genera todos los textos personalizados de WhatsApp según el tipo de evento (boda, comunión, cumpleaños, empresa), marca y fase comercial (envío de presupuesto, recordatorio de seguimiento, confirmación de señal, envío de contrato, formulario musical, álbum QR).
4. **`ImapLeadFetcherService` y `BridalFairLeadService`**:
   - Conectan por IMAP al buzón de correo o procesan formularios de ferias nupciales para detectar nuevos leads de *Bodas.net* y *Zankyou*, extrayendo automáticamente nombre de los novios, teléfono, fecha del evento, finca y notas.
5. **`ContractTemplateService` y `DossierTemplateService`**:
   - Generan las cláusulas dinámicas del contrato (adaptando servicios contratados, horarios, importe acordado, señal y datos fiscales del cliente) y la estructura del dossier musical.
6. **`GeneralTariffDossierService`**:
   - Construye el catálogo general de tarifas y packs por marca para su exportación en PDF vertical o modo presentación horizontal (TV / Stand de feria).
7. **`FotosEventosQrService` y `QrPosterPdfService`**:
   - Sincronizan eventos con la plataforma externa `fotos.nunezandson.com` y renderizan los carteles PDF de alta resolución con código QR para las mesas del banquete.
8. **`MusicSearchService`, `SpotifyService`, `MusicListImportService`, `DjHistoryParserService` y `CloudMusicStorageService`**:
   - Búsqueda unificada de canciones (Spotify, iTunes, Deezer), importación de listas en texto/enlaces, parseo de sesiones DJ (`M3U`, `TXT`, `CSV`, `CUE`) y enlace con carpetas de música en la nube.
9. **`DocumentValidationService`, `PostalCodeService` y `VenueLookupService`**:
   - Validación oficial de DNI/NIE/CIF español (algoritmo módulo 23 sin modificar la entrada del usuario), autocompletado de municipio/provincia por código postal y directorio de fincas/restaurantes habituales con sus distancias y requisitos técnicos.
10. **`AppNotificationService` y `SystemHealthAuditService`**:
    - Gestión de notificaciones en tiempo real en la campana del panel y auditoría de estado de integraciones (base de datos, SMTP, IMAP, Evolution API, Spotify).

---

## 4. Controladores HTTP (`app/Http/Controllers/`)

- **`PdfController`**:
  - Generación y descarga de todos los documentos PDF y Excel del sistema:
    - `downloadQuote` / `downloadGuestQuote` (Presupuesto PDF con desglose de servicios).
    - `downloadContract` / `downloadGuestContract` (Contrato legal firmado o pendiente).
    - `downloadInvoice` (Factura con IVA o Recibo de pago).
    - `downloadPackingList` (Hoja de carga de material e inventario por evento).
    - `downloadMusicEscaleta` (Escaleta musical completa para cabina DJ).
    - `downloadPhotoQrPoster` / `downloadGuestPhotoQrPoster` (Cartel QR para invitados).
    - `downloadBillingReportPdf` / `downloadBillingReportExcel` (Informe ejecutivo de facturación e ingresos en PDF A4 horizontal y Excel `.xlsx`).
- **`WhatsAppWebhookController`**:
  - Recibe eventos `messages.upsert` desde Evolution API, asocia el mensaje entrante al evento correspondiente por número de teléfono y crea alertas instantáneas para el administrador.
- **`LeadWebhookController`**:
  - Endpoint REST para la recepción automatizada de leads externos (Make, Zapier, formularios web).
- **`CalendarFeedController`**:
  - Genera el feed iCal (`.ics`) suscribible desde Google Calendar / Apple Calendar con todos los eventos y citas confirmadas.
- **`SpotifyAuthController`**:
  - Flujo OAuth 2.0 con Spotify para conectar cuentas y exportar playlists directamente desde la ficha del evento.
- **`AuthController`**:
  - Inicio y cierre de sesión con redirección inteligente según el rol del usuario (`admin`, `dj`, `assistant`, `client`).

---

## 5. Componentes Livewire (`app/Livewire/`)

### 5.1. Panel de Administración (`app/Livewire/Admin/`)

| Componente | Ruta | Función Principal |
| :--- | :--- | :--- |
| **`Dashboard`** | `/admin/dashboard` | Panel principal con embudo de conversión, alertas de eventos sin respuesta, métricas por temporada (2027/2028), próximos eventos y accesos rápidos. |
| **`EventManager`** | `/admin/events` | Listado general de eventos, filtros por estado/marca, buscador instantáneo, creación rápida de eventos y autocompletado automático de horarios desde notas. |
| **`EventShow`** | `/admin/events/{event}` | Centro de mando integral de un evento: gestión de presupuestos y revisiones, contratos, facturas y recibos, reserva y pagos a proveedor de Fotomatón/360º, planificación horaria y montaje, escaleta musical, listas Spotify, chat de WhatsApp integrado, álbum QR de fotos, inventario y reuniones. |
| **`BillingManager`** | `/admin/billing` | Módulo de Facturación y Control de Ingresos: vista por eventos, libro cronológico de cobros y pestaña de pagos a proveedores (Fotomatón/360º), filtro rápido por **Temporada**, cálculo de gasto de proveedores y beneficio neto, edición rápida de cobros y exportación a Excel/PDF. |
| **`ClientManager`** | `/admin/clients` | CRM de clientes y parejas: alta/edición de datos fiscales, validación de DNI/NIE/CIF, autocompletado por código postal e historial de eventos por cliente. |
| **`CalendarManager`** | `/admin/calendar` | Calendario mensual interactivo de eventos, montajes en víspera y citas comerciales, más enlaces de suscripción iCal. |
| **`DjBoothMode`** | `/admin/events/{event}/booth` | Modo Cabina DJ en vivo: pantalla optimizada para actuación con cronograma en tiempo real, escaleta musical con reproductor de preescucha, cola de peticiones QR de invitados e importación de historial pinchado. |
| **`MusicManager`** | `/admin/music` | Gestión del catálogo musical recomendado, playlists por momentos de la boda y pistas de inspiración. |
| **`InventoryManager`** | `/admin/inventory` | Gestión del stock de equipos de sonido, iluminación, efectos y estado de mantenimiento. |
| **`TariffDossierManager`** | `/admin/tariff-dossier` | Editor visual del dossier de tarifas, packs de DJ, extras, Fotomatón/360º y modo pantalla TV para ferias de bodas. |
| **`AnalyticsManager`** | `/admin/analytics` | Estadísticas de canciones más pedidas por los novios e invitados y análisis de sesiones DJ. |
| **`UserManager`** | `/admin/users` | Gestión de usuarios del equipo (administradores, DJs, técnicos asistentes y accesos de clientes). |
| **`Settings`** | `/admin/settings` | Configuración general de marcas, tarifas del configurador `/presupuesto`, conexión WhatsApp Evolution API, buzón IMAP, SMTP, plantillas de contratos y auditoría de salud del sistema. |

### 5.2. Portal Público de Clientes e Invitados (`app/Livewire/Guest/`)

| Componente | Ruta Pública | Función Principal |
| :--- | :--- | :--- |
| **`QuoteCalculator`** | `/presupuesto` | Configurador interactivo público para que los clientes calculen su presupuesto online a medida, seleccionen extras (Fotomatón, 360º, horas extra, iluminación) y envíen su solicitud creando automáticamente el evento y borrador de propuesta. |
| **`QuoteResponse`** | `/propuesta/{token}/{quote?}` | Portal donde el cliente visualiza sus propuestas económicas con URL limpia, compara revisiones, descarga el dossier PDF y acepta (o desestima con motivo) su presupuesto. |
| **`ContractSign`** | `/contrato/{token}/firmar` | Página de revisión de datos fiscales (con validación de DNI/NIE/CIF y autocompletado por CP) y firma digital manuscrita del contrato en pantalla táctil o ratón. |
| **`EventForm`** | `/evento/{token}/musica` | Cuestionario musical interactivo para que los novios completen las canciones de cada momento de su boda con buscador integrado de Spotify/iTunes y preescucha. |
| **`PhotoQrDesigner`** | `/evento/{token}/cartel-qr` | Diseñador visual para que los novios personalicen el estilo, plantilla y textos del cartel QR del álbum de fotos de su boda y lo descarguen en PDF. |
| **`LiveRequests`** | `/evento/{token}/pedir` | Webapp móvil para invitados durante la fiesta: permite buscar canciones, enviarlas a la cabina del DJ en tiempo real y votar las peticiones de otros invitados. |

---

## 6. Flujos de Trabajo Principales

1. **Entrada de Solicitud y Horarios Automáticos:**
   - Cuando entra una solicitud desde `/presupuesto` o por IMAP/ficha, `Event::extractScheduleTimesFromText()` analiza las notas del cliente (ej. *"ceremonia a las 13:00, cóctel 14:30, banquete 16:00, 19:00 baile inicial"*) y pre-rellena automáticamente las horas de Ceremonia, Cóctel, Banquete y Baile en el planificador horario del evento.
2. **Ciclo de Vida de Presupuestos Múltiples:**
   - Un evento puede tener varias revisiones de presupuesto (`Quote`).
   - Si el cliente **acepta** una revisión en `/propuesta/{token}/{quote}`, esa propuesta queda bloqueada como confirmada, el evento pasa a `confirmed` y el resto de propuestas pendientes de ese mismo evento pasan automáticamente a desestimadas para evitar duplicidades.
   - Si el cliente **desestima** una revisión pero tiene otras propuestas activas, el evento se mantiene activo; además, una propuesta desestimada o aceptada no puede ser reactivada ni modificada unilateralmente por el cliente desde la web pública (solo desde el panel de administración).
3. **Control Financiero y Proveedores Externos (Fotomatón / 360º):**
   - `Event::getExternalProviderSummaryAttribute()` detecta automáticamente en las líneas del presupuesto activo el servicio e importe PVP de Fotomatón, Plataforma 360º, Pack Dúo o Mesa Dulce (`price * quantity`).
   - En `/admin/billing` y en la ficha del evento se controla tanto el cobro al cliente (Señal + Liquidación) como el pago al proveedor externo (Señal de reserva + Liquidación final), calculando en tiempo real el **Gasto de Proveedores** y el **Neto real por evento y por Temporada**.
