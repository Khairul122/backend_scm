<?php
class Router {
    private $routes = [];
    
    public function get($path, $callback) {
        $this->addRoute('GET', $path, $callback);
    }
    
    public function post($path, $callback) {
        $this->addRoute('POST', $path, $callback);
    }
    
    public function put($path, $callback) {
        $this->addRoute('PUT', $path, $callback);
    }
    
    public function delete($path, $callback) {
        $this->addRoute('DELETE', $path, $callback);
    }
    
    private function addRoute($method, $path, $callback) {
        $this->routes[$method][$path] = $callback;
    }
    
    public function run() {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = $this->getCurrentPath();
        
        if (isset($this->routes[$method][$path])) {
            $callback = $this->routes[$method][$path];
            
            if (is_callable($callback)) {
                try {
                    call_user_func($callback);
                } catch (Exception $e) {
                    response(500, ['error' => 'Internal server error: ' . $e->getMessage()]);
                }
            }
        } else {
            response(404, [
                'error' => 'Route not found', 
                'method' => $method, 
                'path' => $path,
                'available_routes' => array_keys($this->routes[$method] ?? []),
                'all_routes' => $this->routes
            ]);
        }
    }
    
    private function getCurrentPath() {
        $path = $_SERVER['REQUEST_URI'];
        $path = strtok($path, '?');
        
        $script_name = dirname($_SERVER['SCRIPT_NAME']);
        if ($script_name !== '/') {
            $path = substr($path, strlen($script_name));
        }
        
        return $path;
    }
}