<?php
require_once 'check_admin.php';
requireAdmin();

$message = '';
$messageType = '';

// Handle CSS file save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'save_css') {
        $cssContent = $_POST['css_content'];
        $cssFile = '../css/style.css';
        
        // Create backup
        $backupFile = '../css/style.css.backup_' . date('YmdHis');
        copy($cssFile, $backupFile);
        
        // Save new content
        if (file_put_contents($cssFile, $cssContent) !== false) {
            $message = 'CSS file saved successfully! Backup created at: ' . basename($backupFile);
            $messageType = 'success';
        } else {
            $message = 'Error saving CSS file!';
            $messageType = 'error';
        }
    } elseif ($_POST['action'] === 'save_content') {
        // Handle page content save
        $pageFile = $_POST['page_file'];
        $updatedContent = $_POST['page_content'];
        
        // Security check - ensure file is within allowed directories
        $allowedPaths = ['../index.php', '../hiscores.php', '../ex/rules.php', '../ex/geting-started.php'];
        if (!in_array($pageFile, $allowedPaths)) {
            $message = 'Invalid file selection!';
            $messageType = 'error';
        } else {
            // Create backup
            $backupFile = $pageFile . '.backup_' . date('YmdHis');
            copy($pageFile, $backupFile);
            
            // Save the updated content
            if (file_put_contents($pageFile, $updatedContent) !== false) {
                $message = 'Page content saved successfully! Backup created.';
                $messageType = 'success';
            } else {
                $message = 'Error saving page content!';
                $messageType = 'error';
            }
        }
    } elseif ($_POST['action'] === 'quick_edit') {
        // Handle quick edits
        $file = $_POST['file'];
        $edits = json_decode($_POST['edits'], true);
        
        // Security check
        $allowedPaths = ['../index.php', '../hiscores.php', '../ex/rules.php', '../ex/geting-started.php'];
        if (!in_array($file, $allowedPaths)) {
            $message = 'Invalid file selection!';
            $messageType = 'error';
        } else {
            // Read file content
            $content = file_get_contents($file);
            
            // Create backup
            $backupFile = $file . '.backup_' . date('YmdHis');
            copy($file, $backupFile);
            
            // Apply edits
            foreach ($edits as $edit) {
                $content = str_replace($edit['find'], $edit['replace'], $content);
            }
            
            // Save updated content
            if (file_put_contents($file, $content) !== false) {
                $message = 'Quick edits saved successfully!';
                $messageType = 'success';
            } else {
                $message = 'Error saving quick edits!';
                $messageType = 'error';
            }
        }
    }
}

// Load CSS content
$cssContent = file_get_contents('../css/style.css');

// Define editable pages
$editablePages = [
    '../index.php' => 'Homepage',
    '../hiscores.php' => 'Hiscores',
    '../ex/rules.php' => 'Game Rules',
    '../ex/geting-started.php' => 'Getting Started'
];

// Load selected page content
$selectedPage = isset($_POST['selected_page']) ? $_POST['selected_page'] : '../index.php';
$pageContent = '';
if (isset($editablePages[$selectedPage])) {
    $pageContent = file_get_contents($selectedPage);
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Content Management - Admin Panel</title>
    <link rel="stylesheet" href="css/admin-style.css">
    <style>
        
        /* Tabs */
        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
            border-bottom: 2px solid #444;
        }
        
        .tab {
            padding: 15px 30px;
            background: none;
            border: none;
            color: #ccc;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.3s;
            border-bottom: 2px solid transparent;
            margin-bottom: -2px;
        }
        
        .tab:hover {
            color: #ffd700;
        }
        
        .tab.active {
            color: #ffd700;
            border-bottom-color: #ffd700;
        }
        
        /* Tab Content */
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        /* Editor Section */
        .editor-section {
            background: #2d2d2d;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .editor-section h3 {
            color: #ffd700;
            margin-bottom: 15px;
        }
        
        .editor-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .page-selector {
            padding: 10px 15px;
            background: #1a1a1a;
            border: 1px solid #444;
            color: #fff;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
        }
        
        .page-selector:focus {
            outline: none;
            border-color: #ffd700;
        }
        
        textarea {
            width: 100%;
            min-height: 500px;
            padding: 15px;
            background: #1a1a1a;
            border: 1px solid #444;
            color: #fff;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            line-height: 1.5;
            border-radius: 5px;
            resize: vertical;
        }
        
        textarea:focus {
            outline: none;
            border-color: #ffd700;
        }
        
        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        
        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: #ffd700;
            color: #1a1a1a;
        }
        
        .btn-primary:hover {
            background: #ffed4e;
        }
        
        .btn-secondary {
            background: #444;
            color: #fff;
        }
        
        .btn-secondary:hover {
            background: #555;
        }
        
        /* Messages */
        .message {
            padding: 15px 20px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        
        .message.success {
            background: rgba(76, 175, 80, 0.2);
            border: 1px solid #4caf50;
            color: #81c784;
        }
        
        .message.error {
            background: rgba(244, 67, 54, 0.2);
            border: 1px solid #f44336;
            color: #ef5350;
        }
        
        /* Info Box */
        .info-box {
            background: rgba(255, 215, 0, 0.1);
            border: 1px solid #ffd700;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .info-box h4 {
            color: #ffd700;
            margin-bottom: 10px;
        }
        
        .info-box ul {
            margin-left: 20px;
            color: #ccc;
        }
        
        .info-box li {
            margin-bottom: 5px;
        }
        
        /* Quick Edit Section */
        .quick-edits {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }
        
        .quick-edit-card {
            background: #2d2d2d;
            border-radius: 10px;
            padding: 20px;
        }
        
        .quick-edit-card h4 {
            color: #ffd700;
            margin-bottom: 15px;
        }
        
        .quick-edit-card input,
        .quick-edit-card textarea {
            width: 100%;
            padding: 10px;
            background: #1a1a1a;
            border: 1px solid #444;
            color: #fff;
            border-radius: 5px;
            margin-bottom: 10px;
            font-family: inherit;
        }
        
        .quick-edit-card input:focus,
        .quick-edit-card textarea:focus {
            outline: none;
            border-color: #ffd700;
        }
        
        .quick-edit-card textarea {
            resize: vertical;
            line-height: 1.4;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <?php require_once 'includes/nav.php'; ?>
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="page-header">
                <h1>Content Management</h1>
                <p>Edit CSS styles and page content</p>
            </div>
            
            <?php if ($message): ?>
                <div class="message <?php echo $messageType; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <!-- Tabs -->
            <div class="tabs">
                <button class="tab active" onclick="switchTab('css')">CSS Editor</button>
                <button class="tab" onclick="switchTab('content')">Page Content</button>
                <button class="tab" onclick="switchTab('quick')">Quick Edits</button>
            </div>
            
            <!-- CSS Editor Tab -->
            <div id="css-tab" class="tab-content active">
                <div class="editor-section">
                    <h3>Global CSS Editor</h3>
                    <div class="info-box">
                        <h4>CSS Editing Tips:</h4>
                        <ul>
                            <li>This file controls the styling for all pages</li>
                            <li>Changes take effect immediately after saving</li>
                            <li>A backup is created automatically before each save</li>
                            <li>Main colors: #ffd700 (gold), #1a1a1a (dark bg)</li>
                        </ul>
                    </div>
                    <form method="POST">
                        <input type="hidden" name="action" value="save_css">
                        <textarea name="css_content" placeholder="Loading CSS content..."><?php echo htmlspecialchars($cssContent); ?></textarea>
                        <div class="button-group">
                            <button type="submit" class="btn btn-primary">Save CSS</button>
                            <button type="button" class="btn btn-secondary" onclick="location.reload()">Reset</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Page Content Tab -->
            <div id="content-tab" class="tab-content">
                <div class="editor-section">
                    <h3>Page Content Editor</h3>
                    <form method="POST" id="page-selector-form">
                        <div class="editor-controls">
                            <div>
                                <label for="page-select">Select Page to Edit:</label>
                                <select name="selected_page" id="page-select" class="page-selector" onchange="this.form.submit()">
                                    <?php foreach ($editablePages as $file => $name): ?>
                                        <option value="<?php echo $file; ?>" <?php echo $selectedPage === $file ? 'selected' : ''; ?>>
                                            <?php echo $name; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </form>
                    
                    <form method="POST">
                        <input type="hidden" name="action" value="save_content">
                        <input type="hidden" name="page_file" value="<?php echo htmlspecialchars($selectedPage); ?>">
                        <textarea name="page_content" placeholder="Loading page content..."><?php echo htmlspecialchars($pageContent); ?></textarea>
                        <div class="button-group">
                            <button type="submit" class="btn btn-primary">Save Page Content</button>
                            <button type="button" class="btn btn-secondary" onclick="location.reload()">Reset</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Quick Edits Tab -->
            <div id="quick-tab" class="tab-content">
                <div class="info-box">
                    <h4>Quick Text Editor</h4>
                    <p>Edit common text elements across your site quickly. Enter the new text and click Update.</p>
                </div>
                
                <div class="quick-edits">
                    <div class="quick-edit-card">
                        <h4>Homepage Hero Text</h4>
                        <form method="POST" class="quick-edit-form" data-file="../index.php">
                            <input type="text" name="hero_title" placeholder="Experience RuneScape as it was in 2004" data-find="Experience RuneScape as it was in 2004">
                            <input type="text" name="hero_subtitle" placeholder="Join thousands of players..." data-find="Join thousands of players in the most authentic Old School RuneScape experience">
                            <button type="submit" class="btn btn-primary">Update Hero</button>
                        </form>
                    </div>
                    
                    <div class="quick-edit-card">
                        <h4>Server Name</h4>
                        <form method="POST" class="quick-edit-form">
                            <input type="text" name="server_name" placeholder="2004Scape" value="2004Scape">
                            <small style="color: #888;">Note: Update this in Settings for database storage</small>
                            <button type="submit" class="btn btn-primary">Update Name</button>
                        </form>
                    </div>
                    
                    <div class="quick-edit-card">
                        <h4>Page Titles</h4>
                        <form method="POST" class="quick-edit-form">
                            <input type="text" name="hiscores_title" placeholder="Hiscores title" data-page="../hiscores.php">
                            <input type="text" name="rules_title" placeholder="Game Rules title" data-page="../ex/rules.php">
                            <input type="text" name="guide_title" placeholder="Getting Started title" data-page="../ex/geting-started.php">
                            <button type="submit" class="btn btn-primary">Update Titles</button>
                        </form>
                    </div>
                    
                    <div class="quick-edit-card">
                        <h4>Feature Card 1 - Gameplay</h4>
                        <form method="POST" class="quick-edit-form" data-file="../index.php">
                            <input type="text" name="feature1_icon" placeholder="Icon (emoji)" value="🎮">
                            <input type="text" name="feature1_title" placeholder="Title" value="Authentic Gameplay">
                            <textarea name="feature1_desc" rows="3" style="min-height: 80px; margin-bottom: 10px;">Experience RuneScape exactly as it was in 2004, with all the nostalgia and none of the modern changes.</textarea>
                            <button type="submit" class="btn btn-primary">Update Card 1</button>
                        </form>
                    </div>
                    
                    <div class="quick-edit-card">
                        <h4>Feature Card 2 - Community</h4>
                        <form method="POST" class="quick-edit-form" data-file="../index.php">
                            <input type="text" name="feature2_icon" placeholder="Icon (emoji)" value="👥">
                            <input type="text" name="feature2_title" placeholder="Title" value="Active Community">
                            <textarea name="feature2_desc" rows="3" style="min-height: 80px; margin-bottom: 10px;">Join a thriving community of players who share your passion for classic RuneScape.</textarea>
                            <button type="submit" class="btn btn-primary">Update Card 2</button>
                        </form>
                    </div>
                    
                    <div class="quick-edit-card">
                        <h4>Feature Card 3 - Fair Play</h4>
                        <form method="POST" class="quick-edit-form" data-file="../index.php">
                            <input type="text" name="feature3_icon" placeholder="Icon (emoji)" value="⚖️">
                            <input type="text" name="feature3_title" placeholder="Title" value="Fair Play">
                            <textarea name="feature3_desc" rows="3" style="min-height: 80px; margin-bottom: 10px;">No pay-to-win mechanics. Everyone starts equal and progresses through their own efforts.</textarea>
                            <button type="submit" class="btn btn-primary">Update Card 3</button>
                        </form>
                    </div>
                    
                    <div class="quick-edit-card">
                        <h4>Feature Card 4 - Security</h4>
                        <form method="POST" class="quick-edit-form" data-file="../index.php">
                            <input type="text" name="feature4_icon" placeholder="Icon (emoji)" value="🔒">
                            <input type="text" name="feature4_title" placeholder="Title" value="Secure & Stable">
                            <textarea name="feature4_desc" rows="3" style="min-height: 80px; margin-bottom: 10px;">Regular backups, active moderation, and dedicated hosting ensure your progress is always safe.</textarea>
                            <button type="submit" class="btn btn-primary">Update Card 4</button>
                        </form>
                    </div>
                </div>
                
                <div class="info-box" style="margin-top: 30px;">
                    <h4>Note:</h4>
                    <p>For more complex edits, use the Page Content tab. Quick edits are for simple text replacements only.</p>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function switchTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            document.querySelectorAll('.tab').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Show selected tab
            document.getElementById(tabName + '-tab').classList.add('active');
            event.target.classList.add('active');
        }
        
        // Handle quick edit forms
        document.querySelectorAll('.quick-edit-form').forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData();
                formData.append('action', 'quick_edit');
                
                // Get the file to edit (default to homepage)
                const file = form.dataset.file || '../index.php';
                formData.append('file', file);
                
                // Build the find/replace edits
                const edits = [];
                
                // Handle feature cards
                if (form.querySelector('[name*="feature"]')) {
                    const match = form.querySelector('[name*="feature1"]') ? '1' :
                                form.querySelector('[name*="feature2"]') ? '2' :
                                form.querySelector('[name*="feature3"]') ? '3' : '4';
                    
                    const icon = form.querySelector(`[name="feature${match}_icon"]`);
                    const title = form.querySelector(`[name="feature${match}_title"]`);
                    const desc = form.querySelector(`[name="feature${match}_desc"]`);
                    
                    if (icon && icon.value !== icon.defaultValue) {
                        // Find the specific feature card and update icon
                        const oldIcon = icon.defaultValue || (match === '1' ? '🎮' : match === '2' ? '👥' : match === '3' ? '⚖️' : '🔒');
                        edits.push({
                            find: `<div class="feature-icon">${oldIcon}</div>`,
                            replace: `<div class="feature-icon">${icon.value}</div>`
                        });
                    }
                    
                    if (title && title.value !== title.defaultValue) {
                        const oldTitle = title.defaultValue || (match === '1' ? 'Authentic Gameplay' : match === '2' ? 'Active Community' : match === '3' ? 'Fair Play' : 'Secure & Stable');
                        edits.push({
                            find: `<h3>${oldTitle}</h3>`,
                            replace: `<h3>${title.value}</h3>`
                        });
                    }
                    
                    if (desc && desc.value !== desc.defaultValue) {
                        const oldDesc = desc.defaultValue || desc.textContent.trim();
                        // Need to be careful with line breaks in descriptions
                        edits.push({
                            find: `<p>${oldDesc}</p>`,
                            replace: `<p>${desc.value}</p>`
                        });
                    }
                }
                
                // Handle hero text
                if (form.querySelector('[name="hero_title"]')) {
                    const heroTitle = form.querySelector('[name="hero_title"]');
                    const heroSubtitle = form.querySelector('[name="hero_subtitle"]');
                    
                    if (heroTitle && heroTitle.value) {
                        edits.push({
                            find: heroTitle.dataset.find || 'Experience RuneScape as it was in 2004',
                            replace: heroTitle.value
                        });
                    }
                    
                    if (heroSubtitle && heroSubtitle.value) {
                        edits.push({
                            find: heroSubtitle.dataset.find || 'Join thousands of players in the most authentic Old School RuneScape experience',
                            replace: heroSubtitle.value
                        });
                    }
                }
                
                if (edits.length === 0) {
                    alert('No changes detected. Please modify at least one field.');
                    return;
                }
                
                formData.append('edits', JSON.stringify(edits));
                
                // Submit the form
                fetch('content.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(html => {
                    // Reload the page to show the message
                    location.reload();
                })
                .catch(error => {
                    alert('Error saving changes: ' + error);
                });
            });
        });
    </script>
</body>
</html>