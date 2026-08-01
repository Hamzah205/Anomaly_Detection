<?php
/**
 * PDAM Anomaly Detection - Simple Router
 * Handles routing to different pages securely
 */

class Router {
    private $routes = [];
    
    public function __construct() {
        $this->defineRoutes();
    }
    
    private function defineRoutes() {
        // Public routes (accessible without auth)
        $this->routes = [
            '' => ['file' => 'landing.php', 'auth' => false],
            'home' => ['file' => 'landing.php', 'auth' => false],
            'login' => ['file' => '../resources/views/login.php', 'auth' => false],
            'register' => ['file' => '../resources/views/register.php', 'auth' => false],
            
            // Dashboard routes (accessible as guest or user)
            'dashboard' => ['file' => '../resources/views/dashboard1.php', 'auth' => false],
            'dashboard1' => ['file' => '../resources/views/dashboard1.php', 'auth' => false],
            'dashboard2' => ['file' => '../resources/views/dashboard2.php', 'auth' => false],
            'dashboard3' => ['file' => '../resources/views/dashboard3.php', 'auth' => false],
            'dashboard4' => ['file' => '../resources/views/dashboard4.php', 'auth' => false],
            
            // User routes (require authentication)
            'history' => ['file' => '../resources/views/history.php', 'auth' => true],
            
            // API endpoint
            'api' => ['file' => '../routes/api.php', 'auth' => false],
            
            // Auth actions
            'logout' => ['file' => '../app/Http/Controllers/logout.php', 'auth' => false],
        ];
    }
    
    public function route($path) {
        // Clean path
        $path = trim($path, '/');
        $path = explode('?', $path)[0]; // Remove query string
        
        // Check if route exists
        if (!isset($this->routes[$path])) {
            $this->notFound();
            return;
        }
        
        $route = $this->routes[$path];
        
        // Check authentication
        if ($route['auth'] && !auth_check()) {
            header('Location: ' . url('/login?redirect=') . urlencode($path));
            exit;
        }
        
        // Include the file
        $filePath = __DIR__ . '/' . $route['file'];
        
        if (!file_exists($filePath)) {
            $this->notFound();
            return;
        }
        
        // For landing page, we need to handle it differently
        if ($path === '' || $path === 'home') {
            $this->serveLandingPage();
            return;
        }
        
        // For API, set proper content type handled by the file itself
        require $filePath;
        exit;
    }
    
    private function serveLandingPage() {
        // Landing page content is in current index.php
        // We'll mark it to render normally
        return;
    }
    
    private function notFound() {
        http_response_code(404);
        ?>
        <!DOCTYPE html>
        <html lang="id">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>404 - Halaman Tidak Ditemukan</title>
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body { 
                    font-family: system-ui, -apple-system, sans-serif; 
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    min-height: 100vh;
                    text-align: center;
                    padding: 20px;
                }
                .container { max-width: 500px; }
                h1 { font-size: 120px; font-weight: 800; margin-bottom: 20px; opacity: 0.9; }
                h2 { font-size: 28px; margin-bottom: 16px; }
                p { font-size: 16px; opacity: 0.9; margin-bottom: 32px; }
                a { 
                    display: inline-block;
                    background: white;
                    color: #667eea;
                    padding: 14px 32px;
                    border-radius: 50px;
                    text-decoration: none;
                    font-weight: 600;
                    transition: transform 0.2s;
                }
                a:hover { transform: translateY(-2px); }
            </style>
        </head>
        <body>
            <div class="container">
                <h1>404</h1>
                <h2>Halaman Tidak Ditemukan</h2>
                <p>Maaf, halaman yang Anda cari tidak ada atau telah dipindahkan.</p>
                <a href="/">Kembali ke Beranda</a>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
}

// Get the route from URL
$route = $_GET['route'] ?? '';

// Initialize router
$router = new Router();

// Check if we should route or serve landing page
if (!empty($route)) {
    $router->route($route);
}

// If no route or landing page, continue to render index.php content below
