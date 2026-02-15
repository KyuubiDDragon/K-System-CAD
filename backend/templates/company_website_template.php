<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ website.site_name }} - {{ page.title }}</title>
    <meta name="description" content="{{ website.site_description }}">
    <style>
        :root {
            --primary-color: {{ website.primary_color }};
            --secondary-color: {{ website.secondary_color }};
            --background-color: {{ website.background_color }};
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: var(--background-color, #ffffff);
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        /* Header */
        header {
            background-color: var(--primary-color, #3b82f6);
            color: #fff;
            padding: 20px 0;
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo h1 {
            font-size: 2.2rem;
            margin-bottom: 5px;
        }
        
        .logo p {
            font-size: 1rem;
            opacity: 0.8;
        }
        
        /* Navigation */
        nav ul {
            display: flex;
            list-style: none;
        }
        
        nav ul li {
            margin-left: 20px;
        }
        
        nav ul li a {
            color: #fff;
            text-decoration: none;
            font-weight: bold;
            padding: 5px 10px;
            border-radius: 4px;
            transition: background-color 0.3s;
        }
        
        nav ul li a:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        /* Main Content */
        .main-content {
            display: flex;
            margin: 40px 0;
        }
        
        .content {
            flex: 3;
            padding-right: 40px;
        }
        
        .content h2 {
            font-size: 2rem;
            margin-bottom: 20px;
            color: var(--primary-color, #3b82f6);
        }
        
        .content p {
            margin-bottom: 20px;
            line-height: 1.7;
        }
        
        .content img {
            max-width: 100%;
            height: auto;
            margin: 20px 0;
            border-radius: 5px;
        }
        
        /* Sidebar */
        .sidebar {
            flex: 1;
        }
        
        .sidebar-section {
            margin-bottom: 30px;
            padding: 20px;
            background-color: #f5f7fb;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        
        .sidebar-section h3 {
            color: var(--primary-color, #3b82f6);
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e1e4e8;
        }
        
        .recent-posts ul, .categories ul {
            list-style: none;
        }
        
        .recent-posts li, .categories li {
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e1e4e8;
        }
        
        .recent-posts li:last-child, .categories li:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        
        .recent-posts a, .categories a {
            color: #444;
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .recent-posts a:hover, .categories a:hover {
            color: var(--primary-color, #3b82f6);
        }
        
        .post-date {
            font-size: 0.8rem;
            color: #888;
            margin-top: 5px;
            display: block;
        }
        
        .contact-info p {
            margin-bottom: 10px;
        }
        
        .contact-info strong {
            color: var(--primary-color, #3b82f6);
            width: 80px;
            display: inline-block;
        }
        
        /* Form */
        .contact-form {
            margin-top: 20px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #444;
        }
        
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }
        
        button {
            background-color: var(--primary-color, #3b82f6);
            color: white;
            border: none;
            padding: 10px 20px;
            font-size: 1rem;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        button:hover {
            opacity: 0.9;
        }
        
        /* Footer */
        footer {
            background-color: var(--secondary-color, #1e3a8a);
            color: white;
            padding: 40px 0;
            margin-top: 40px;
        }
        
        .footer-content {
            display: flex;
            justify-content: space-between;
        }
        
        .footer-section {
            flex: 1;
            margin-right: 40px;
        }
        
        .footer-section:last-child {
            margin-right: 0;
        }
        
        .footer-section h3 {
            margin-bottom: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding-bottom: 10px;
        }
        
        .copyright {
            text-align: center;
            padding-top: 30px;
            margin-top: 30px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            font-size: 0.9rem;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                text-align: center;
            }
            
            nav ul {
                flex-direction: column;
                text-align: center;
                margin-top: 20px;
            }
            
            nav ul li {
                margin: 10px 0;
            }
            
            .main-content {
                flex-direction: column;
            }
            
            .content {
                padding-right: 0;
                margin-bottom: 30px;
            }
            
            .footer-content {
                flex-direction: column;
            }
            
            .footer-section {
                margin-right: 0;
                margin-bottom: 30px;
            }
        }
    </style>
</head>

<body>
    <header>
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <h1>{{ website.site_name }}</h1>
                    <p>{{ website.site_slogan }}</p>
                </div>
                
                <nav>
                    <ul>
                        <?php foreach ($templateVars['navigation'] as $navItem): ?>
                            <li>
                                <a href="/website/<?php echo htmlspecialchars($templateVars['website']['company_name']); ?>/<?php echo htmlspecialchars($navItem['slug']); ?>"
                                   <?php echo $navItem['slug'] === $templateVars['page']['slug'] ? 'class="active"' : ''; ?>>
                                    <?php echo htmlspecialchars($navItem['title']); ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </nav>
            </div>
        </div>
    </header>
    
    <div class="container">
        <div class="main-content">
            <main class="content">
                <h2><?php echo htmlspecialchars($templateVars['page']['title']); ?></h2>
                
                <?php echo $templateVars['page']['content']; ?>
                
                <?php if (isset($_GET['contact'])): ?>
                <div class="contact-form">
                    <h3>Kontaktieren Sie uns</h3>
                    <form action="/website/submit-contact.php" method="post">
                        <input type="hidden" name="website_id" value="<?php echo $templateVars['website']['id']; ?>">
                        
                        <div class="form-group">
                            <label for="name">Name</label>
                            <input type="text" id="name" name="name" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">E-Mail</label>
                            <input type="email" id="email" name="email" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="message">Nachricht</label>
                            <textarea id="message" name="message" rows="5" required></textarea>
                        </div>
                        
                        <button type="submit">Absenden</button>
                    </form>
                </div>
                <?php endif; ?>
            </main>
            
            <aside class="sidebar">
                <div class="sidebar-section contact-info">
                    <h3>Kontakt</h3>
                    <p><strong>Adresse:</strong> <?php echo htmlspecialchars($templateVars['website']['location']); ?></p>
                    <p><strong>Telefon:</strong> <?php echo htmlspecialchars($templateVars['website']['phonenumber']); ?></p>
                    <p><strong>E-Mail:</strong> <?php echo htmlspecialchars($templateVars['website']['email']); ?></p>
                    <p><strong>Kontakt:</strong> <?php echo htmlspecialchars($templateVars['website']['contact_person']); ?></p>
                </div>
                
                <?php if (!empty($templateVars['recentPosts'])): ?>
                <div class="sidebar-section recent-posts">
                    <h3>Aktuelle Beiträge</h3>
                    <ul>
                        <?php foreach ($templateVars['recentPosts'] as $post): ?>
                        <li>
                            <a href="/website/<?php echo htmlspecialchars($templateVars['website']['company_name']); ?>/blog/<?php echo htmlspecialchars($post['slug']); ?>">
                                <?php echo htmlspecialchars($post['title']); ?>
                            </a>
                            <span class="post-date"><?php echo date('d.m.Y', strtotime($post['created_at'])); ?></span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($templateVars['categories'])): ?>
                <div class="sidebar-section categories">
                    <h3>Kategorien</h3>
                    <ul>
                        <?php foreach ($templateVars['categories'] as $category): ?>
                        <li>
                            <a href="/website/<?php echo htmlspecialchars($templateVars['website']['company_name']); ?>/category/<?php echo htmlspecialchars($category['slug']); ?>">
                                <?php echo htmlspecialchars($category['name']); ?>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
            </aside>
        </div>
    </div>
    
    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>Über uns</h3>
                    <p><?php echo htmlspecialchars($templateVars['website']['site_description']); ?></p>
                </div>
                
                <div class="footer-section">
                    <h3>Kontakt</h3>
                    <p><?php echo htmlspecialchars($templateVars['website']['contact_person']); ?></p>
                    <p><?php echo htmlspecialchars($templateVars['website']['email']); ?></p>
                    <p><?php echo htmlspecialchars($templateVars['website']['phonenumber']); ?></p>
                </div>
                
                <div class="footer-section">
                    <h3>Quicklinks</h3>
                    <ul>
                        <?php foreach ($templateVars['navigation'] as $navItem): ?>
                        <li>
                            <a href="/website/<?php echo htmlspecialchars($templateVars['website']['company_name']); ?>/<?php echo htmlspecialchars($navItem['slug']); ?>">
                                <?php echo htmlspecialchars($navItem['title']); ?>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            
            <div class="copyright">
                <p>© <?php echo date('Y'); ?> <?php echo htmlspecialchars($templateVars['website']['site_name']); ?>. Alle Rechte vorbehalten.</p>
                <?php if (!empty($templateVars['website']['footer_text'])): ?>
                <p><?php echo htmlspecialchars($templateVars['website']['footer_text']); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </footer>
</body>
</html> 