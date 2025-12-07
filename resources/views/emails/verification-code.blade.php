<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Código de Verificación</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #4A90E2;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 5px 5px 0 0;
        }
        .content {
            background-color: #f9f9f9;
            padding: 30px;
            border: 1px solid #ddd;
            border-radius: 0 0 5px 5px;
        }
        .code {
            background-color: #fff;
            border: 2px dashed #4A90E2;
            padding: 15px;
            text-align: center;
            font-size: 32px;
            font-weight: bold;
            letter-spacing: 5px;
            margin: 20px 0;
            color: #4A90E2;
        }
        .footer {
            text-align: center;
            margin-top: 20px;
            font-size: 12px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🔐 Verificación de Email</h1>
    </div>
    <div class="content">
        <p>Hola <strong>{{ $user->name }}</strong>,</p>
        
        <p>Gracias por registrarte en <strong>Lanería Mariano Díaz</strong>.</p>
        
        <p>Tu código de verificación es:</p>
        
        <div class="code">{{ $code }}</div>
        
        <p>Este código expira en <strong>15 minutos</strong>.</p>
        
        <p>Si no solicitaste este registro, puedes ignorar este correo.</p>
        
        <p>Saludos,<br><strong>Equipo de Lanería Mariano Díaz</strong></p>
    </div>
    <div class="footer">
        <p>Este es un correo automático, por favor no respondas a este mensaje.</p>
    </div>
</body>
</html>