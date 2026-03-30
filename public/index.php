<?php
/**
 * Punto de entrada de la aplicación
 * Sistema TAMEP - Gestión Documental
 */

// Autoloader
require_once __DIR__ . '/autoload.php';

use TAMEP\Core\Router;
use TAMEP\Core\Session;

// Iniciar sesión
Session::start();

// Error reporting (desactivar en producción)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Crear router
$router = new Router();

// ====================================
// RUTAS PÚBLICAS
// ====================================

// Login
$router->get('/login', 'AuthController@showLogin');
$router->post('/login', 'AuthController@login');
$router->get('/logout', 'AuthController@logout');

// ====================================
// RUTAS PROTEGIDAS (requieren auth)
// ====================================

// Dashboard
$router->get('/', 'DashboardController@index', ['AuthMiddleware']);
$router->get('/inicio', 'DashboardController@index', ['AuthMiddleware']);

// Catalogación
$router->get('/catalogacion', 'CatalogacionController@index', ['AuthMiddleware']);
$router->get('/catalogacion/crear', 'CatalogacionController@crear', ['AuthMiddleware', 'WriteAccessMiddleware']);
$router->post('/catalogacion/guardar', 'CatalogacionController@guardar', ['AuthMiddleware', 'WriteAccessMiddleware']);
$router->get('/catalogacion/ver/{id}', 'CatalogacionController@ver', ['AuthMiddleware']);
$router->get('/catalogacion/editar/{id}', 'CatalogacionController@editar', ['AuthMiddleware', 'WriteAccessMiddleware']);
$router->post('/catalogacion/actualizar/{id}', 'CatalogacionController@actualizar', ['AuthMiddleware', 'WriteAccessMiddleware']);
$router->get('/catalogacion/eliminar/{id}', 'CatalogacionController@eliminar', ['AuthMiddleware', 'WriteAccessMiddleware']);
$router->post('/catalogacion/lote/actualizar', 'CatalogacionController@actualizarLote', ['AuthMiddleware', 'WriteAccessMiddleware']);

// Préstamos
$router->get('/prestamos', 'PrestamosController@index', ['AuthMiddleware']);
$router->get('/prestamos/nuevo', 'PrestamosController@nuevo', ['AuthMiddleware', 'WriteAccessMiddleware']);
$router->post('/prestamos/guardar-multiple', 'PrestamosController@guardarMultiple', ['AuthMiddleware', 'WriteAccessMiddleware']);
$router->get('/prestamos/crear', 'PrestamosController@crear', ['AuthMiddleware', 'WriteAccessMiddleware']);
$router->post('/prestamos/guardar', 'PrestamosController@guardar', ['AuthMiddleware', 'WriteAccessMiddleware']);
$router->get('/prestamos/ver/{id}', 'PrestamosController@ver', ['AuthMiddleware']);
$router->post('/prestamos/actualizarEstados', 'PrestamosController@actualizarEstados', ['AuthMiddleware', 'WriteAccessMiddleware']); // New Route
$router->get('/prestamos/devolver/{id}', 'PrestamosController@devolver', ['AuthMiddleware', 'WriteAccessMiddleware']);
$router->get('/prestamos/exportar-pdf/{id}', 'PrestamosController@exportarPdf', ['AuthMiddleware']);
$router->get('/prestamos/pdf-edicion/{id}', 'PrestamosController@pdfEdicion', ['AuthMiddleware']);
$router->get('/prestamos/exportar-excel/{id}', 'PrestamosController@exportarExcel', ['AuthMiddleware']);
$router->post('/prestamos/agregarDetalle', 'PrestamosController@agregarDetalle', ['AuthMiddleware', 'WriteAccessMiddleware']);
$router->get('/prestamos/quitarDetalle/{id}', 'PrestamosController@quitarDetalle', ['AuthMiddleware', 'WriteAccessMiddleware']);
$router->get('/prestamos/importar', 'PrestamosController@vistaImportar', ['AuthMiddleware', 'WriteAccessMiddleware']);
$router->post('/prestamos/importar/procesar', 'PrestamosController@procesarImportacion', ['AuthMiddleware', 'WriteAccessMiddleware']);
$router->get('/prestamos/procesar/{id}', 'PrestamosController@procesar', ['AuthMiddleware', 'WriteAccessMiddleware']);
$router->get('/prestamos/editar/{id}', 'PrestamosController@editar', ['AuthMiddleware', 'WriteAccessMiddleware']);
$router->post('/prestamos/actualizarEncabezado/{id}', 'PrestamosController@actualizarEncabezado', ['AuthMiddleware', 'WriteAccessMiddleware']);
$router->post('/prestamos/confirmarProceso', 'PrestamosController@confirmarProceso', ['AuthMiddleware', 'WriteAccessMiddleware']);
$router->get('/prestamos/revertirProceso/{id}', 'PrestamosController@revertirProceso', ['AuthMiddleware', 'WriteAccessMiddleware']);
$router->get('/prestamos/eliminar/{id}', 'PrestamosController@eliminar', ['AuthMiddleware', 'WriteAccessMiddleware']);

// Reportes
$router->get('/reportes', 'ReportesController@graficos', ['AuthMiddleware']); // Default to graphics
$router->get('/reportes/graficos', 'ReportesController@graficos', ['AuthMiddleware']);
$router->get('/reportes/prestamos', 'ReportesController@prestamos', ['AuthMiddleware']);
$router->get('/reportes/no-disponibles', 'ReportesController@noDisponibles', ['AuthMiddleware']);
$router->get('/reportes/auditorias', 'ReportesController@auditorias', ['AuthMiddleware']);
$router->post('/reportes/exportar-pdf-seleccion', 'ReportesController@exportarPdfSeleccion', ['AuthMiddleware']);
$router->post('/reportes/exportar-excel-seleccion', 'ReportesController@exportarExcelSeleccion', ['AuthMiddleware']);
$router->post('/reportes/auditorias/exportar-seleccion', 'ReportesController@exportarAuditoriaSeleccion', ['AuthMiddleware']);

// Contenedores
$router->get('/contenedores', 'ContenedoresController@index', ['AuthMiddleware']);
$router->get('/contenedores/crear', 'ContenedoresController@crear', ['AuthMiddleware', 'WriteAccessMiddleware']);
$router->post('/contenedores/guardar', 'ContenedoresController@guardar', ['AuthMiddleware', 'WriteAccessMiddleware']);
$router->post('/contenedores/guardar-rapido', 'ContenedoresController@guardarRapido', ['AuthMiddleware', 'WriteAccessMiddleware']);
$router->get('/contenedores/ver/{id}', 'ContenedoresController@ver', ['AuthMiddleware']);
$router->get('/contenedores/editar/{id}', 'ContenedoresController@editar', ['AuthMiddleware', 'WriteAccessMiddleware']);
$router->post('/contenedores/actualizar/{id}', 'ContenedoresController@actualizar', ['AuthMiddleware', 'WriteAccessMiddleware']);
$router->get('/contenedores/eliminar/{id}', 'ContenedoresController@eliminar', ['AuthMiddleware', 'WriteAccessMiddleware']);
$router->get('/contenedores/api-buscar', 'ContenedoresController@apiBuscar', ['AuthMiddleware']);
$router->post('/contenedores/actualizar-ubicacion-masiva', 'ContenedoresController@actualizarUbicacionMasiva', ['AuthMiddleware', 'WriteAccessMiddleware']);
$router->post('/contenedores/api-crear-ubicacion', 'ContenedoresController@apiCrearUbicacion', ['AuthMiddleware', 'WriteAccessMiddleware']);

// Usuarios (solo administrador)
$router->get('/admin/usuarios', 'UsuariosController@index', ['AuthMiddleware']);
$router->get('/admin/usuarios/crear', 'UsuariosController@crear', ['AuthMiddleware', 'WriteAccessMiddleware']);
$router->post('/admin/usuarios/guardar', 'UsuariosController@guardar', ['AuthMiddleware', 'WriteAccessMiddleware']);
$router->get('/admin/usuarios/editar/{id}', 'UsuariosController@editar', ['AuthMiddleware', 'WriteAccessMiddleware']);
$router->post('/admin/usuarios/actualizar/{id}', 'UsuariosController@actualizar', ['AuthMiddleware', 'WriteAccessMiddleware']);
$router->get('/admin/usuarios/eliminar/{id}', 'UsuariosController@eliminar', ['AuthMiddleware', 'WriteAccessMiddleware']);
$router->get('/admin/usuarios/reset-password/{id}', 'UsuariosController@resetPassword', ['AuthMiddleware', 'WriteAccessMiddleware']);

// Herramientas
$router->get('/herramientas/control-amarros', 'HerramientasController@controlAmarros', ['AuthMiddleware']);
$router->get('/herramientas/varita-magica', 'HerramientasController@varitaMagica', ['AuthMiddleware', 'WriteAccessMiddleware']);

// Normalización (solo admin) - TODO: Crear NormalizacionController
// $router->get('/normalizacion', 'NormalizacionController@index', ['AuthMiddleware']);

// Configuración
$router->get('/configuracion/tipos', 'ConfiguracionController@tipos', ['AuthMiddleware']);
$router->get('/configuracion/tipos/crear', 'ConfiguracionController@crearTipo', ['AuthMiddleware', 'WriteAccessMiddleware']); // Fix internal redirect to this route
$router->get('/configuracion/crearTipo', 'ConfiguracionController@crearTipo', ['AuthMiddleware', 'WriteAccessMiddleware']); // Alias for controller redirect
$router->post('/configuracion/tipos/guardar', 'ConfiguracionController@guardarTipo', ['AuthMiddleware', 'WriteAccessMiddleware']);
$router->get('/configuracion/tipos/editar/{id}', 'ConfiguracionController@editarTipo', ['AuthMiddleware', 'WriteAccessMiddleware']);
$router->post('/configuracion/tipos/actualizar/{id}', 'ConfiguracionController@actualizarTipo', ['AuthMiddleware', 'WriteAccessMiddleware']);
$router->get('/configuracion/tipos/eliminar/{id}', 'ConfiguracionController@eliminarTipo', ['AuthMiddleware', 'WriteAccessMiddleware']);

$router->get('/configuracion/password', 'ConfiguracionController@password', ['AuthMiddleware']);
$router->post('/configuracion/password/actualizar', 'ConfiguracionController@updatePassword', ['AuthMiddleware']);

// Ejecutar router
$router->dispatch();
