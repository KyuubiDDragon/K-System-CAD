<?php
/**
 * Public website endpoint for company websites
 * This file handles the public-facing company websites
 */

// Include database connection and common functions
require_once 'common.php';

// No auth check required for public website access
if (!isset($_GET['company']) || empty($_GET['company'])) {
    // No company specified
    header('HTTP/1.1 404 Not Found');
    include('404.html');
    exit;
}

$companyName = $_GET['company'];
$page = isset($_GET['page']) ? $_GET['page'] : 'home';

// Get company and website info
try {
    $stmt = $pdo->prepare("
        SELECT 
            cw.*, 
            c.name as company_name, 
            c.contact_person, 
            c.phonenumber, 
            c.email, 
            c.location
        FROM 
            kdd_company_websites cw
        JOIN 
            kdd_companies c ON c.id = cw.company_id 
        WHERE 
            c.name = :company_name
            AND cw.is_active = 1
            AND c.is_deleted = 0
    ");
    
    $stmt->execute([':company_name' => $companyName]);
    $website = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$website) {
        // Website not found or inactive
        header('HTTP/1.1 404 Not Found');
        include('404.html');
        exit;
    }
    
    // Get the requested page
    $stmt = $pdo->prepare("
        SELECT * 
        FROM kdd_company_website_pages 
        WHERE website_id = :website_id 
        AND slug = :slug 
        AND is_published = 1
    ");
    
    $stmt->execute([
        ':website_id' => $website['id'],
        ':slug' => $page
    ]);
    
    $pageData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$pageData && $page !== 'home') {
        // Try to find the homepage as fallback
        $stmt = $pdo->prepare("
            SELECT * 
            FROM kdd_company_website_pages 
            WHERE website_id = :website_id 
            AND slug = 'home' 
            AND is_published = 1
        ");
        
        $stmt->execute([':website_id' => $website['id']]);
        $pageData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$pageData) {
            // No homepage found
            header('HTTP/1.1 404 Not Found');
            include('404.html');
            exit;
        }
    }
    
    // Get recent posts/news for the sidebar
    $stmt = $pdo->prepare("
        SELECT id, title, slug, created_at, excerpt
        FROM kdd_company_website_posts
        WHERE website_id = :website_id
        AND is_published = 1
        ORDER BY created_at DESC
        LIMIT 5
    ");
    
    $stmt->execute([':website_id' => $website['id']]);
    $recentPosts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get all categories
    $stmt = $pdo->prepare("
        SELECT id, name, slug
        FROM kdd_company_website_categories
        WHERE website_id = :website_id
        ORDER BY name ASC
    ");
    
    $stmt->execute([':website_id' => $website['id']]);
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get navigation pages (main menu)
    $stmt = $pdo->prepare("
        SELECT id, title, slug
        FROM kdd_company_website_pages
        WHERE website_id = :website_id
        AND is_published = 1
        AND show_in_menu = 1
        ORDER BY menu_order ASC
    ");
    
    $stmt->execute([':website_id' => $website['id']]);
    $navPages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Prepare template variables
    $templateVars = [
        'website' => $website,
        'page' => $pageData,
        'recentPosts' => $recentPosts,
        'categories' => $categories,
        'navigation' => $navPages,
        'current_url' => $_SERVER['REQUEST_URI']
    ];
    
    // Load and render the template
    $template = file_get_contents('templates/company_website_template.php');
    
    // Simple template engine
    foreach ($templateVars as $key => $value) {
        if (is_array($value)) {
            continue; // Skip arrays, handle them in the template with loops
        }
        $template = str_replace('{{ ' . $key . ' }}', htmlspecialchars($value, ENT_QUOTES, 'UTF-8'), $template);
    }
    
    // Füge einen Wartungsmodus zur öffentlichen Website hinzu
    // Prüfe, ob die Website im Wartungsmodus ist
    $maintenance_check = $pdo->prepare("
        SELECT maintenance_mode, maintenance_message
        FROM kdd_website_config
        WHERE id = ? AND is_active = 1 AND authority_id = ?
    ");
    $maintenance_check->execute([$website['id'], $authorityId]);
    $maintenance_info = $maintenance_check->fetch(PDO::FETCH_ASSOC);

    // Wenn die Website im Wartungsmodus ist, zeige die Wartungsseite
    if ($maintenance_info && $maintenance_info['maintenance_mode']) {
        // Hole grundlegende Website-Informationen für die Wartungsseite
        $stmt = $pdo->prepare("
            SELECT site_name, footer_text, primary_color, logo 
            FROM kdd_website_config
            WHERE id = ? AND authority_id = ?
        ");
        $stmt->execute([$website['id'], $authorityId]);
        $website_info = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Zeige die Wartungsseite
        ?>
        <!DOCTYPE html>
        <html lang="de">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?php echo htmlspecialchars($website_info['site_name']); ?> - Wartungsmodus</title>
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
            <style>
                :root {
                    --primary-color: <?php echo $website_info['primary_color'] ?? '#3b82f6'; ?>;
                }
                
                body {
                    font-family: 'Arial', sans-serif;
                    line-height: 1.6;
                    color: #333;
                    margin: 0;
                    padding: 0;
                    display: flex;
                    flex-direction: column;
                    min-height: 100vh;
                }
                
                .maintenance-container {
                    flex: 1;
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    justify-content: center;
                    text-align: center;
                    padding: 2rem;
                }
                
                .site-logo img {
                    max-width: 200px;
                    height: auto;
                    margin-bottom: 1rem;
                }
                
                .maintenance-icon {
                    font-size: 4rem;
                    margin: 2rem 0;
                    color: var(--primary-color);
                }
                
                h1 {
                    font-size: 2.5rem;
                    margin-bottom: 0.5rem;
                }
                
                h2 {
                    font-size: 2rem;
                    margin-bottom: 1rem;
                }
                
                p {
                    font-size: 1.1rem;
                    max-width: 600px;
                    margin: 0 auto;
                }
                
                .site-footer {
                    text-align: center;
                    padding: 1rem;
                    background-color: #f8f9fa;
                    margin-top: auto;
                }
            </style>
        </head>
        <body>
            <div class="maintenance-container">
                <?php if ($website_info['logo']): ?>
                    <div class="site-logo">
                        <img src="<?php echo htmlspecialchars($website_info['logo']); ?>" alt="Logo">
                    </div>
                <?php endif; ?>
                
                <h1><?php echo htmlspecialchars($website_info['site_name']); ?></h1>
                
                <div class="maintenance-icon">
                    <i class="fas fa-tools"></i>
                </div>
                
                <h2>Wartungsmodus</h2>
                
                <p><?php echo htmlspecialchars($maintenance_info['maintenance_message'] ?? 'Diese Website befindet sich derzeit im Wartungsmodus und wird in Kürze wieder verfügbar sein.'); ?></p>
            </div>
            
            <footer class="site-footer">
                <p>
                    <?php 
                        echo htmlspecialchars(
                            $website_info['footer_text'] ?? 
                            ('© ' . date('Y') . ' ' . $website_info['site_name'])
                        ); 
                    ?>
                </p>
            </footer>
        </body>
        </html>
        <?php
        exit; // Beende die Ausführung, um nicht den Rest der Website anzuzeigen
    }
    
    echo $template;
    
} catch (PDOException $e) {
    // Log the error
    error_log('Database error in public_website.php: ' . $e->getMessage());
    
    // Show error page
    header('HTTP/1.1 500 Internal Server Error');
    include('500.html');
    exit;
} 