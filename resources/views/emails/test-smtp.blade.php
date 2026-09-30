<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prueba de Configuración de Correo</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #f8fafc;
            color: #1e293b;
            margin: 0;
            padding: 0;
            line-height: 1.6;
        }
        .container {
            max-width: 600px;
            margin: 30px auto;
            background: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            border: 1px solid #e2e8f0;
        }
        .header {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            color: #ffffff;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 22px;
            font-weight: 800;
            letter-spacing: -0.5px;
        }
        .header p {
            margin: 6px 0 0;
            opacity: 0.9;
            font-size: 14px;
        }
        .content {
            padding: 32px 30px;
        }
        .status-badge {
            display: inline-block;
            background-color: #ecfdf5;
            color: #065f46;
            border: 1px solid #a7f3d0;
            padding: 6px 14px;
            border-radius: 9999px;
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 20px;
        }
        .card {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 18px;
            margin: 20px 0;
            font-size: 13px;
        }
        .card-row {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
            border-bottom: 1px solid #edf2f7;
        }
        .card-row:last-child {
            border-bottom: none;
        }
        .card-label {
            color: #64748b;
            font-weight: 600;
        }
        .card-val {
            color: #0f172a;
            font-weight: 700;
        }
        .footer {
            background-color: #f1f5f9;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #64748b;
            border-top: 1px solid #e2e8f0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{{ $companyName }}</h1>
            <p>Panel de Gestión de Eventos & DJ</p>
        </div>
        <div class="content">
            <div class="status-badge">
                ✓ Conexión SMTP Exitosa
            </div>
            <h2 style="margin-top: 0; font-size: 18px; color: #0f172a;">¡El servidor de correo está funcionando correctamente!</h2>
            <p style="color: #475569; font-size: 14px;">
                Si estás recibiendo este mensaje, significa que los parámetros de envío de correo electrónico en tu servidor están correctamente configurados y listos para enviar avisos de presupuestos y contratos a tus clientes.
            </p>

            <div class="card">
                <div class="card-row">
                    <span class="card-label">Empresa:</span>
                    <span class="card-val">{{ $companyName }}</span>
                </div>
                <div class="card-row">
                    <span class="card-label">Fecha del test:</span>
                    <span class="card-val">{{ $timestamp }}</span>
                </div>
                <div class="card-row">
                    <span class="card-label">Estado:</span>
                    <span class="card-val" style="color: #059669;">Operativo (200 OK)</span>
                </div>
            </div>

            <p style="color: #64748b; font-size: 13px; margin-bottom: 0;">
                Este es un mensaje automático de diagnóstico enviado desde el panel de administración.
            </p>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} {{ $companyName }} &bull; Sistema de Gestión de Eventos
        </div>
    </div>
</body>
</html>
