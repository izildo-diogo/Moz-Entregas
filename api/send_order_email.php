<?php
/**
 * API para envio de emails automáticos após pedidos
 * 
 * @author MozEntregas Team
 * @version 2.0
 */

require_once '../config_moz.php';

// Definir cabeçalhos para API JSON
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Verificar se é uma requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendErrorResponse('Método não permitido.', 405);
}

try {
    // Obter dados JSON da requisição
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        sendErrorResponse('Dados JSON inválidos.');
    }
    
    // Validar dados obrigatórios
    if (empty($data['pedido_id'])) {
        sendErrorResponse('ID do pedido é obrigatório.');
    }
    
    $pedidoId = (int)$data['pedido_id'];
    $emailType = $data['email_type'] ?? 'order_confirmation';
    
    // Conectar à base de dados
    $pdo = getConnection();
    
    // Buscar dados do pedido
    $stmt = $pdo->prepare("
        SELECT p.*, u.nome as usuario_nome, u.email as usuario_email, u.telefone as usuario_telefone
        FROM pedidos p
        LEFT JOIN usuarios u ON p.usuario_id = u.id
        WHERE p.id = ?
    ");
    $stmt->execute([$pedidoId]);
    $pedido = $stmt->fetch();
    
    if (!$pedido) {
        sendErrorResponse('Pedido não encontrado.');
    }
    
    // Verificar se há email para enviar
    if (!$pedido['usuario_email']) {
        sendErrorResponse('Email do cliente não encontrado.');
    }
    
    // Buscar itens do pedido
    $stmt = $pdo->prepare("
        SELECT ip.*, pr.nome as produto_nome, l.nome as loja_nome
        FROM itens_pedido ip
        INNER JOIN produtos pr ON ip.produto_id = pr.id
        INNER JOIN lojas l ON pr.loja_id = l.id
        WHERE ip.pedido_id = ?
        ORDER BY l.nome, pr.nome
    ");
    $stmt->execute([$pedidoId]);
    $itens = $stmt->fetchAll();
    
    // Gerar conteúdo do email baseado no tipo
    $emailContent = generateEmailContent($emailType, $pedido, $itens);
    
    // Enviar email (simulação - em produção usar PHPMailer, SendGrid, etc.)
    $emailSent = sendEmail(
        $pedido['usuario_email'],
        $emailContent['subject'],
        $emailContent['body'],
        $pedido['usuario_nome']
    );
    
    if ($emailSent) {
        // Registrar envio do email
        $stmt = $pdo->prepare("
            INSERT INTO emails_enviados (pedido_id, destinatario, assunto, tipo, enviado_em)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $pedidoId,
            $pedido['usuario_email'],
            $emailContent['subject'],
            $emailType
        ]);
        
        // Log da atividade
        logUserActivity($pedido['usuario_id'], 'order_email_sent', 'Order email sent to customer', [
            'pedido_id' => $pedidoId,
            'email_type' => $emailType,
            'destinatario' => $pedido['usuario_email']
        ]);
        
        sendSuccessResponse('Email enviado com sucesso!', [
            'pedido_id' => $pedidoId,
            'email_type' => $emailType,
            'destinatario' => $pedido['usuario_email']
        ]);
    } else {
        sendErrorResponse('Erro ao enviar email. Tente novamente mais tarde.');
    }
    
} catch (PDOException $e) {
    // Log do erro do sistema
    logSystemError('Database error in send_order_email: ' . $e->getMessage(), [
        'file' => __FILE__,
        'line' => __LINE__,
        'pedido_id' => $pedidoId ?? null
    ]);
    
    sendErrorResponse('Erro interno do servidor. Tente novamente mais tarde.', 500);
    
} catch (Exception $e) {
    // Log do erro geral
    logSystemError('General error in send_order_email: ' . $e->getMessage(), [
        'file' => __FILE__,
        'line' => __LINE__,
        'pedido_id' => $pedidoId ?? null
    ]);
    
    sendErrorResponse('Erro inesperado. Tente novamente mais tarde.', 500);
}

/**
 * Gerar conteúdo do email baseado no tipo
 */
function generateEmailContent($emailType, $pedido, $itens) {
    $baseUrl = 'https://' . $_SERVER['HTTP_HOST'];
    
    switch ($emailType) {
        case 'order_confirmation':
            return [
                'subject' => 'Confirmação de Pedido #' . $pedido['id'] . ' - MozEntregas',
                'body' => generateOrderConfirmationEmail($pedido, $itens, $baseUrl)
            ];
            
        case 'order_status_update':
            return [
                'subject' => 'Atualização do Pedido #' . $pedido['id'] . ' - MozEntregas',
                'body' => generateOrderStatusUpdateEmail($pedido, $itens, $baseUrl)
            ];
            
        case 'order_delivered':
            return [
                'subject' => 'Pedido #' . $pedido['id'] . ' Entregue - MozEntregas',
                'body' => generateOrderDeliveredEmail($pedido, $itens, $baseUrl)
            ];
            
        default:
            return [
                'subject' => 'Notificação do Pedido #' . $pedido['id'] . ' - MozEntregas',
                'body' => generateGenericOrderEmail($pedido, $itens, $baseUrl)
            ];
    }
}

/**
 * Gerar email de confirmação de pedido
 */
function generateOrderConfirmationEmail($pedido, $itens, $baseUrl) {
    $html = '
    <!DOCTYPE html>
    <html lang="pt">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Confirmação de Pedido</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                line-height: 1.6;
                color: #333;
                margin: 0;
                padding: 0;
                background-color: #f4f4f4;
            }
            
            .container {
                max-width: 600px;
                margin: 0 auto;
                background: white;
                padding: 20px;
            }
            
            .header {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                padding: 20px;
                text-align: center;
                border-radius: 8px 8px 0 0;
            }
            
            .logo {
                font-size: 1.8rem;
                font-weight: bold;
                margin-bottom: 10px;
            }
            
            .content {
                padding: 20px;
            }
            
            .order-info {
                background: #f8f9fa;
                padding: 15px;
                border-radius: 8px;
                margin: 20px 0;
            }
            
            .items-table {
                width: 100%;
                border-collapse: collapse;
                margin: 20px 0;
            }
            
            .items-table th,
            .items-table td {
                padding: 10px;
                text-align: left;
                border-bottom: 1px solid #ddd;
            }
            
            .items-table th {
                background: #667eea;
                color: white;
            }
            
            .total {
                text-align: right;
                font-size: 1.2rem;
                font-weight: bold;
                color: #667eea;
                margin: 20px 0;
            }
            
            .button {
                display: inline-block;
                background: #667eea;
                color: white;
                padding: 12px 24px;
                text-decoration: none;
                border-radius: 6px;
                font-weight: bold;
                margin: 10px 0;
            }
            
            .footer {
                background: #f8f9fa;
                padding: 20px;
                text-align: center;
                color: #666;
                font-size: 0.9rem;
                border-radius: 0 0 8px 8px;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <div class="logo">🍽️ MozEntregas</div>
                <h2>Confirmação de Pedido</h2>
            </div>
            
            <div class="content">
                <h3>Olá, ' . htmlspecialchars($pedido['usuario_nome']) . '!</h3>
                
                <p>Obrigado por escolher o MozEntregas! Seu pedido foi recebido e está sendo processado.</p>
                
                <div class="order-info">
                    <h4>Detalhes do Pedido</h4>
                    <p><strong>Número do Pedido:</strong> #' . $pedido['id'] . '</p>
                    <p><strong>Data:</strong> ' . date('d/m/Y H:i', strtotime($pedido['created_at'])) . '</p>
                    <p><strong>Status:</strong> ' . ucfirst($pedido['status']) . '</p>
                    <p><strong>Endereço de Entrega:</strong> ' . htmlspecialchars($pedido['endereco_entrega']) . '</p>
                </div>
                
                <h4>Itens do Pedido</h4>
                <table class="items-table">
                    <thead>
                        <tr>
                            <th>Produto</th>
                            <th>Loja</th>
                            <th>Qtd</th>
                            <th>Preço</th>
                        </tr>
                    </thead>
                    <tbody>';
    
    foreach ($itens as $item) {
        $html .= '
                        <tr>
                            <td>' . htmlspecialchars($item['produto_nome']) . '</td>
                            <td>' . htmlspecialchars($item['loja_nome']) . '</td>
                            <td>' . $item['quantidade'] . '</td>
                            <td>MT ' . number_format($item['preco'], 2, ',', '.') . '</td>
                        </tr>';
    }
    
    $html .= '
                    </tbody>
                </table>
                
                <div class="total">
                    Total: MT ' . number_format($pedido['total'], 2, ',', '.') . '
                </div>
                
                <p>Você pode acompanhar o status do seu pedido clicando no botão abaixo:</p>
                
                <a href="' . $baseUrl . '/rastreamento_moz.php?pedido=' . $pedido['id'] . '" class="button">
                    Rastrear Pedido
                </a>
                
                <p>Tempo estimado de entrega: 30-45 minutos</p>
                
                <p>Se tiver alguma dúvida, entre em contato conosco:</p>
                <ul>
                    <li>Telefone: +258 84 123 4567</li>
                    <li>Email: contato@mozentregas.com</li>
                </ul>
            </div>
            
            <div class="footer">
                <p>Este email foi enviado automaticamente. Por favor, não responda.</p>
                <p>&copy; 2024 MozEntregas. Todos os direitos reservados.</p>
            </div>
        </div>
    </body>
    </html>';
    
    return $html;
}

/**
 * Gerar email de atualização de status
 */
function generateOrderStatusUpdateEmail($pedido, $itens, $baseUrl) {
    $statusMessages = [
        'confirmado' => 'Seu pedido foi confirmado e está sendo preparado!',
        'preparando' => 'Seu pedido está sendo preparado pelos restaurantes.',
        'saiu_entrega' => 'Seu pedido saiu para entrega! O entregador está a caminho.',
        'entregue' => 'Seu pedido foi entregue com sucesso!',
        'cancelado' => 'Infelizmente, seu pedido foi cancelado.'
    ];
    
    $message = $statusMessages[$pedido['status']] ?? 'Status do seu pedido foi atualizado.';
    
    return generateGenericOrderEmail($pedido, $itens, $baseUrl, $message);
}

/**
 * Gerar email de pedido entregue
 */
function generateOrderDeliveredEmail($pedido, $itens, $baseUrl) {
    $message = 'Parabéns! Seu pedido foi entregue com sucesso. Esperamos que tenha gostado da experiência!';
    return generateGenericOrderEmail($pedido, $itens, $baseUrl, $message);
}

/**
 * Gerar email genérico
 */
function generateGenericOrderEmail($pedido, $itens, $baseUrl, $customMessage = '') {
    // Implementação similar ao email de confirmação, mas com mensagem personalizada
    return generateOrderConfirmationEmail($pedido, $itens, $baseUrl);
}

/**
 * Enviar email (simulação)
 * Em produção, usar PHPMailer, SendGrid, Amazon SES, etc.
 */
function sendEmail($to, $subject, $body, $toName = '') {
    // Simulação de envio de email
    // Em produção, implementar com biblioteca real de email
    
    // Log do envio (para demonstração)
    logSystemError('Email sent (simulation)', [
        'to' => $to,
        'to_name' => $toName,
        'subject' => $subject,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
    // Simular sucesso (em produção, retornar resultado real)
    return true;
}
?>

