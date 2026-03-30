<?php
/**
 * Clase Router - Manejo de rutas
 * 
 * @package TAMEP\Core
 */

namespace TAMEP\Core;

class Router
{
    private $routes = [];
    private $middlewares = [];
    
    public function get($path, $handler, $middleware = [])
    {
        $this->addRoute('GET', $path, $handler, $middleware);
    }
    
    public function post($path, $handler, $middleware = [])
    {
        $this->addRoute('POST', $path, $handler, $middleware);
    }
    
    private function addRoute($method, $path, $handler, $middleware)
    {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler,
            'middleware' => $middleware
        ];
    }
    
    public function dispatch()
    {
        $requestMethod = $_SERVER['REQUEST_METHOD'];
        $requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        // Remover el base path del proyecto
        $basePath = str_replace('/index.php', '', $_SERVER['SCRIPT_NAME']);
        $requestUri = str_replace($basePath, '', $requestUri);
        $requestUri = $requestUri ?: '/';
        
        foreach ($this->routes as $route) {
            if ($route['method'] !== $requestMethod) {
                continue;
            }
            
            // Convertir patrón de ruta a regex
            $pattern = preg_replace('/\{([a-z]+)\}/', '(?P<$1>[^/]+)', $route['path']);
            $pattern = '#^' . $pattern . '$#';
            
            if (preg_match($pattern, $requestUri, $matches)) {
                // Ejecutar middlewares
                foreach ($route['middleware'] as $middleware) {
                    $middlewareClass = "TAMEP\\Middleware\\{$middleware}";
                    $middlewareInstance = new $middlewareClass();
                    if (!$middlewareInstance->handle()) {
                        return; // Middleware bloqueó la petición
                    }
                }
                
                // Extraer parámetros de la ruta
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                
                // Ejecutar el handler
                if (is_callable($route['handler'])) {
                    call_user_func_array($route['handler'], $params);
                } else {
                    list($controller, $method) = explode('@', $route['handler']);
                    $controllerClass = "TAMEP\\Controllers\\{$controller}";
                    $controllerInstance = new $controllerClass();
                    call_user_func_array([$controllerInstance, $method], $params);
                }
                
                return;
            }
        }
        
        // No se encontró la ruta
        http_response_code(404);
        echo "404 - Página no encontrada";
    }
}
