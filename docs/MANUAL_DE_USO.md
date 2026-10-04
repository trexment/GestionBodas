# 📖 MANUAL COMPLETO DE USO DE LA APLICACIÓN
## Sistema de Gestión Integral para Eventos Musicales y Bodas

---

## 📑 Índice de Contenidos
1. [Introducción y Filosofía Multi-Marca](#1-introducción-y-filosofía-multi-marca)
2. [Acceso al Sistema y Seguridad](#2-acceso-al-sistema-y-seguridad)
3. [Panel de Control (Dashboard)](#3-panel-de-control-dashboard)
4. [Gestión de Eventos (Ciclo de Vida Completo)](#4-gestión-de-eventos-ciclo-de-vida-completo)
   - 4.1. Creación de un Evento
   - 4.2. Estados del Evento y su Significado
   - 4.3. Datos Generales y Contactos de Finca/Proveedores
   - 4.4. Presupuestos y Fianza de Reserva
   - 4.5. Facturación Automática
   - 4.6. Contratos y Firma Digital con Auditoría
   - 4.7. Actas de Reunión con Clientes
   - 4.8. Escaleta Musical y Peticiones de los Novios
   - 4.9. Importación de Sesiones de DJ (Engine DJ, Rekordbox, M3U)
   - 4.10. Asignación de Equipamiento y Direccionamiento DMX
   - 4.11. Generación y Descarga de Documentos PDF
5. [Modo Cabina DJ en Directo (DJ Booth Live Mode)](#5-modo-cabina-dj-en-directo-dj-booth-live-mode)
6. [Portal de Peticiones en Vivo para Invitados (Código QR)](#6-portal-de-peticiones-en-vivo-para-invitados-código-qr)
7. [Calculadora Pública de Presupuestos (Web / Embed)](#7-calculadora-pública-de-presupuestos-web--embed)
8. [Automatización de Leads por Correo Electrónico](#8-automatización-de-leads-por-correo-electrónico)
9. [Gestión de Clientes y Directorio](#9-gestión-de-clientes-y-directorio)
10. [Calendario y Sincronización Externa (iCal)](#10-calendario-y-sincronización-externa-ical)
11. [Inventario y Material Técnico](#11-inventario-y-material-técnico)
12. [Biblioteca Musical y Servicios Cloud](#12-biblioteca-musical-y-servicios-cloud)
13. [Analítica y Métricas de Rendimiento](#13-analítica-y-métricas-de-rendimiento)
14. [Configuración del Sistema y Ajustes Globales](#14-configuración-del-sistema-y-ajustes-globales)

---

## 1. Introducción y Filosofía Multi-Marca

La aplicación está diseñada para gestionar de forma centralizada todas las operaciones de una empresa de eventos musicales y DJ, con soporte nativo para **dos marcas comerciales independientes**:

- **JAVNXDJ**: Orientada a eventos de autor, sesiones de DJ exclusivas, música electrónica y eventos corporativos.
- **NÚÑEZ & SON (Eventos Musicales)**: Orientada a bodas integrales, ceremonias, cócteles, banquetes y montajes de sonido/iluminación completos.

Cada evento, presupuesto, contrato, correo y PDF adopta automáticamente la identidad visual, colores, logotipos, datos fiscales y cuentas bancarias de la marca seleccionada.

---

## 2. Acceso al Sistema y Seguridad

- **URL de acceso**: `https://tudominio.com/login`
- **Inicio de sesión**: Mediante correo electrónico y contraseña.
- **Roles de usuario**:
  - **Administrador**: Control total sobre eventos, finanzas, presupuestos, contratos, configuración, inventario y usuarios.
  - **Staff / DJ Asistente**: Acceso al calendario, detalles técnicos de eventos asignados y modo cabina en directo.
- **Tokens de acceso público seguro**: Los novios, invitados y DJs auxiliares pueden interactuar con partes específicas de la app mediante enlaces únicos con token seguro (sin necesidad de crear cuenta ni recordar contraseñas).

---

## 3. Panel de Control (Dashboard)

Al iniciar sesión, el Dashboard ofrece una visión panorámica e inmediata del negocio:
- **Resumen Financiero**: Ingresos previstos, fianzas cobradas, facturación pendiente y conversión de presupuestos.
- **Próximos Eventos**: Lista interactiva de los eventos más inmediatos con fechas, horarios, lugar y estado.
- **Accesos Rápidos**: Crear evento, nuevo presupuesto, revisar contratos pendientes de firma y comprobar el estado del material.
- **Alertas de Acción Rápida**: Solicitudes de información sin responder, pagos de fianza próximos a vencer o escaletas musicales incompletas.

---

## 4. Gestión de Eventos (Ciclo de Vida Completo)

La sección **Eventos** (`/admin/events`) es el núcleo del sistema. Permite filtrar por estado, marca, año y buscar por nombres de novios, finca o localidad.

### 4.1. Creación de un Evento
Al pulsar en **"Nuevo Evento"**, se pueden introducir los datos clave:
- **Marca**: JAVNXDJ o NÚÑEZ & SON.
- **Tipo de evento**: Boda, Corporativo, Cumpleaños, Fiesta privada, etc.
- **Cliente**: Selección de cliente existente o creación sobre la marcha.
- **Fechas y Horarios**: Fecha del evento, hora de inicio/fin, hora prevista de montaje y hora límite de barra libre.
- **Ubicación**: Nombre de la finca/restaurante, dirección y código postal.

### 4.2. Estados del Evento y su Significado
Cada evento progresa a través de una serie de estados bien definidos:
1. **Nuevo**: Lead o solicitud inicial recibida.
2. **Presupuestado**: Se ha elaborado y enviado una propuesta económica.
3. **No responde**: El cliente ha recibido información pero no ha dado respuesta tras el seguimiento.
4. **Aprobado**: El cliente ha aceptado la propuesta (o pagado la fianza).
5. **Planificando**: Se está preparando la escaleta musical, detalles técnicos y logística.
6. **Listo**: Todo el material, personal y música están 100% coordinados para el día D.
7. **En curso**: El evento se está celebrando en este momento.
8. **Finalizado**: El evento ha concluido con éxito.
9. **Rechazado**: El cliente declinó la propuesta formalmente por presupuesto, fecha no disponible u otro motivo.
10. **Cancelado**: El evento fue anulado tras haber estado aprobado previamente.

### 4.3. Datos Generales y Contactos de Finca/Proveedores
Dentro de la ficha del evento (`/admin/events/{id}`):
- **Pestaña Resumen**: Muestra la línea de tiempo, horas clave (ceremonia, cóctel, banquete, barra libre), y teléfonos de contacto directo (maître, fotógrafo, wedding planner, responsable de la finca).
- **Enlace al Formulario de los Novios**: Enlace público único (`/guest/form/{token}`) para que la pareja rellene cómodamente sus datos y elecciones musicales desde el móvil.

### 4.4. Presupuestos y Fianza de Reserva
- **Líneas de servicio**: Añade equipos de sonido, iluminación ambiental, horas extra, microfonía, cabinas especiales o efectos especiales (fuego frío, humo bajo).
- **Cálculo de impuestos**: Desglose automático de Base Imponible + IVA (21%) o exento.
- **Fianza / Señal de Reserva**: Porcentaje o importe fijo de reserva (habitualmente 150€ - 300€).
- **Control de Pago**: Casilla para marcar la fianza como **"Pagada"** registrando la fecha del cobro.
- **Exportación en PDF**: Descarga instantánea de la propuesta formal con el diseño corporativo de la marca.

### 4.5. Facturación Automática
- Desde la pestaña de finanzas se puede convertir cualquier presupuesto en **Factura Oficial Proforma o Definitiva**.
- Numeración correlativa automática por serie y año.
- Descarga de factura en PDF con desglose fiscal y datos bancarios para transferencia o Bizum.

### 4.6. Contratos y Firma Digital con Auditoría
- **Generación automática**: El contrato extrae todas las cláusulas, datos de los novios, servicios contratados, importes y fechas.
- **Portal de Firma Online (`/contrato/{token}`)**:
  - Los novios acceden desde su teléfono u ordenador.
  - Leen las condiciones y dibujan su firma con el dedo o ratón.
  - El sistema registra la **dirección IP, navegador, fecha y hora exacta** de la firma como prueba de auditoría legal.
- **Descarga del Contrato Firmado en PDF**: Documento listo para archivar con las firmas incrustadas y el sello digital.

### 4.7. Actas de Reunión con Clientes
- Registro de todas las llamadas, videollamadas o reuniones presenciales con los clientes.
- Historial cronológico de notas, acuerdos tomados, cambios de última hora y compromisos adquiridos.

### 4.8. Escaleta Musical y Peticiones de los Novios
- **Momentos Especiales**:
  - Entrada a la Ceremonia (Novio / Novia).
  - Salida de la Ceremonia.
  - Entrada al Banquete.
  - Momento Tarta Nupcial.
  - Entrega de Ramo / Regalos especiales.
  - Baile Nupcial (Primer baile).
  - Apertura de Barra Libre.
- **Lista de Canciones Imprescindibles ("Must Play") y Prohibidas ("Do Not Play")**.
- **Descarga de la Escaleta Musical en PDF**: Guía impresa o digital para el DJ durante el evento.

### 4.9. Importación de Sesiones de DJ (Engine DJ, Rekordbox, M3U)
Permite guardar el historial real de lo que sonó en la cabina durante el evento:
1. En la pestaña **Música**, pulsa en **"🎧 Importar Sesión DJ (Engine / M3U)"**.
2. Sube el archivo CSV exportado desde **Denon Engine DJ**, **Pioneer Rekordbox**, **Serato**, una lista **M3U/M3U8** o pega la lista de temas en texto.
3. El sistema procesa todos los temas, calcula duraciones, BPMs y **cruza automáticamente la lista con las peticiones de los novios e invitados**.
4. Las canciones que coincidieron se marcan en verde como **"Sonada en directo"**.
5. Puedes descargar un **Tracklist Oficial en PDF** con toda la sesión para entregarlo como recuerdo a la pareja.

### 4.10. Asignación de Equipamiento y Direccionamiento DMX
- Pestaña **Material / Equipos**: Selecciona el material necesario del inventario (altavoces, subgraves, puentes de luces, cabezas móviles, máquinas de efectos).
- **Gestión DMX**: Asignación de canal de inicio DMX, universo y modo de canales para preparar el software de luces (SoundSwitch, Daslight, etc.).
- **Packing List en PDF**: Hoja de carga y comprobación de almacén para que el equipo técnico cargue la furgoneta sin olvidar ningún cable o equipo.

### 4.11. Generación y Descarga de Documentos PDF
Todos los documentos cuentan con un diseño gráfico profesional según la marca:
- `Presupuesto PDF`
- `Contrato Firmado PDF`
- `Factura Oficial PDF`
- `Packing List de Equipos PDF`
- `Escaleta Musical de Momentos PDF`
- `Historial de Sesión DJ PDF`

---

## 5. Modo Cabina DJ en Directo (DJ Booth Live Mode)

- **Acceso**: `/admin/events/{id}/live` (o enlace directo con token para tablets en cabina).
- **Interfaz Oscura de Alto Contraste**: Diseñada para visualizarse perfectamente en cabinas con poca luz.
- **Funciones en vivo**:
  - Consulta rápida de momentos especiales y canciones clave.
  - Visualización en tiempo real de las **peticiones enviadas por los invitados desde la pista**.
  - Botones de acción rápida para marcar canciones como *Pinchada*, *Pendiente* o *Descartada*.
  - Vista del cronograma del evento.

---

## 6. Portal de Peticiones en Vivo para Invitados (Código QR)

- **Acceso**: `/peticiones/{token}` (se puede generar un código QR para colocar en las mesas o la barra).
- **Experiencia de los invitados**:
  - Buscan su canción favorita mediante el motor integrado de Spotify / Apple Music.
  - Escriben su nombre o una dedicatoria ("De parte de los amigos del novio").
  - La petición aparece al instante en la pantalla de cabina del DJ.

---

## 7. Calculadora Pública de Presupuestos (Web / Embed)

- **Acceso**: `/presupuesto`
- **Uso**: Permite a futuros clientes calcular una estimación de su boda o evento directamente desde la web.
- **Características**:
  - Selección interactiva de paquetes y extras (iluminación, microfonía, horas adicionales).
  - Introducción del código postal: el sistema calcula automáticamente los kilómetros de desplazamiento y suma el coste logístico exacto.
  - Formulario de contacto final que crea automáticamente un nuevo evento/lead en el panel de administración.

---

## 8. Automatización de Leads por Correo Electrónico

El comando de fondo `php artisan emails:fetch-leads` revisa periódicamente los buzones configurados (por ejemplo los correos de **bodas.net**, formularios web de contacto, etc.):
- Conecta por protocolo seguro IMAP SSL a los buzones de `nunezandson.com` y `javnxdj.com`.
- Extrae automáticamente el nombre de los novios, fecha de la boda, lugar, teléfono y notas.
- Crea el evento en estado **Nuevo**, asigna la marca correcta y notifica en el panel.

---

## 9. Gestión de Clientes y Directorio

- Sección **Clientes** (`/admin/clients`): Ficha de cada cliente con historial completo de eventos pasados y futuros, presupuestos emitidos, facturas pagadas y notas de contacto.
- Búsqueda por nombre, DNI, teléfono, email o población.

---

## 10. Calendario y Sincronización Externa (iCal)

- Sección **Calendario** (`/admin/calendar`): Vista mensual, semanal y diaria de todos los eventos y fechas de montaje.
- **Feed iCal Privado (`/calendar/feed/{token}.ics`)**:
  - Permite suscribirse desde **Google Calendar**, **Apple Calendar (iPhone/Mac)** o **Microsoft Outlook**.
  - Cualquier evento nuevo, cambio de hora o aplazamiento se sincroniza automáticamente en tu teléfono móvil.

---

## 11. Inventario y Material Técnico

- Sección **Inventario** (`/admin/inventory`):
  - Catálogo de altavoces, iluminación, cableado, microfonía y estructuras.
  - Control de unidades en stock, potencia en vatios (W), peso y estado de conservación.
  - Configuración técnica de luces: universos DMX, canales ocupados y modos de funcionamiento.

---

## 12. Biblioteca Musical y Servicios Cloud

- Sección **Música** (`/admin/music`):
  - Base de datos de temas musicales categorizados por géneros y momentos.
  - Conexión con Spotify API para enriquecer metadatos (portadas, BPM, tono, popularidad).
  - Reproductor y preescucha de audio integrado.
  - Soporte para almacenamiento y streaming de audios locales o en la nube (Google Drive).

---

## 13. Analítica y Métricas de Rendimiento

- Sección **Analítica** (`/admin/analytics`):
  - Gráficas de eventos por mes y estacionalidad.
  - Tasa de conversión de presupuestos a eventos confirmados.
  - Rendimiento y facturación por marca comercial.
  - Estadísticas de géneros y canciones más solicitadas en bodas.

---

## 14. Configuración del Sistema y Ajustes Globales

- Sección **Ajustes** (`/admin/settings`):
  - Configuración de datos fiscales, CIF, direcciones y cuentas bancarias de cada marca.
  - Configuración del servidor de correo SMTP para el envío automático de presupuestos y contratos.
  - Personalización de plantillas de mensajes para WhatsApp y correos electrónicos.
  - Integración de claves de API (Spotify Client ID/Secret, Google Drive, etc.).
