<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Política de Privacidade - MozEntregas</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background: #f8f9fa;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem 0;
            text-align: center;
        }

        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }

        .header p {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem;
            background: white;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-top: -1rem;
            border-radius: 10px 10px 0 0;
        }

        .privacy-content {
            margin-bottom: 2rem;
        }

        .privacy-content h2 {
            color: #667eea;
            margin: 2rem 0 1rem 0;
            font-size: 1.5rem;
            border-bottom: 2px solid #e1e5e9;
            padding-bottom: 0.5rem;
        }

        .privacy-content h3 {
            color: #555;
            margin: 1.5rem 0 0.5rem 0;
            font-size: 1.2rem;
        }

        .privacy-content p {
            margin-bottom: 1rem;
            text-align: justify;
        }

        .privacy-content ul, .privacy-content ol {
            margin: 1rem 0 1rem 2rem;
        }

        .privacy-content li {
            margin-bottom: 0.5rem;
        }

        .highlight {
            background: #d1ecf1;
            padding: 1rem;
            border-left: 4px solid #17a2b8;
            margin: 1rem 0;
            border-radius: 4px;
        }

        .warning {
            background: #fff3cd;
            padding: 1rem;
            border-left: 4px solid #ffc107;
            margin: 1rem 0;
            border-radius: 4px;
        }

        .back-button {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
            margin-bottom: 2rem;
        }

        .back-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        .last-updated {
            background: #e9ecef;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            text-align: center;
            font-style: italic;
            color: #6c757d;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin: 1rem 0;
        }

        .data-table th,
        .data-table td {
            border: 1px solid #dee2e6;
            padding: 0.75rem;
            text-align: left;
        }

        .data-table th {
            background: #f8f9fa;
            font-weight: 600;
        }

        @media (max-width: 768px) {
            .container {
                margin: 1rem;
                padding: 1.5rem;
            }

            .header h1 {
                font-size: 2rem;
            }

            .privacy-content h2 {
                font-size: 1.3rem;
            }

            .data-table {
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1><i class="fas fa-shield-alt"></i> Política de Privacidade</h1>
        <p>MozEntregas - Proteção dos seus dados</p>
    </div>

    <div class="container">
        <a href="javascript:history.back()" class="back-button">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>

        <div class="last-updated">
            <strong>Última atualização:</strong> <?= date('d/m/Y') ?>
        </div>

        <div class="privacy-content">
            <h2>1. Introdução</h2>
            <p>
                A MozEntregas está comprometida em proteger a privacidade e segurança dos dados pessoais 
                dos nossos usuários. Esta Política de Privacidade explica como coletamos, usamos, 
                armazenamos e protegemos suas informações pessoais.
            </p>

            <div class="highlight">
                <strong>Compromisso:</strong> Respeitamos sua privacidade e seguimos as melhores práticas 
                internacionais de proteção de dados, incluindo princípios do GDPR (Regulamento Geral 
                sobre a Proteção de Dados).
            </div>

            <h2>2. Informações que Coletamos</h2>
            
            <h3>2.1 Informações Fornecidas Diretamente</h3>
            <p>Coletamos informações que você nos fornece diretamente, incluindo:</p>
            
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Tipo de Informação</th>
                        <th>Exemplos</th>
                        <th>Finalidade</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Dados de Cadastro</td>
                        <td>Nome, email, telefone</td>
                        <td>Criação e gestão da conta</td>
                    </tr>
                    <tr>
                        <td>Endereços</td>
                        <td>Endereços de entrega</td>
                        <td>Processamento e entrega de pedidos</td>
                    </tr>
                    <tr>
                        <td>Dados de Pagamento</td>
                        <td>Informações de cartão, M-Pesa</td>
                        <td>Processamento de pagamentos</td>
                    </tr>
                    <tr>
                        <td>Preferências</td>
                        <td>Restaurantes favoritos, histórico</td>
                        <td>Personalização da experiência</td>
                    </tr>
                </tbody>
            </table>

            <h3>2.2 Informações Coletadas Automaticamente</h3>
            <ul>
                <li><strong>Dados de Navegação:</strong> Páginas visitadas, tempo de permanência, cliques</li>
                <li><strong>Informações do Dispositivo:</strong> Tipo de dispositivo, sistema operacional, navegador</li>
                <li><strong>Dados de Localização:</strong> Localização aproximada (quando autorizada)</li>
                <li><strong>Cookies e Tecnologias Similares:</strong> Para melhorar a experiência do usuário</li>
            </ul>

            <h3>2.3 Informações de Terceiros</h3>
            <p>
                Podemos receber informações de parceiros comerciais, como restaurantes e serviços de pagamento, 
                sempre dentro dos limites legais e com seu consentimento.
            </p>

            <h2>3. Como Usamos suas Informações</h2>
            
            <h3>3.1 Finalidades Principais</h3>
            <ul>
                <li><strong>Prestação de Serviços:</strong> Processar pedidos, coordenar entregas, atendimento ao cliente</li>
                <li><strong>Comunicação:</strong> Confirmações de pedido, atualizações de entrega, suporte</li>
                <li><strong>Pagamentos:</strong> Processar transações e emitir recibos</li>
                <li><strong>Segurança:</strong> Prevenir fraudes e proteger a plataforma</li>
            </ul>

            <h3>3.2 Finalidades Secundárias</h3>
            <ul>
                <li><strong>Personalização:</strong> Recomendar restaurantes e produtos</li>
                <li><strong>Marketing:</strong> Enviar ofertas e promoções (com seu consentimento)</li>
                <li><strong>Análise:</strong> Melhorar nossos serviços e desenvolver novos recursos</li>
                <li><strong>Pesquisa:</strong> Realizar pesquisas de satisfação e feedback</li>
            </ul>

            <h2>4. Base Legal para Processamento</h2>
            <p>Processamos seus dados pessoais com base em:</p>
            
            <ul>
                <li><strong>Execução de Contrato:</strong> Para fornecer os serviços solicitados</li>
                <li><strong>Consentimento:</strong> Para marketing e comunicações promocionais</li>
                <li><strong>Interesse Legítimo:</strong> Para segurança, prevenção de fraudes e melhorias</li>
                <li><strong>Obrigação Legal:</strong> Para cumprir requisitos legais e regulamentares</li>
            </ul>

            <h2>5. Compartilhamento de Informações</h2>
            
            <h3>5.1 Parceiros de Serviço</h3>
            <p>Compartilhamos informações com:</p>
            <ul>
                <li><strong>Restaurantes:</strong> Detalhes do pedido para preparação</li>
                <li><strong>Entregadores:</strong> Informações necessárias para entrega</li>
                <li><strong>Processadores de Pagamento:</strong> Para processar transações</li>
                <li><strong>Provedores de Tecnologia:</strong> Para manutenção e suporte da plataforma</li>
            </ul>

            <h3>5.2 Situações Especiais</h3>
            <p>Podemos divulgar informações quando:</p>
            <ul>
                <li>Exigido por lei ou ordem judicial</li>
                <li>Necessário para proteger direitos, propriedade ou segurança</li>
                <li>Em caso de fusão, aquisição ou venda de ativos</li>
                <li>Com seu consentimento explícito</li>
            </ul>

            <div class="warning">
                <strong>Importante:</strong> Nunca vendemos suas informações pessoais para terceiros 
                para fins de marketing direto.
            </div>

            <h2>6. Segurança dos Dados</h2>
            
            <h3>6.1 Medidas de Proteção</h3>
            <ul>
                <li><strong>Criptografia:</strong> Dados sensíveis são criptografados em trânsito e em repouso</li>
                <li><strong>Controle de Acesso:</strong> Acesso limitado apenas a funcionários autorizados</li>
                <li><strong>Monitoramento:</strong> Sistemas de detecção de intrusão e atividades suspeitas</li>
                <li><strong>Atualizações:</strong> Sistemas regularmente atualizados com patches de segurança</li>
            </ul>

            <h3>6.2 Armazenamento Seguro</h3>
            <p>
                Seus dados são armazenados em servidores seguros com backup regular. 
                Informações de pagamento são processadas através de provedores certificados PCI DSS.
            </p>

            <h2>7. Seus Direitos</h2>
            <p>Você tem os seguintes direitos em relação aos seus dados pessoais:</p>
            
            <ul>
                <li><strong>Acesso:</strong> Solicitar cópia dos dados que temos sobre você</li>
                <li><strong>Retificação:</strong> Corrigir informações incorretas ou incompletas</li>
                <li><strong>Exclusão:</strong> Solicitar a remoção de seus dados (direito ao esquecimento)</li>
                <li><strong>Portabilidade:</strong> Receber seus dados em formato estruturado</li>
                <li><strong>Restrição:</strong> Limitar o processamento de seus dados</li>
                <li><strong>Oposição:</strong> Opor-se ao processamento para fins de marketing</li>
                <li><strong>Retirada de Consentimento:</strong> Retirar consentimento a qualquer momento</li>
            </ul>

            <h3>7.1 Como Exercer seus Direitos</h3>
            <p>Para exercer qualquer destes direitos, entre em contato conosco através de:</p>
            <ul>
                <li><strong>Email:</strong> privacidade@mozentregas.com</li>
                <li><strong>Telefone:</strong> +258 84 123 4567</li>
                <li><strong>Formulário online:</strong> Disponível na seção "Minha Conta"</li>
            </ul>

            <h2>8. Retenção de Dados</h2>
            <p>Mantemos seus dados pessoais apenas pelo tempo necessário para:</p>
            
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Tipo de Dados</th>
                        <th>Período de Retenção</th>
                        <th>Justificativa</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Dados da Conta</td>
                        <td>Enquanto a conta estiver ativa + 2 anos</td>
                        <td>Prestação de serviços e obrigações legais</td>
                    </tr>
                    <tr>
                        <td>Histórico de Pedidos</td>
                        <td>5 anos</td>
                        <td>Suporte ao cliente e questões fiscais</td>
                    </tr>
                    <tr>
                        <td>Dados de Pagamento</td>
                        <td>Conforme exigido pelos processadores</td>
                        <td>Prevenção de fraudes e chargebacks</td>
                    </tr>
                    <tr>
                        <td>Logs de Sistema</td>
                        <td>1 ano</td>
                        <td>Segurança e resolução de problemas</td>
                    </tr>
                </tbody>
            </table>

            <h2>9. Cookies e Tecnologias de Rastreamento</h2>
            
            <h3>9.1 Tipos de Cookies</h3>
            <ul>
                <li><strong>Essenciais:</strong> Necessários para o funcionamento da plataforma</li>
                <li><strong>Funcionais:</strong> Lembram suas preferências e configurações</li>
                <li><strong>Analíticos:</strong> Ajudam a entender como você usa nossos serviços</li>
                <li><strong>Marketing:</strong> Usados para personalizar anúncios (com consentimento)</li>
            </ul>

            <h3>9.2 Gestão de Cookies</h3>
            <p>
                Você pode gerenciar cookies através das configurações do seu navegador ou 
                através do nosso centro de preferências de cookies.
            </p>

            <h2>10. Transferências Internacionais</h2>
            <p>
                Seus dados podem ser transferidos para países fora de Moçambique para processamento 
                por nossos parceiros de serviço. Garantimos que tais transferências atendam aos 
                padrões adequados de proteção de dados.
            </p>

            <h2>11. Menores de Idade</h2>
            <p>
                Nossos serviços não são direcionados a menores de 18 anos. Não coletamos 
                intencionalmente informações pessoais de menores. Se tomarmos conhecimento 
                de que coletamos dados de um menor, tomaremos medidas para excluí-los.
            </p>

            <h2>12. Alterações nesta Política</h2>
            <p>
                Podemos atualizar esta Política de Privacidade periodicamente. Notificaremos 
                sobre mudanças significativas através de:
            </p>
            <ul>
                <li>Aviso na plataforma</li>
                <li>Email para usuários registrados</li>
                <li>Notificação push (se habilitada)</li>
            </ul>

            <h2>13. Contato e Reclamações</h2>
            
            <h3>13.1 Encarregado de Proteção de Dados</h3>
            <p>Para questões relacionadas à privacidade, entre em contato com nosso DPO:</p>
            <ul>
                <li><strong>Email:</strong> dpo@mozentregas.com</li>
                <li><strong>Telefone:</strong> +258 84 123 4567</li>
                <li><strong>Endereço:</strong> Av. Julius Nyerere, Maputo, Moçambique</li>
            </ul>

            <h3>13.2 Autoridade de Supervisão</h3>
            <p>
                Se não estivermos conseguindo resolver suas preocupações, você tem o direito 
                de apresentar uma reclamação à autoridade de proteção de dados competente.
            </p>

            <div class="highlight">
                <strong>Transparência:</strong> Estamos comprometidos em ser transparentes sobre 
                nossas práticas de dados. Se tiver dúvidas, não hesite em nos contactar.
            </div>
        </div>
    </div>
</body>
</html>

