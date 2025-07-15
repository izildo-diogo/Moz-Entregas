<?php
/**
 * API para gerar recibos em PDF
 * 
 * @author MozEntregas Team
 * @version 2.0
 */

require_once '../config_moz.php';

// Verificar se usuário está logado
$currentUser = getCurrentUser();
if (!$currentUser) {
    header('Location: ../login.php?message=Você precisa fazer login para acessar esta funcionalidade.&type=error');
    exit;
}

// Verificar se ID do pedido foi fornecido
if (empty($_GET['pedido_id'])) {
    header('Location: ../pedidos_moz.php?message=ID do pedido não fornecido.&type=error');
    exit;
}

$pedidoId = (int)$_GET['pedido_id'];

try {
    $pdo = getConnection();
    
    // Buscar dados do pedido
    $stmt = $pdo->prepare("
        SELECT p.*, u.nome as usuario_nome, u.email as usuario_email, u.telefone as usuario_telefone
        FROM pedidos p
        LEFT JOIN usuarios u ON p.usuario_id = u.id
        WHERE p.id = ? AND (p.usuario_id = ? OR p.session_id = ?)
    ");
    $stmt->execute([$pedidoId, $currentUser['id'], session_id()]);
    $pedido = $stmt->fetch();
    
    if (!$pedido) {
        header('Location: ../pedidos_moz.php?message=Pedido não encontrado.&type=error');
        exit;
    }
    
    // Buscar itens do pedido
    $stmt = $pdo->prepare("
        SELECT ip.*, pr.nome as produto_nome, pr.descricao as produto_descricao,
               l.nome as loja_nome, l.localizacao as loja_localizacao
        FROM itens_pedido ip
        INNER JOIN produtos pr ON ip.produto_id = pr.id
        INNER JOIN lojas l ON pr.loja_id = l.id
        WHERE ip.pedido_id = ?
        ORDER BY l.nome, pr.nome
    ");
    $stmt->execute([$pedidoId]);
    $itens = $stmt->fetchAll();
    
    // Gerar HTML do recibo
    $html = generateReceiptHTML($pedido, $itens);
    
    // Configurar cabeçalhos para PDF
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="recibo_pedido_' . $pedidoId . '.pdf"');
    
    // Gerar PDF usando biblioteca (simulação - em produção usar TCPDF, FPDF ou similar)
    // Por agora, vamos retornar o HTML para demonstração
    header('Content-Type: text/html; charset=utf-8');
    echo $html;
    
    // Log da atividade
    logUserActivity($currentUser['id'], 'receipt_downloaded', 'Downloaded receipt PDF', [
        'pedido_id' => $pedidoId,
        'total' => $pedido['total']
    ]);
    
} catch(PDOException $e) {
    logSystemError('Database error in generate_receipt_pdf: ' . $e->getMessage());
    header('Location: ../pedidos_moz.php?message=Erro ao gerar recibo.&type=error');
    exit;
}

/**
 * Gerar HTML do recibo
 */
function generateReceiptHTML($pedido, $itens) {
    $html = '
    <!DOCTYPE html>
    <html lang="pt">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Recibo - Pedido #' . $pedido['id'] . '</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                margin: 0;
                padding: 20px;
                color: #333;
                line-height: 1.6;
            }
            
            .receipt-container {
                max-width: 800px;
                margin: 0 auto;
                background: white;
                padding: 30px;
                border: 1px solid #ddd;
            }
            
            .header {
                text-align: center;
                margin-bottom: 30px;
                border-bottom: 2px solid #667eea;
                padding-bottom: 20px;
            }
            
            .logo {
                font-size: 2rem;
                font-weight: bold;
                color: #667eea;
                margin-bottom: 10px;
            }
            
            .company-info {
                color: #666;
                font-size: 0.9rem;
            }
            
            .receipt-title {
                font-size: 1.5rem;
                font-weight: bold;
                margin: 20px 0;
                text-align: center;
                color: #333;
            }
            
            .order-info {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 20px;
                margin-bottom: 30px;
            }
            
            .info-section {
                background: #f8f9fa;
                padding: 15px;
                border-radius: 8px;
            }
            
            .info-title {
                font-weight: bold;
                color: #667eea;
                margin-bottom: 10px;
                font-size: 1.1rem;
            }
            
            .info-item {
                margin-bottom: 5px;
            }
            
            .items-table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 20px;
            }
            
            .items-table th,
            .items-table td {
                padding: 12px;
                text-align: left;
                border-bottom: 1px solid #ddd;
            }
            
            .items-table th {
                background: #667eea;
                color: white;
                font-weight: bold;
            }
            
            .items-table tr:nth-child(even) {
                background: #f8f9fa;
            }
            
            .total-section {
                text-align: right;
                margin-top: 20px;
                padding-top: 20px;
                border-top: 2px solid #667eea;
            }
            
            .total-row {
                display: flex;
                justify-content: space-between;
                margin-bottom: 10px;
                padding: 5px 0;
            }
            
            .total-final {
                font-size: 1.3rem;
                font-weight: bold;
                color: #667eea;
                border-top: 1px solid #ddd;
                padding-top: 10px;
                margin-top: 10px;
            }
            
            .footer {
                margin-top: 40px;
                text-align: center;
                color: #666;
                font-size: 0.9rem;
                border-top: 1px solid #ddd;
                padding-top: 20px;
            }
            
            .status-badge {
                display: inline-block;
                padding: 5px 15px;
                border-radius: 20px;
                font-size: 0.8rem;
                font-weight: bold;
                text-transform: uppercase;
            }
            
            .status-confirmado {
                background: #d4edda;
                color: #155724;
            }
            
            .status-pendente {
                background: #fff3cd;
                color: #856404;
            }
            
            .status-cancelado {
                background: #f8d7da;
                color: #721c24;
            }
            
            .status-entregue {
                background: #d1ecf1;
                color: #0c5460;
            }
            
            @media print {
                body {
                    margin: 0;
                    padding: 0;
                }
                
                .receipt-container {
                    border: none;
                    box-shadow: none;
                    padding: 20px;
                }
            }
        </style>
    </head>
    <body>
        <div class="receipt-container">
            <!-- Header -->
            <div class="header">
                <div class="logo">🍽️ MozEntregas</div>
                <div class="company-info">
                    Delivery de Comida em Moçambique<br>
                    Email: contato@mozentregas.com | Telefone: +258 84 123 4567<br>
                    Av. Julius Nyerere, Maputo, Moçambique
                </div>
            </div>
            
            <!-- Receipt Title -->
            <div class="receipt-title">RECIBO DE PEDIDO</div>
            
            <!-- Order Information -->
            <div class="order-info">
                <div class="info-section">
                    <div class="info-title">Informações do Pedido</div>
                    <div class="info-item"><strong>Número:</strong> #' . $pedido['id'] . '</div>
                    <div class="info-item"><strong>Data:</strong> ' . date('d/m/Y H:i', strtotime($pedido['created_at'])) . '</div>
                    <div class="info-item"><strong>Status:</strong> 
                        <span class="status-badge status-' . $pedido['status'] . '">' . ucfirst($pedido['status']) . '</span>
                    </div>
                    <div class="info-item"><strong>Método de Pagamento:</strong> ' . ucfirst($pedido['metodo_pagamento']) . '</div>
                </div>
                
                <div class="info-section">
                    <div class="info-title">Informações do Cliente</div>
                    <div class="info-item"><strong>Nome:</strong> ' . htmlspecialchars($pedido['usuario_nome'] ?: 'Cliente Convidado') . '</div>';
    
    if ($pedido['usuario_email']) {
        $html .= '<div class="info-item"><strong>Email:</strong> ' . htmlspecialchars($pedido['usuario_email']) . '</div>';
    }
    
    if ($pedido['usuario_telefone']) {
        $html .= '<div class="info-item"><strong>Telefone:</strong> ' . htmlspecialchars($pedido['usuario_telefone']) . '</div>';
    }
    
    $html .= '
                    <div class="info-item"><strong>Endereço:</strong> ' . htmlspecialchars($pedido['endereco_entrega']) . '</div>
                </div>
            </div>
            
            <!-- Items Table -->
            <table class="items-table">
                <thead>
                    <tr>
                        <th>Produto</th>
                        <th>Loja</th>
                        <th>Quantidade</th>
                        <th>Preço Unit.</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>';
    
    $subtotal = 0;
    foreach ($itens as $item) {
        $itemTotal = $item['preco'] * $item['quantidade'];
        $subtotal += $itemTotal;
        
        $html .= '
                    <tr>
                        <td>
                            <strong>' . htmlspecialchars($item['produto_nome']) . '</strong><br>
                            <small>' . htmlspecialchars($item['produto_descricao']) . '</small>
                        </td>
                        <td>' . htmlspecialchars($item['loja_nome']) . '</td>
                        <td>' . $item['quantidade'] . '</td>
                        <td>MT ' . number_format($item['preco'], 2, ',', '.') . '</td>
                        <td>MT ' . number_format($itemTotal, 2, ',', '.') . '</td>
                    </tr>';
    }
    
    $html .= '
                </tbody>
            </table>
            
            <!-- Total Section -->
            <div class="total-section">
                <div class="total-row">
                    <span>Subtotal:</span>
                    <span>MT ' . number_format($subtotal, 2, ',', '.') . '</span>
                </div>
                <div class="total-row">
                    <span>Taxa de Entrega:</span>
                    <span>MT ' . number_format($pedido['taxa_entrega'], 2, ',', '.') . '</span>
                </div>';
    
    if ($pedido['desconto'] > 0) {
        $html .= '
                <div class="total-row">
                    <span>Desconto:</span>
                    <span>-MT ' . number_format($pedido['desconto'], 2, ',', '.') . '</span>
                </div>';
    }
    
    $html .= '
                <div class="total-row total-final">
                    <span>TOTAL:</span>
                    <span>MT ' . number_format($pedido['total'], 2, ',', '.') . '</span>
                </div>
            </div>
            
            <!-- Footer -->
            <div class="footer">
                <p><strong>Obrigado por escolher o MozEntregas!</strong></p>
                <p>Este recibo foi gerado automaticamente em ' . date('d/m/Y H:i') . '</p>
                <p>Para dúvidas ou suporte, entre em contato conosco através dos canais acima.</p>
            </div>
        </div>
    </body>
    </html>';
    
    return $html;
}
?>

