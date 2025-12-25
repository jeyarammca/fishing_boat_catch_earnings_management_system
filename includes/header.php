<?php
// Header Template

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . SITE_URL . 'login.php');
    exit();
}

// Get user info
$user_query = "SELECT * FROM users WHERE id = " . $_SESSION['user_id'];
$user_result = execute_query($user_query);
$current_user = $user_result->fetch_assoc();
// Prepare language list and current label for display
$default_langs = [ 'en' => 'English', 'ta' => 'தமிழ்', 'ml' => 'മലയാളം', 'kn' => 'ಕನ್ನಡ', 'hi' => 'हिन्दी' ];
$enabled_langs = $default_langs;
$check_lang_table = execute_query("SHOW TABLES LIKE 'language_settings'");
if ($check_lang_table && $check_lang_table->num_rows > 0) {
    $res_langs = execute_query("SELECT lang_code FROM language_settings WHERE enabled = 1 ORDER BY lang_code");
    $temp_langs = [];
    while ($r = $res_langs->fetch_assoc()) {
        $c = $r['lang_code'];
        if (isset($default_langs[$c])) $temp_langs[$c] = $default_langs[$c];
    }
    if (!empty($temp_langs)) $enabled_langs = $temp_langs;
}
$current_lang_label = $enabled_langs[$_SESSION['lang'] ?? 'en'] ?? strtoupper($_SESSION['lang'] ?? 'EN');
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($_SESSION['lang'] ?? 'en'); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'Dashboard'; ?> - <?php echo SITE_NAME; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>assets/css/style.css">
    
    <!-- Google Fonts (Noto Sans for regional languages) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Devanagari:wght@400;500;600;700&family=Noto+Sans+Kannada:wght@400;500;600;700&family=Noto+Sans+Malayalam:wght@400;500;600;700&family=Noto+Sans+Tamil:wght@400;500;600;700&family=Noto+Sans:ital,wght@0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="<?php echo SITE_URL; ?>">
                <i class="fas fa-ship"></i> <?php echo __('site_name'); ?>
            </a>
            <button id="sidebarToggle" class="btn btn-sm btn-light me-2" title="Toggle sidebar" aria-expanded="true">
                <i class="fas fa-bars"></i>
            </button>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <span class="navbar-text text-white me-3">
                            <i class="fas fa-user"></i> <?php echo htmlspecialchars($current_user['full_name']); ?> (<?php echo ucfirst($current_user['role']); ?>)
                            <span class="badge bg-light text-primary ms-2" title="<?php echo htmlspecialchars($current_lang_label); ?>"><?php echo htmlspecialchars($current_lang_label); ?></span>
                        </span>
                    </li>
                    <li class="nav-item dropdown me-2">
                        <a class="nav-link dropdown-toggle text-white" href="#" id="langDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-language"></i> <?php echo strtoupper($_SESSION['lang'] ?? 'EN'); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="langDropdown">
                            <?php foreach ($enabled_langs as $code => $label): ?>
                                <li><a class="dropdown-item lang-switch" href="#" data-lang="<?php echo $code; ?>"><?php echo $label; ?></a></li>
                            <?php endforeach; ?>
                        </ul>
                    </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo SITE_URL; ?>logout.php">
                                    <i class="fas fa-sign-out-alt"></i> <?php echo __('logout'); ?>
                                </a>
                            </li>
                </ul>
            </div>
        </div>
    </nav>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.lang-switch').forEach(function(el){
            el.addEventListener('click', function(e){
                e.preventDefault();
                var code = this.getAttribute('data-lang');
                try {
                    var url = new URL(window.location.href);
                    url.searchParams.set('lang', code);
                    window.location.href = url.toString();
                } catch (err) {
                    // Fallback for older browsers
                    var href = window.location.pathname + window.location.search;
                    if (href.indexOf('?') === -1) href += '?lang=' + code; else if (href.indexOf('lang=') === -1) href += '&lang=' + code; else href = href.replace(/(lang=)[^&]*/, '$1' + code);
                    window.location.href = href;
                }
            });
        });

        // Auto-append current lang to internal links so language persists across navigation
        var currentLang = '<?php echo isset($_SESSION['lang']) ? addslashes($_SESSION['lang']) : 'en'; ?>';
        function isInternalHref(h) {
            if (!h) return false;
            if (h.indexOf('mailto:') === 0 || h.indexOf('tel:') === 0 || h.indexOf('javascript:') === 0) return false;
            if (h.indexOf('#') === 0) return false;
            // absolute external
            if (/^[a-zA-Z][a-zA-Z0-9+.-]*:\/\//.test(h)) {
                // treat as internal if it starts with SITE_URL
                return h.indexOf('<?php echo SITE_URL; ?>') === 0;
            }
            // relative or root-relative are internal
            return true;
        }

        function appendLangToUrl(href, lang) {
            try {
                var u = new URL(href, window.location.origin);
                u.searchParams.set('lang', lang);
                return u.toString();
            } catch (e) {
                // fallback string manipulation
                if (href.indexOf('?') === -1) return href + '?lang=' + lang;
                if (href.indexOf('lang=') !== -1) return href.replace(/(lang=)[^&]*/, '$1' + lang);
                return href + '&lang=' + lang;
            }
        }

        document.querySelectorAll('a[href]').forEach(function(a){
            var h = a.getAttribute('href');
            if (!isInternalHref(h)) return;
            // don't modify language-switch links (they handle themselves)
            if (a.classList.contains('lang-switch')) return;
            // skip anchors and empty
            if (!h || h.startsWith('#')) return;
            // skip mailto/tel
            if (h.indexOf('mailto:') === 0 || h.indexOf('tel:') === 0) return;
            // Only append if lang param not already present
            try {
                var test = new URL(h, window.location.origin);
                if (!test.searchParams.has('lang')) {
                    a.setAttribute('href', appendLangToUrl(h, currentLang));
                }
            } catch (e) {
                if (h.indexOf('lang=') === -1) {
                    a.setAttribute('href', appendLangToUrl(h, currentLang));
                }
            }
        });

        // Ensure forms include current lang (hidden input) and append lang to action URLs when present
        document.querySelectorAll('form').forEach(function(form){
            // add hidden lang input if not present
            if (!form.querySelector('input[name="lang"]')) {
                var inp = document.createElement('input');
                inp.type = 'hidden';
                inp.name = 'lang';
                inp.value = currentLang;
                form.appendChild(inp);
            }
            // if form has action and is internal, append lang param
            var action = form.getAttribute('action') || '';
            if (action && isInternalHref(action)) {
                try {
                    var u = new URL(action, window.location.origin);
                    if (!u.searchParams.has('lang')) {
                        form.setAttribute('action', appendLangToUrl(action, currentLang));
                    }
                } catch (e) {
                    if (action.indexOf('lang=') === -1) {
                        form.setAttribute('action', appendLangToUrl(action, currentLang));
                    }
                }
            }
        });

        // Sidebar toggle behavior and persistence (responsive)
        var sidebar = document.querySelector('.sidebar');
        var mainContent = document.querySelector('.main-content');
        var toggle = document.getElementById('sidebarToggle');
        var backdrop = document.getElementById('sidebarBackdrop');

        function isMobile() {
            return window.matchMedia('(max-width: 768px)').matches;
        }

        function applySidebarState(collapsed) {
            if (!sidebar || !mainContent) return;
            if (isMobile()) {
                // On mobile: show-mobile controls visibility
                if (collapsed) {
                    sidebar.classList.remove('show-mobile');
                    if (backdrop) backdrop.style.display = 'none';
                } else {
                    sidebar.classList.add('show-mobile');
                    if (backdrop) backdrop.style.display = 'block';
                }
                // main-content remains full width on mobile
                mainContent.classList.remove('fullwidth');
            } else {
                // Desktop behavior: collapsed -> hidden sidebar
                if (collapsed) {
                    sidebar.classList.add('collapsed');
                    mainContent.classList.add('fullwidth');
                } else {
                    sidebar.classList.remove('collapsed');
                    mainContent.classList.remove('fullwidth');
                }
                if (backdrop) backdrop.style.display = 'none';
            }
        }

        // Initialize from localStorage for desktop; mobile defaults hidden (via CSS)
        var collapsedState = localStorage.getItem('sidebarCollapsed');
        if (collapsedState === null) collapsedState = 'false';
        applySidebarState(collapsedState === 'true');

        if (toggle) {
            toggle.addEventListener('click', function () {
                if (isMobile()) {
                    // toggle mobile show state (do not persist)
                    var now = sidebar.classList.contains('show-mobile');
                    applySidebarState(now); // if now true -> collapse (hide); if now false -> show
                } else {
                    var now = sidebar.classList.contains('collapsed');
                    applySidebarState(!now);
                    localStorage.setItem('sidebarCollapsed', (!now).toString());
                }
            });
        }

        // Clicking backdrop closes mobile sidebar
        if (backdrop) {
            backdrop.addEventListener('click', function () {
                applySidebarState(true);
            });
        }

        // When resizing, ensure appropriate state applied
        window.addEventListener('resize', function () {
            // Reapply state so mobile/desktop classes update
            var persists = localStorage.getItem('sidebarCollapsed');
            if (persists === null) persists = 'false';
            applySidebarState(persists === 'true');
        });
    });
    </script>

    <!-- Mobile backdrop for sidebar when open on small screens -->
    <div id="sidebarBackdrop" class="sidebar-backdrop" style="display:none"></div>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar Navigation -->
            <nav class="col-md-2 d-md-block bg-light sidebar">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <?php if ($current_user['role'] == 'admin') { ?>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo SITE_URL; ?>admin/dashboard.php" title="<?php echo __('dashboard'); ?>">
                                    <i class="fas fa-chart-line"></i> <?php echo __('dashboard'); ?>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                                    <i class="fas fa-cogs"></i> <?php echo __('master_data'); ?>
                                </a>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>admin/boats.php" title="<?php echo __('boats'); ?>"><?php echo __('boats'); ?></a></li>
                                    <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>admin/fishermen.php" title="<?php echo __('fishermen'); ?>"><?php echo __('fishermen'); ?></a></li>
                                    <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>admin/fish_types.php" title="<?php echo __('fish_types'); ?>"><?php echo __('fish_types'); ?></a></li>
                                    <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>admin/users.php" title="<?php echo __('users'); ?>"><?php echo __('users'); ?></a></li>
                                </ul>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo SITE_URL; ?>admin/language_settings.php" title="<?php echo __('language_settings') ?? 'Language Settings'; ?>">
                                    <i class="fas fa-language"></i> <?php echo __('language_settings') ?? 'Language Settings'; ?>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                                    <i class="fas fa-file-alt"></i> <?php echo __('reports'); ?>
                                </a>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>reports/daily_collection.php"><?php echo __('daily_collection'); ?></a></li>
                                    <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>reports/boat_earnings.php"><?php echo __('boat_earnings'); ?></a></li>
                                    <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>reports/monthly_earnings.php"><?php echo __('monthly_earnings'); ?></a></li>
                                    <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>reports/fish_type_report.php">Fish Type Report</a></li>
                                    <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>reports/fishermen_attendance.php"><?php echo __('fishermen_attendance'); ?></a></li>
                                </ul>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                                    <i class="fas fa-tasks"></i> <?php echo __('daily_operations'); ?>
                                </a>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>staff/boat_trips.php"><?php echo __('boat_trips'); ?></a></li>
                                    <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>staff/assign_fishermen.php"><?php echo __('assign_fishermen'); ?></a></li>
                                    <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>staff/catch_entry.php"><?php echo __('catch_entry'); ?></a></li>
                                    <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>staff/expenses.php"><?php echo __('expenses'); ?></a></li>
                                </ul>
                            </li>
                        <?php } else { ?>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo SITE_URL; ?>staff/dashboard.php" title="<?php echo __('dashboard'); ?>">
                                    <i class="fas fa-chart-line"></i> <?php echo __('dashboard'); ?>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown" title="<?php echo __('daily_operations'); ?>">
                                    <i class="fas fa-tasks"></i> <?php echo __('daily_operations'); ?>
                                </a>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>staff/boat_trips.php" title="<?php echo __('boat_trips'); ?>"><?php echo __('boat_trips'); ?></a></li>
                                    <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>staff/assign_fishermen.php" title="<?php echo __('assign_fishermen'); ?>"><?php echo __('assign_fishermen'); ?></a></li>
                                    <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>staff/catch_entry.php" title="<?php echo __('catch_entry'); ?>"><?php echo __('catch_entry'); ?></a></li>
                                    <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>staff/expenses.php" title="<?php echo __('expenses'); ?>"><?php echo __('expenses'); ?></a></li>
                                </ul>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                                    <i class="fas fa-file-alt"></i> <?php echo __('reports'); ?>
                                </a>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>reports/daily_collection.php"><?php echo __('daily_collection'); ?></a></li>
                                    <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>reports/boat_earnings.php"><?php echo __('boat_earnings'); ?></a></li>
                                </ul>
                            </li>
                        <?php } ?>
                    </ul>
                </div>
            </nav>

            <!-- Main Content -->
            <main class="col-md-10 ms-sm-auto px-md-4 main-content">
