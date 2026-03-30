<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Sistema TAMEP</title>
    <link rel="icon" type="image/png" href="/favicon.png">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1B3C84 0%, #2d5aa0 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .login-container {
            width: 100%;
            max-width: 450px;
            padding: 20px;
        }
        
        .login-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            padding: 40px;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo-container {
            margin-bottom: 20px;
        }
        
        .logo-container img {
            max-width: 100px;
            height: auto;
            filter: drop-shadow(0 2px 10px rgba(0,0,0,0.1));
        }
        
        h1 {
            color: #1B3C84;
            font-size: 28px;
            margin-bottom: 8px;
        }
        
        .login-header p {
            color: #718096;
            font-size: 14px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            color: #2D3748;
            font-weight: 500;
            margin-bottom: 8px;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #E2E8F0;
            border-radius: 6px;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #1B3C84;
            box-shadow: 0 0 0 3px rgba(27, 60, 132, 0.1);
        }
        
        .btn {
            width: 100%;
            padding: 12px;
            background: #1B3C84;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn:hover {
            background: #142D66;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(27, 60, 132, 0.3);
        }
        
        .alert {
            padding: 12px 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        
        .alert-error {
            background: #FED7D7;
            color: #742A2A;
            border-left: 4px solid #E53E3E;
        }
        
        .demo-info {
            background: #BEE3F8;
            color: #2C5282;
            border-left: 4px solid #3182CE;
            padding: 15px;
            border-radius: 6px;
            margin-top: 20px;
            font-size: 13px;
        }
        
        .demo-info strong {
            display: block;
            margin-bottom: 8px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="logo-container">
                    <img src="/assets/img/logo-tamep.png" alt="Logo TAMEP">
                </div>
                <h1>Sistema TAMEP</h1>
                <p>Gestión Documental</p>
            </div>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-error">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="/login">
                <div class="form-group">
                    <label for="username">Usuario</label>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        class="form-control" 
                        placeholder="Ingrese su usuario"
                        required 
                        autofocus
                    >
                </div>
                
                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        class="form-control" 
                        placeholder="Ingrese su contraseña"
                        required
                    >
                </div>
                
                <button type="submit" class="btn">Iniciar Sesión</button>
            </form>
            
            <div class="demo-info">
                <strong>Usuario demo:</strong>
                <code>admin</code> | Contraseña: <code>admin123</code>
            </div>
        </div>
    </div>
</body>
</html>
