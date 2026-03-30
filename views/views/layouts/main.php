<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Sistema TAMEP' ?></title>
    <link rel="icon" type="image/png" href="/favicon.png">
    <link rel="stylesheet" href="/assets/css/main.css">
    <link rel="stylesheet" href="/assets/css/layout.css">
</head>
<body>
    <div class="layout">
        <?php include __DIR__ . '/../components/sidebar.php'; ?>
        
        <div class="main-content">
            <?php if (isset($pageTitle)): ?>
            <div class="main-header">
                <h1><?= htmlspecialchars($pageTitle) ?></h1>
            </div>
            <?php endif; ?>
            
            <?php 
            $success = \TAMEP\Core\Session::flash('success');
            if ($success): 
            ?>
                <div class="alert alert-success">
                    <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>
            
            <?php 
            $error = \TAMEP\Core\Session::flash('error');
            if ($error): 
            ?>
                <div class="alert alert-error">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <div class="content">
                <?php echo $content ?? ''; ?>
            </div>
        </div>
    </div>
</body>
</html>
