# Arquitectura Técnica del Sistema

El Sistema de Gestión de Archivos TAMEP implementa una arquitectura **MVC (Modelo-Vista-Controlador)** personalizada en PHP nativo, diseñada para ser ligera, eficiente y fácil de auditar.

## 1. Patrón de Diseño MVC

### Controlador (Controller)
Ubicación: `src/Controllers/`

Es el intermediario entre la Vista y el Modelo.
- Recibe la solicitud del `Router`.
- Valida los datos de entrada (`$_GET`, `$_POST`).
- Llama a los métodos del Modelo para obtener o persistir datos.
- Carga una Vista, pasándole los datos necesarios.

### Modelo (Model)
Ubicación: `src/Models/`

Encapsula la lógica de negocio y el acceso a datos.
- Cada modelo extiende de `BaseModel`.
- Utiliza la clase `Database` para ejecutar consultas SQL seguras (Prepared Statements).
- Representa una entidad de la base de datos (ej. `Documento`, `Usuario`).

### Vista (View)
Ubicación: `views/`

Archivos PHP/HTML encargados de la presentación.
- Organizadas por módulos (ej. `views/prestamos/`, `views/dashboard/`).
- Utilizan layouts (`views/layouts/`) para mantener la consistencia visual (encabezados, menús).
- Reciben variables del controlador para mostrar información dinámica.

---

## 2. Ciclo de Vida de una Petición (Request Lifecycle)

1.  **Entrada (`index.php`)**:
    Todas las peticiones son redirigidas por el servidor web (Apache/.htaccess) hacia `public/index.php`.

2.  **Bootstrapping**:
    - Se carga el `autoload.php` para incluir clases automáticamente.
    - Se inicia la sesión con `Session::start()`.
    - Se instancian los componentes del núcleo (`Router`, `Database` implícitamente).

3.  **Enrutamiento (`Router::dispatch`)**:
    - El `Router` analiza la URL solicitada.
    - Compara la URL contra las rutas definidas en `public/index.php`.
    - Si encuentra coincidencia, identifica el Controlador, Método y Middlewares asociados.

4.  **Middleware Pipeline**:
    - Antes de ejecutar el controlador, se procesan los middlewares.
    - **Ejemplo**: `AuthMiddleware` verifica si el usuario tiene sesión activa. Si no, redirige a `/login` e interrumpe el ciclo.

5.  **Ejecución del Controlador**:
    - Se crea una instancia del Controlador (ej. `PrestamosController`).
    - Se ejecuta el método correspondiente (ej. `crear()`).
    - El método procesa la lógica y prepara los datos.

6.  **Renderizado**:
    - El controlador hace `require` de la vista correspondiente.
    - La vista genera el HTML final que se envía al navegador.

---

## 3. Componentes del Núcleo (`src/Core`)

### `Router`
Gestiona el mapeo de URLs a funciones. Soporta parámetros dinámicos (ej. `/prestamos/ver/{id}`).
- Usa Expresiones Regulares para validar rutas.
- Soporta métodos HTTP (GET, POST).

### `Database`
Wrapper alrededor de `PDO` (PHP Data Objects).
- Implementa el patrón **Singleton** para mantener una única conexión a la base de datos.
- Métodos facilitadores: `query`, `fetchOne`, `fetchAll`, `lastInsertId`.
- Carga configuración desde `config/database.php`.

### `Session`
Wrapper para manejo seguro de `$_SESSION`.
- Métodos para login, logout, verificar autenticación y mensajes flash.

---

## 4. Seguridad Implementada

### Autenticación y Autorización
- **AuthMiddleware**: Bloquea el acceso a rutas protegidas para visitantes no autenticados.
- **WriteAccessMiddleware**: Restringe acciones de modificación (POST) a usuarios con permisos de escritura, protegiendo contra usuarios de "solo lectura".

### Protección de Datos
- **SQL Injection**: Prevenida totalmente mediante el uso de Prepared Statements en `Database.php`.
- **XSS**: Las vistas deben escapar la salida (aunque actualmente se confía en el HTML generado internamente).
- **Passwords**: Hashing seguro utilizando `password_hash()` (Bcrypt).
- **Cache Control**: Cabeceras HTTP para prevenir que el navegador cachee páginas protegidas (`Cache-Control: no-store`).

---

## 5. Estructura de Directorios Detallada

A continuación se detalla la ubicación y propósito de los archivos clave del sistema:

### `config/` - Configuración
- `app.php`: Configuración general (URL base, nombre de app).
- `database.php`: Credenciales de conexión a base de datos (Soporta variables de entorno).
- `database_clevercloud.php`: Configuración específica para despliegue en Clever Cloud.

### `public/` - Document Root
- `index.php`: **Punto de Entrada**. Inicia sesión, carga `autoload.php` y despacha el `Router`.
- `autoload.php`: Carga automática de clases PSR-4.
- `assets/`: Archivos estáticos (CSS, JS, Imágenes).
- `debug_status.php`: Script de utilidad para verificar estado del servidor.

### `src/` - Núcleo de la Aplicación

#### `src/Core/` - Componentes Base
- `Router.php`: Maneja el enrutamiento de URLs a Controladores.
- `Database.php`: Singleton para conexión PDO segura a MySQL.
- `Session.php`: Abstracción para manejo de sesiones y mensajes flash.
- `AuditLogger.php`: Servicio para registrar acciones en la tabla de auditoría.

#### `src/Controllers/` - Lógica de Negocio
- `BaseController.php`: Clase padre. Maneja carga de vistas, redirecciones y verificación de auth.
- `AuthController.php`: Login, Logout y manejo de sesión.
- `DashboardController.php`: Página principal, estadísticas y resúmenes.
- `DocumentosController.php`: Búsqueda y gestión de documentos.
- `PrestamosController.php`: Gestión completa del ciclo de préstamos y devoluciones.
- `UsuariosController.php`: ABM de usuarios (Solo Admin).
- `ReportesController.php`: Generación de reportes PDF/Excel y gráficos.
- `ContenedoresController.php`: Gestión de Cajas, Amarros y Libros.
- `ConfiguracionController.php`: Gestión de catálogos (Tipos, Ubicaciones).

#### `src/Models/` - Acceso a Datos (ORM Básico)
- `BaseModel.php`: Clase padre con métodos comunes (`find`, `all`, `create`, `update`, `delete`).
- `Documento.php`: Lógica compleja de búsqueda de documentos.
- `Prestamo.php` & `PrestamoHeader.php`: Gestión de préstamos (Cabecera y Detalle).
- `User.php` / `Usuario.php`: Autenticación y datos de usuario.
- `ContenedorFisico.php`: Lógica de ubicaciones físicas.
- `Auditoria.php`: Registro de logs.

#### `src/Middleware/` - Filtros de Petición
- `AuthMiddleware.php`: Verifica que el usuario haya iniciado sesión.
- `WriteAccessMiddleware.php`: Verifica permisos de escritura (POST/PUT).
- `RoleMiddleware.php`: Verifica roles específicos (ej. Admin).

### `views/` - Capa de Presentación

#### `views/layouts/`
- `main.php`: Layout principal. Contiene `<head>`, navbar, sidebar y footer. Envuelve a las demás vistas.

#### `views/auth/`
- `login.php`: Formulario de inicio de sesión.

#### `views/dashboard/`
- `index.php`: Panel principal con tarjetas de estadísticas.

#### `views/documentos/`
- `index.php`: Buscador avanzado de documentos.
- `crear.php`: Formulario de alta de documentos.
- `editar.php`: Edición de metadatos.

#### `views/prestamos/`
- `nuevo.php` / `crear.php`: Crear nueva solicitud de préstamo.
- `index.php`: Listado de préstamos activos e históricos.
- `detalle.php`: Vista individual de un préstamo.
- `pdf_report.php`: Plantilla para generación de PDF.

#### `views/usuarios/`
- `index.php`: Tabla de gestión de usuarios.
- `crear.php` / `editar.php`: Formularios de usuario.

#### `views/partials/`
- `sidebar.php`: Menú lateral de navegación.
- `table.php`: Componente reutilizable para tablas.

### `storage/`
- `logs/`: Archivos de log del sistema (errores, emails).
