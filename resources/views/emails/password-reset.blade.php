<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
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
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
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
        .button-container {
            margin: 40px 0;
        }
        .reset-button {
            display: inline-block;
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            text-decoration: none;
            padding: 18px 50px;
            border-radius: 50px;
            font-size: 18px;
            font-weight: 600;
            box-shadow: 0 10px 30px rgba(245, 87, 108, 0.4);
            transition: transform 0.3s;
        }
        .reset-button:hover {
            transform: translateY(-2px);
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
        .alternative {
            margin-top: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
            font-size: 14px;
            color: #6c757d;
            text-align: left;
        }
        .alternative a {
            color: #f5576c;
            word-break: break-all;
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
            color: #f5576c;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">🔑</div>
            <h1>Recuperar Contraseña</h1>
        </div>
        
        <div class="content">
            <div class="greeting">
                ¡Hola {{ $user->name }}! 👋
            </div>
            
            <p class="message">
                Recibimos una solicitud para restablecer tu contraseña. 
                Haz clic en el botón de abajo para crear una nueva contraseña:
            </p>
            
            <div class="button-container">
                <a href="{{ $resetUrl }}" class="reset-button">
                    🔐 Restablecer Contraseña
                </a>
            </div>
            
            <div class="expiry">
                <strong>⏱️ Este enlace expira en 1 hora.</strong><br>
                Si no solicitaste restablecer tu contraseña, ignora este mensaje.
            </div>
            
            <div class="alternative">
                <strong>¿El botón no funciona?</strong><br>
                Copia y pega este enlace en tu navegador:<br>
                <a href="{{ $resetUrl }}">{{ $resetUrl }}</a>
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