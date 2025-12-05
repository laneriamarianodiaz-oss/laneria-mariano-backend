<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Código de Verificación</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px 20px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px 30px;
            text-align: center;
        }
        .logo {
            font-size: 48px;
            margin-bottom: 10px;
        }
        .header h1 {
            color: white;
            font-size: 24px;
            font-weight: 600;
            margin: 0;
        }
        .content {
            padding: 50px 40px;
            text-align: center;
        }
        .greeting {
            font-size: 20px;
            color: #333;
            margin-bottom: 20px;
        }
        .message {
            font-size: 16px;
            color: #666;
            line-height: 1.6;
            margin-bottom: 40px;
        }
        .code-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 30px;
            border-radius: 15px;
            margin: 30px 0;
        }
        .code {
            font-size: 48px;
            font-weight: bold;
            color: white;
            letter-spacing: 10px;
            font-family: 'Courier New', monospace;
        }
        .code-label {
            color: rgba(255,255,255,0.9);
            font-size: 14px;
            margin-top: 10px;
        }
        .expiry {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            border-radius: 8px;
            margin: 30px 0;
            text-align: left;
        }
        .expiry strong {
            color: #856404;
        }
        .footer {
            background: #f8f9fa;
            padding: 30px;
            text-align: center;
            color: #6c757d;
            font-size: 14px;
            line-height: 1.6;
        }
        .footer a {
            color: #667eea;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">🧶</div>
            <h1>Lanería Mariano Díaz</h1>
        </div>
        
        <div class="content">
            <div class="greeting">
                ¡Hola {{ $user->name }}! 👋
            </div>
            
            <p class="message">
                Gracias por registrarte en Lanería Mariano Díaz. Para completar tu registro, 
                por favor ingresa el siguiente código de verificación:
            </p>
            
            <div class="code-box">
                <div class="code">{{ $code }}</div>
                <div class="code-label">Código de Verificación</div>
            </div>
            
            <div class="expiry">
                <strong>⏱️ Este código expira en 15 minutos.</strong><br>
                Si no solicitaste este código, puedes ignorar este mensaje.
            </div>
        </div>
        
        <div class="footer">
            <p>
                Este es un correo automático, por favor no respondas.<br>
                Si tienes alguna duda, contáctanos en 
                <a href="mailto:laneriamarianodiaz@gmail.com">laneriamarianodiaz@gmail.com</a>
            </p>
            <p style="margin-top: 20px; color: #adb5bd; font-size: 12px;">
                © {{ date('Y') }} Lanería Mariano Díaz. Todos los derechos reservados.<br>
                Pacucha, Andahuaylas, Apurímac, Perú
            </p>
        </div>
    </div>
</body>
</html>