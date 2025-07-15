<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Termos de Uso - MozEntregas</title>
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

        .terms-content {
            margin-bottom: 2rem;
        }

        .terms-content h2 {
            color: #667eea;
            margin: 2rem 0 1rem 0;
            font-size: 1.5rem;
            border-bottom: 2px solid #e1e5e9;
            padding-bottom: 0.5rem;
        }

        .terms-content h3 {
            color: #555;
            margin: 1.5rem 0 0.5rem 0;
            font-size: 1.2rem;
        }

        .terms-content p {
            margin-bottom: 1rem;
            text-align: justify;
        }

        .terms-content ul, .terms-content ol {
            margin: 1rem 0 1rem 2rem;
        }

        .terms-content li {
            margin-bottom: 0.5rem;
        }

        .highlight {
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

        @media (max-width: 768px) {
            .container {
                margin: 1rem;
                padding: 1.5rem;
            }

            .header h1 {
                font-size: 2rem;
            }

            .terms-content h2 {
                font-size: 1.3rem;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1><i class="fas fa-file-contract"></i> Termos de Uso</h1>
        <p>MozEntregas - Plataforma de Delivery</p>
    </div>

    <div class="container">
        <a href="javascript:history.back()" class="back-button">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>

        <div class="last-updated">
            <strong>Última atualização:</strong> <?= date('d/m/Y') ?>
        </div>

        <div class="terms-content">
            <h2>1. Aceitação dos Termos</h2>
            <p>
                Ao acessar e usar a plataforma MozEntregas, você concorda em cumprir e estar vinculado a estes Termos de Uso. 
                Se você não concordar com qualquer parte destes termos, não deve usar nossos serviços.
            </p>

            <h2>2. Descrição do Serviço</h2>
            <p>
                O MozEntregas é uma plataforma digital que conecta usuários a restaurantes e estabelecimentos comerciais 
                em Moçambique, facilitando pedidos de comida e produtos para entrega domiciliar.
            </p>

            <h3>2.1 Serviços Oferecidos</h3>
            <ul>
                <li>Catálogo online de restaurantes e produtos</li>
                <li>Sistema de pedidos e pagamentos</li>
                <li>Serviço de entrega através de parceiros</li>
                <li>Rastreamento de pedidos em tempo real</li>
                <li>Atendimento ao cliente</li>
            </ul>

            <h2>3. Cadastro e Conta do Usuário</h2>
            <p>
                Para usar nossos serviços, você deve criar uma conta fornecendo informações precisas e atualizadas.
            </p>

            <h3>3.1 Responsabilidades do Usuário</h3>
            <ul>
                <li>Fornecer informações verdadeiras e atualizadas</li>
                <li>Manter a confidencialidade da sua senha</li>
                <li>Notificar imediatamente sobre uso não autorizado da conta</li>
                <li>Ser responsável por todas as atividades em sua conta</li>
            </ul>

            <h3>3.2 Requisitos de Idade</h3>
            <p>
                Você deve ter pelo menos 18 anos de idade para criar uma conta e usar nossos serviços. 
                Menores de idade podem usar a plataforma apenas sob supervisão de um responsável legal.
            </p>

            <h2>4. Pedidos e Pagamentos</h2>
            
            <h3>4.1 Processo de Pedidos</h3>
            <ul>
                <li>Os pedidos são processados conforme disponibilidade dos estabelecimentos</li>
                <li>Preços e disponibilidade podem variar sem aviso prévio</li>
                <li>Reservamo-nos o direito de recusar ou cancelar pedidos</li>
            </ul>

            <h3>4.2 Métodos de Pagamento</h3>
            <p>
                Aceitamos os seguintes métodos de pagamento:
            </p>
            <ul>
                <li>M-Pesa</li>
                <li>Cartão de crédito/débito</li>
                <li>Dinheiro na entrega (quando disponível)</li>
            </ul>

            <div class="highlight">
                <strong>Importante:</strong> Todos os pagamentos são processados de forma segura. 
                Não armazenamos informações completas de cartão de crédito em nossos servidores.
            </div>

            <h2>5. Entrega</h2>
            
            <h3>5.1 Áreas de Entrega</h3>
            <p>
                Atualmente operamos nas seguintes áreas de Maputo:
            </p>
            <ul>
                <li>Centro da cidade</li>
                <li>Polana</li>
                <li>Sommerschield</li>
                <li>Costa do Sol</li>
                <li>Outras áreas conforme disponibilidade</li>
            </ul>

            <h3>5.2 Tempos de Entrega</h3>
            <p>
                Os tempos de entrega são estimativas e podem variar devido a:
            </p>
            <ul>
                <li>Condições climáticas</li>
                <li>Tráfego</li>
                <li>Volume de pedidos</li>
                <li>Disponibilidade de entregadores</li>
            </ul>

            <h2>6. Cancelamentos e Reembolsos</h2>
            
            <h3>6.1 Cancelamento pelo Cliente</h3>
            <ul>
                <li>Pedidos podem ser cancelados antes da confirmação pelo restaurante</li>
                <li>Após confirmação, cancelamentos estão sujeitos à política do estabelecimento</li>
                <li>Reembolsos são processados no método de pagamento original</li>
            </ul>

            <h3>6.2 Cancelamento pela Plataforma</h3>
            <p>
                Podemos cancelar pedidos em casos de:
            </p>
            <ul>
                <li>Indisponibilidade de produtos</li>
                <li>Problemas técnicos</li>
                <li>Condições climáticas adversas</li>
                <li>Suspeita de fraude</li>
            </ul>

            <h2>7. Conduta do Usuário</h2>
            
            <h3>7.1 Uso Proibido</h3>
            <p>É proibido usar a plataforma para:</p>
            <ul>
                <li>Atividades ilegais ou fraudulentas</li>
                <li>Assédio ou comportamento abusivo</li>
                <li>Spam ou comunicações não solicitadas</li>
                <li>Violação de direitos de propriedade intelectual</li>
                <li>Interferência no funcionamento da plataforma</li>
            </ul>

            <h2>8. Privacidade e Proteção de Dados</h2>
            <p>
                Sua privacidade é importante para nós. Coletamos e usamos suas informações pessoais 
                conforme descrito em nossa Política de Privacidade, que faz parte integrante destes termos.
            </p>

            <h3>8.1 Dados Coletados</h3>
            <ul>
                <li>Informações de cadastro (nome, email, telefone)</li>
                <li>Endereços de entrega</li>
                <li>Histórico de pedidos</li>
                <li>Dados de pagamento (de forma segura)</li>
                <li>Informações de localização (quando autorizado)</li>
            </ul>

            <h2>9. Propriedade Intelectual</h2>
            <p>
                Todo o conteúdo da plataforma MozEntregas, incluindo textos, gráficos, logos, ícones, 
                imagens e software, é propriedade da empresa ou de seus licenciadores e está protegido 
                por leis de direitos autorais e propriedade intelectual.
            </p>

            <h2>10. Limitação de Responsabilidade</h2>
            <p>
                O MozEntregas atua como intermediário entre clientes e estabelecimentos. 
                Nossa responsabilidade é limitada ao valor do pedido e não nos responsabilizamos por:
            </p>
            <ul>
                <li>Qualidade dos alimentos fornecidos pelos restaurantes</li>
                <li>Alergias alimentares ou reações adversas</li>
                <li>Danos indiretos ou consequenciais</li>
                <li>Perda de dados ou interrupção de serviços</li>
            </ul>

            <h2>11. Modificações dos Termos</h2>
            <p>
                Reservamo-nos o direito de modificar estes Termos de Uso a qualquer momento. 
                As alterações entrarão em vigor imediatamente após a publicação na plataforma. 
                O uso continuado dos serviços constitui aceitação dos termos modificados.
            </p>

            <h2>12. Rescisão</h2>
            <p>
                Podemos suspender ou encerrar sua conta a qualquer momento, com ou sem aviso, 
                por violação destes termos ou por qualquer outro motivo que consideremos apropriado.
            </p>

            <h2>13. Lei Aplicável</h2>
            <p>
                Estes Termos de Uso são regidos pelas leis da República de Moçambique. 
                Qualquer disputa será resolvida nos tribunais competentes de Maputo.
            </p>

            <h2>14. Contato</h2>
            <p>
                Para questões sobre estes Termos de Uso, entre em contato conosco:
            </p>
            <ul>
                <li><strong>Email:</strong> legal@mozentregas.com</li>
                <li><strong>Telefone:</strong> +258 84 123 4567</li>
                <li><strong>Endereço:</strong> Av. Julius Nyerere, Maputo, Moçambique</li>
            </ul>

            <div class="highlight">
                <strong>Nota:</strong> Ao usar a plataforma MozEntregas, você confirma que leu, 
                compreendeu e concorda em estar vinculado a estes Termos de Uso.
            </div>
        </div>
    </div>
</body>
</html>

