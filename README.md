# Sistema de Gestión de Archivos TAMEP

Sistema de gestión de archivos contables para TAMEP, desarrollado para digitalizar y administrar el registro de documentación contable del período 2007-2026.

## 📋 Descripción

Este sistema permite gestionar más de 2 millones de registros de documentos contables distribuidos en 8 categorías diferentes:

- **Registro Diario** - Comprobantes diarios de contabilidad
- **Registro de Ingreso** - Comprobantes de ingreso
- **Registro de Egreso (CEPS)** - Comprobantes de egreso
- **Preventivos** - Preventivos de tesorería
- **Asientos Manuales** - Asientos manuales de contabilidad
- **Diarios de Apertura** - Comprobantes de diarios de apertura
- **Registro de Traspaso** - Comprobantes de traspaso
- **Hojas de Ruta - Diarios** - Detalle de hojas de ruta

## 🎯 Funcionalidades Principales

- ✅ Búsqueda avanzada de documentos por gestión, número, tipo y ubicación
- ✅ Control de ubicación física de documentos (amarros/libros)
- ✅ Sistema de préstamos de documentos a unidades solicitantes
- ✅ Registro de estados: disponible, prestado, perdido
- ✅ Gestión de ubicaciones y unidades organizacionales
- ✅ Sistema de usuarios con roles (Administrador, Usuario)
- ✅ Historial de préstamos y devoluciones
- ✅ Estadísticas y reportes

## 🛠️ Requisitos Técnicos

### Base de Datos
- **MySQL** o **MariaDB** (Producción y Desarrollo)

### Backend
- **PHP 8.0+**
- **Composer** (Gestión de dependencias)

### Frontend
- HTML5, CSS3, JavaScript
- Bootstrap 5

## 📁 Estructura del Proyecto

El proyecto sigue una arquitectura **MVC (Modelo-Vista-Controlador)** personalizada:

```
/
├── config/             # Configuración (Base de datos, App)
├── docs/               # Documentación detallada
│   ├── ARCHITECTURE.md # Arquitectura técnica
│   └── DATABASE.md     # Esquema de base de datos
|   └── full_schema.md  # Esquema completo de base de datos
├── public/             # Document Root (index.php, css, js, img)
├── src/                # Código Fuente PHP
│   ├── Controllers/    # Lógica de negocio
│   ├── Core/           # Núcleo (Router, Database, Session)
│   ├── Middleware/     # Middlewares (Auth, Permisos)
│   ├── Models/         # Modelos de datos (ORM básico)
│   └── Services/       # Servicios auxiliares
├── storage/            # Archivos temporales, logs, uploads
├── vendor/             # Librerías (PHPSpreadsheet, PHPMailer)
├── views/              # Vistas y plantillas HTML
│   ├── layouts/        # Estructuras comunes (header, footer)
│   └── [modulos]/      # Vistas específicas por módulo
├── index.php           # Redirección a public/
└── composer.json       # Definición de dependencias
```

## 📚 Documentación Detallada

- [**Arquitectura Técnica**](docs/ARCHITECTURE.md): Explicación profunda del patrón MVC, ciclo de vida de peticiones y estructura de código.
- [**Base de Datos**](docs/DATABASE.md): Esquema completo de tablas, columnas y relaciones.

## 🚀 Instalación y Despliegue

### 1. Prerrequisitos
Asegúrese de tener instalado PHP 8.0+, Composer y MySQL.

### 2. Instalación de Dependencias
```bash
composer install
```

### 3. Configuración
1.  Configure las credenciales de base de datos en `config/database.php`.
2.  (Opcional) Ajuste la configuración general en `config/app.php`.

### 4. Base de Datos
Importe el esquema de la base de datos (solicitar al administrador si no se encuentra en el repositorio) en su servidor MySQL.

### 5. Iniciar Servidor
Para desarrollo local:
```bash
php -S localhost:8000 -t public
```
Para producción, configure Apache/Nginx para que el *Document Root* apunte a la carpeta `/public`.

### 6. Acceso
- URL: `http://localhost:8000`
- **Credenciales por defecto**: (Consultar con el administrador del sistema)

## 📊 Uso del Sistema

### Búsqueda de Documentos
1. Navegar a "Buscar Documentos"
2. Filtrar por tipo, gestión, número o ubicación
3. Ver detalles del documento encontrado

### Registrar Préstamo
1. Buscar el documento
2. Clic en "Registrar Préstamo"
3. Seleccionar unidad solicitante
4. Indicar fecha de devolución esperada
5. Guardar

### Registrar Devolución
1. Ir a "Préstamos Activos"
2. Seleccionar el préstamo
3. Clic en "Registrar Devolución"
4. Confirmar

### Consultar Estadísticas
1. Navegar a "Reportes"
2. Seleccionar tipo de reporte
3. Filtrar por período o tipo de documento
4. Exportar o visualizar

## 🔐 Seguridad

- Contraseñas hasheadas con bcrypt
- Prevención de SQL injection mediante prepared statements
- Validación de entrada en cliente y servidor
- Control de acceso basado en roles
- Sesiones seguras

## 👥 Contacto

Para soporte o consultas sobre el sistema, contactar al área de Sistemas de TAMEP.

---

**Versión**: 1.0
**Desarrollado para**: TAMEP - Policía Boliviana
