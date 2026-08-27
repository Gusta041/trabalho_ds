<?php
session_start();

// Trava de Segurança
if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}

$host = 'localhost';
$db   = 'moneyball_db';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro de conexão: " . $e->getMessage());
}

$usuario_id   = $_SESSION['usuario_id'];
$usuario_nome = $_SESSION['usuario_nome'] ?? 'Atleta';

// Busca dados do usuário
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = :id");
$stmt->execute([':id' => $usuario_id]);
$dados_usuario = $stmt->fetch(PDO::FETCH_ASSOC);

// Busca dados do jogador (se existir)
$jogador = null;
try {
    $stmt_jog = $pdo->prepare("SELECT * FROM jogadores WHERE id = :id LIMIT 1");
    $stmt_jog->execute([':id' => $usuario_id]);
    $jogador = $stmt_jog->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $jogador = null;
}

// Dados mockados para demonstração (substitua por queries reais depois)
$stats = [
    'jogos' => 18,
    'pontos' => 240,
    'ataques' => 64,
    'bloqueios' => 32,
    'aces' => 14,
    'passe' => 78,
    'interesses' => 2
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel do Atleta | VôleiHub</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* ===== ESTILOS ESPECÍFICOS DO PAINEL DO ATLETA ===== */
        .tab-content {
            display: none;
            animation: fadeIn 0.3s ease;
        }
        .tab-content.active {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 28px;
        }

        .topbar h2 {
            font-size: 22px;
            font-weight: 700;
            margin: 0;
        }

        .profile-header {
            display: flex;
            align-items: center;
            gap: 24px;
            margin-bottom: 28px;
            padding-bottom: 24px;
            border-bottom: 1px solid var(--border);
        }

        .profile-avatar-lg {
            width: 90px;
            height: 90px;
            background: linear-gradient(135deg, var(--royal), var(--petroleum));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            font-weight: 700;
            color: white;
            flex-shrink: 0;
        }

        .profile-info h3 {
            font-size: 22px;
            margin-bottom: 4px;
        }

        .profile-info p {
            color: var(--light-gray);
            font-size: 14px;
            margin-bottom: 10px;
        }

        .profile-tags {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .tag {
            background: rgba(43, 92, 255, 0.12);
            color: var(--royal);
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 18px;
        }

        .info-item {
            background: var(--darker);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 16px;
        }

        .info-item label {
            display: block;
            font-size: 12px;
            color: var(--light-gray);
            margin-bottom: 6px;
        }

        .info-item strong {
            font-size: 15px;
        }

        /* Gráficos */
        .charts-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-top: 20px;
        }

        .chart-card {
            background: var(--darker);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 20px;
        }

        .chart-card h4 {
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 16px;
            color: var(--light-gray);
        }

        .chart-container {
            position: relative;
            height: 260px;
        }

        /* Progress bars */
        .metric-row {
            margin-bottom: 18px;
        }

        .metric-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 6px;
            font-size: 13px;
        }

        .metric-header span:last-child {
            color: var(--royal);
            font-weight: 600;
        }

        .progress-bar {
            height: 8px;
            background: var(--gray);
            border-radius: 10px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            border-radius: 10px;
            background: linear-gradient(90deg, var(--royal), #5B8CFF);
            transition: width 0.8s ease;
        }

        /* Tabela de propostas */
        .proposta-status {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }

        .status-pendente {
            background: rgba(234, 179, 8, 0.15);
            color: #EAB308;
        }

        .status-aceito {
            background: rgba(34, 197, 94, 0.15);
            color: var(--success);
        }

        .status-recusado {
            background: rgba(239, 68, 68, 0.15);
            color: #F87171;
        }

        .action-btns {
            display: flex;
            gap: 6px;
        }

        .btn-accept {
            background: rgba(34, 197, 94, 0.15);
            color: var(--success);
            border: 1px solid rgba(34, 197, 94, 0.3);
            padding: 5px 10px;
            border-radius: 6px;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-accept:hover {
            background: var(--success);
            color: white;
        }

        .btn-reject {
            background: rgba(239, 68, 68, 0.1);
            color: #F87171;
            border: 1px solid rgba(239, 68, 68, 0.25);
            padding: 5px 10px;
            border-radius: 6px;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-reject:hover {
            background: #EF4444;
            color: white;
        }

        /* Suporte */
        .support-form {
            display: grid;
            gap: 16px;
            max-width: 560px;
        }

        .support-form .form-group label {
            display: block;
            margin-bottom: 6px;
            font-size: 13px;
            color: var(--light-gray);
        }

        .support-form input,
        .support-form select,
        .support-form textarea {
            width: 100%;
            background: var(--darker);
            border: 1px solid var(--border);
            color: var(--white);
            padding: 11px 14px;
            border-radius: 8px;
            font-size: 13px;
            outline: none;
            font-family: inherit;
        }

        .support-form input:focus,
        .support-form select:focus,
        .support-form textarea:focus {
            border-color: var(--royal);
        }

        .ticket-list {
            margin-top: 28px;
        }

        .ticket-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 14px 0;
            border-bottom: 1px solid var(--border);
        }

        .ticket-item:last-child {
            border-bottom: none;
        }

        .ticket-info strong {
            display: block;
            font-size: 14px;
            margin-bottom: 2px;
        }

        .ticket-info small {
            color: var(--light-gray);
            font-size: 12px;
        }

        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: var(--light-gray);
        }

        .empty-state i {
            font-size: 36px;
            margin-bottom: 12px;
            opacity: 0.5;
        }

        @media (max-width: 900px) {
            .charts-grid {
                grid-template-columns: 1fr;
            }
            .info-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

<div class="app">
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="logo">
            <i class="fas fa-volleyball"></i>
            <span>VôleiHub</span>
        </div>

        <nav>
            <a href="#perfil" class="nav-item active" data-tab="perfil">
                <i class="fas fa-user-circle"></i> Meu Perfil
            </a>
            <a href="#desempenho" class="nav-item" data-tab="desempenho">
                <i class="fas fa-chart-line"></i> Estatísticas
            </a>
            <a href="#propostas" class="nav-item" data-tab="propostas">
                <i class="fas fa-envelope-open-text"></i> Convites / Pitches
            </a>
            <a href="#chamados" class="nav-item" data-tab="chamados">
                <i class="fas fa-headset"></i> Suporte
            </a>
        </nav>

        <div class="sidebar-footer">
            <div class="user-info">
                <div class="avatar"><?php echo strtoupper(substr($usuario_nome, 0, 2)); ?></div>
                <div>
                    <strong><?php echo htmlspecialchars($usuario_nome); ?></strong>
                    <small>Atleta</small>
                </div>
            </div>
            <a href="index.php?logout=1" class="btn-logout" title="Sair">
                <i class="fas fa-sign-out-alt"></i>
            </a>
        </div>
    </aside>

    <!-- Conteúdo Principal -->
    <main class="main">
        <header class="topbar">
            <div>
                <h2 id="topbar-title">Meu Perfil</h2>
                <p style="color: var(--light-gray); font-size: 13px; margin-top: 2px;">Bem-vindo de volta, <?php echo htmlspecialchars(explode(' ', $usuario_nome)[0]); ?>!</p>
            </div>
            <span class="badge-status available">Perfil Ativo</span>
        </header>

        <!-- Cards de visão geral (sempre visíveis) -->
        <div class="stats-grid" style="grid-template-columns: repeat(4, 1fr);">
            <div class="stat-card">
                <div class="stat-icon blue"><i class="fas fa-volleyball"></i></div>
                <div>
                    <h3><?php echo $stats['jogos']; ?></h3>
                    <span>Jogos Disputados</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon green"><i class="fas fa-bolt"></i></div>
                <div>
                    <h3><?php echo $stats['pontos']; ?></h3>
                    <span>Pontos Marcados</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon teal"><i class="fas fa-hand-fist"></i></div>
                <div>
                    <h3><?php echo $stats['bloqueios']; ?></h3>
                    <span>Bloqueios</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon gray"><i class="fas fa-handshake"></i></div>
                <div>
                    <h3><?php echo $stats['interesses']; ?></h3>
                    <span>Interesses Recebidos</span>
                </div>
            </div>
        </div>

        <!-- ==================== ABA 1: PERFIL ==================== -->
        <section id="perfil" class="card tab-content active" style="margin-top: 24px;">
            <div class="profile-header">
                <div class="profile-avatar-lg">
                    <?php echo strtoupper(substr($usuario_nome, 0, 2)); ?>
                </div>
                <div class="profile-info">
                    <h3><?php echo htmlspecialchars($dados_usuario['nome'] ?? $usuario_nome); ?></h3>
                    <p><?php echo htmlspecialchars($dados_usuario['email'] ?? 'email@exemplo.com'); ?></p>
                    <div class="profile-tags">
                        <span class="tag"><?php echo htmlspecialchars($jogador['posicao'] ?? 'Ponteiro(a)'); ?></span>
                        <span class="tag"><?php echo htmlspecialchars($jogador['status'] ?? 'Livre'); ?></span>
                        <span class="tag">Brasil</span>
                    </div>
                </div>
            </div>

            <div class="info-grid">
                <div class="info-item">
                    <label>Nome Completo</label>
                    <strong><?php echo htmlspecialchars($dados_usuario['nome'] ?? $usuario_nome); ?></strong>
                </div>
                <div class="info-item">
                    <label>E-mail</label>
                    <strong><?php echo htmlspecialchars($dados_usuario['email'] ?? 'Não informado'); ?></strong>
                </div>
                <div class="info-item">
                    <label>Posição Principal</label>
                    <strong><?php echo htmlspecialchars($jogador['posicao'] ?? 'Ponteiro(a)'); ?></strong>
                </div>
                <div class="info-item">
                    <label>Altura</label>
                    <strong><?php echo htmlspecialchars($jogador['altura'] ?? '1,88 m'); ?></strong>
                </div>
                <div class="info-item">
                    <label>Idade</label>
                    <strong><?php echo htmlspecialchars($jogador['idade'] ?? '24 anos'); ?></strong>
                </div>
                <div class="info-item">
                    <label>Status do Contrato</label>
                    <strong><?php echo htmlspecialchars($jogador['status'] ?? 'Livre para Negociação'); ?></strong>
                </div>
                <div class="info-item">
                    <label>Clube Atual</label>
                    <strong><?php echo htmlspecialchars($jogador['clube'] ?? 'Sem clube'); ?></strong>
                </div>
                <div class="info-item">
                    <label>Valor de Mercado</label>
                    <strong><?php echo htmlspecialchars($jogador['valor'] ?? 'R$ 180.000'); ?></strong>
                </div>
            </div>
        </section>

        <!-- ==================== ABA 2: ESTATÍSTICAS ==================== -->
        <section id="desempenho" class="card tab-content" style="margin-top: 24px;">
            <div class="card-header">
                <h2>Desempenho em Quadra</h2>
            </div>

            <!-- Barras de progresso -->
            <div style="padding: 0 4px 20px;">
                <div class="metric-row">
                    <div class="metric-header">
                        <span>Ataques Efetivos</span>
                        <span>64%</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: 64%"></div>
                    </div>
                </div>
                <div class="metric-row">
                    <div class="metric-header">
                        <span>Aproveitamento de Passe</span>
                        <span>78%</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: 78%"></div>
                    </div>
                </div>
                <div class="metric-row">
                    <div class="metric-header">
                        <span>Eficiência no Bloqueio</span>
                        <span>41%</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: 41%"></div>
                    </div>
                </div>
                <div class="metric-row">
                    <div class="metric-header">
                        <span>Saque (Aces / Erros)</span>
                        <span>72%</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: 72%"></div>
                    </div>
                </div>
            </div>

            <!-- Gráficos -->
            <div class="charts-grid">
                <div class="chart-card">
                    <h4>Radar de Habilidades</h4>
                    <div class="chart-container">
                        <canvas id="radarChart"></canvas>
                    </div>
                </div>
                <div class="chart-card">
                    <h4>Pontos por Jogo (últimos 8)</h4>
                    <div class="chart-container">
                        <canvas id="lineChart"></canvas>
                    </div>
                </div>
            </div>
        </section>

        <!-- ==================== ABA 3: PROPOSTAS ==================== -->
        <section id="propostas" class="card tab-content" style="margin-top: 24px;">
            <div class="card-header">
                <h2>Propostas & Convites Recebidos</h2>
            </div>

            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Clube</th>
                            <th>Tipo</th>
                            <th>Valor</th>
                            <th>Data</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <strong>Sesi Bauru</strong><br>
                                <small style="color: var(--light-gray);">Superliga</small>
                            </td>
                            <td>Transferência</td>
                            <td>R$ 220 mil</td>
                            <td>05/08/2026</td>
                            <td><span class="proposta-status status-pendente">Pendente</span></td>
                            <td>
                                <div class="action-btns">
                                    <button class="btn-accept" onclick="responderProposta(this, 'aceito')">Aceitar</button>
                                    <button class="btn-reject" onclick="responderProposta(this, 'recusado')">Recusar</button>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <strong>Praia Clube</strong><br>
                                <small style="color: var(--light-gray);">Superliga</small>
                            </td>
                            <td>Empréstimo</td>
                            <td>R$ 45 mil</td>
                            <td>28/07/2026</td>
                            <td><span class="proposta-status status-pendente">Pendente</span></td>
                            <td>
                                <div class="action-btns">
                                    <button class="btn-accept" onclick="responderProposta(this, 'aceito')">Aceitar</button>
                                    <button class="btn-reject" onclick="responderProposta(this, 'recusado')">Recusar</button>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <strong>Minas Tênis</strong><br>
                                <small style="color: var(--light-gray);">Superliga</small>
                            </td>
                            <td>Transferência</td>
                            <td>R$ 195 mil</td>
                            <td>12/07/2026</td>
                            <td><span class="proposta-status status-recusado">Recusado</span></td>
                            <td>
                                <span style="color: var(--light-gray); font-size: 12px;">—</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- ==================== ABA 4: SUPORTE ==================== -->
        <section id="chamados" class="card tab-content" style="margin-top: 24px;">
            <div class="card-header">
                <h2>Central de Suporte</h2>
            </div>

            <div style="padding: 4px 0 10px;">
                <p style="color: var(--light-gray); font-size: 14px; margin-bottom: 20px;">
                    Precisa de ajuda com seu perfil, propostas ou dados cadastrais? Abra um chamado abaixo.
                </p>

                <form class="support-form" onsubmit="event.preventDefault(); abrirChamado();">
                    <div class="form-group">
                        <label>Assunto</label>
                        <select id="assunto" required>
                            <option value="">Selecione o assunto</option>
                            <option value="perfil">Atualização de Perfil</option>
                            <option value="proposta">Dúvida sobre Proposta</option>
                            <option value="dados">Correção de Dados</option>
                            <option value="outro">Outro</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Mensagem</label>
                        <textarea id="mensagem" rows="4" placeholder="Descreva seu problema ou solicitação..." required></textarea>
                    </div>
                    <button type="submit" class="btn-primary" style="width: fit-content;">
                        <i class="fas fa-paper-plane"></i> Enviar Chamado
                    </button>
                </form>

                <div class="ticket-list">
                    <h4 style="font-size: 14px; margin-bottom: 12px; color: var(--light-gray);">Seus chamados recentes</h4>
                    
                    <div class="ticket-item">
                        <div class="ticket-info">
                            <strong>#1042 — Atualização de altura</strong>
                            <small>Aberto em 18/08/2026 • Status: Resolvido</small>
                        </div>
                        <span class="proposta-status status-aceito">Resolvido</span>
                    </div>
                    <div class="ticket-item">
                        <div class="ticket-info">
                            <strong>#987 — Dúvida sobre proposta do Sesi</strong>
                            <small>Aberto em 03/08/2026 • Status: Em andamento</small>
                        </div>
                        <span class="proposta-status status-pendente">Em andamento</span>
                    </div>
                </div>
            </div>
        </section>
    </main>
</div>

<script>
    // ==================== NAVEGAÇÃO DE ABAS ====================
    document.addEventListener('DOMContentLoaded', () => {
        const navItems = document.querySelectorAll('.nav-item');
        const tabContents = document.querySelectorAll('.tab-content');
        const topbarTitle = document.getElementById('topbar-title');

        const titles = {
            perfil: 'Meu Perfil',
            desempenho: 'Estatísticas & Desempenho',
            propostas: 'Convites e Propostas',
            chamados: 'Central de Suporte'
        };

        navItems.forEach(item => {
            item.addEventListener('click', (e) => {
                e.preventDefault();
                const targetTab = item.getAttribute('data-tab');

                navItems.forEach(nav => nav.classList.remove('active'));
                tabContents.forEach(tab => tab.classList.remove('active'));

                item.classList.add('active');
                document.getElementById(targetTab).classList.add('active');
                topbarTitle.textContent = titles[targetTab] || 'Painel do Atleta';
            });
        });

        // Inicializa os gráficos
        initCharts();
    });

    // ==================== GRÁFICOS ====================
    function initCharts() {
        // Radar Chart
        const radarCtx = document.getElementById('radarChart');
        if (radarCtx) {
            new Chart(radarCtx, {
                type: 'radar',
                data: {
                    labels: ['Ataque', 'Bloqueio', 'Saque', 'Passe', 'Defesa', 'Levantamento'],
                    datasets: [{
                        label: 'Habilidades',
                        data: [78, 65, 72, 81, 68, 55],
                        backgroundColor: 'rgba(43, 92, 255, 0.2)',
                        borderColor: '#2B5CFF',
                        pointBackgroundColor: '#2B5CFF',
                        pointBorderColor: '#fff',
                        pointHoverBackgroundColor: '#fff',
                        pointHoverBorderColor: '#2B5CFF'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        r: {
                            beginAtZero: true,
                            max: 100,
                            ticks: { display: false },
                            grid: { color: 'rgba(255,255,255,0.08)' },
                            angleLines: { color: 'rgba(255,255,255,0.08)' },
                            pointLabels: { color: '#8B95A8', font: { size: 11 } }
                        }
                    },
                    plugins: {
                        legend: { display: false }
                    }
                }
            });
        }

        // Line Chart
        const lineCtx = document.getElementById('lineChart');
        if (lineCtx) {
            new Chart(lineCtx, {
                type: 'line',
                data: {
                    labels: ['J1', 'J2', 'J3', 'J4', 'J5', 'J6', 'J7', 'J8'],
                    datasets: [{
                        label: 'Pontos',
                        data: [12, 18, 9, 22, 15, 19, 14, 21],
                        borderColor: '#2B5CFF',
                        backgroundColor: 'rgba(43, 92, 255, 0.1)',
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: '#2B5CFF',
                        pointRadius: 4,
                        pointHoverRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: 'rgba(255,255,255,0.06)' },
                            ticks: { color: '#8B95A8' }
                        },
                        x: {
                            grid: { display: false },
                            ticks: { color: '#8B95A8' }
                        }
                    },
                    plugins: {
                        legend: { display: false }
                    }
                }
            });
        }
    }

    // ==================== AÇÕES DE PROPOSTA ====================
    function responderProposta(btn, status) {
        const row = btn.closest('tr');
        const statusCell = row.querySelector('.proposta-status');
        const actionsCell = row.querySelector('.action-btns').parentElement;

        if (status === 'aceito') {
            statusCell.className = 'proposta-status status-aceito';
            statusCell.textContent = 'Aceito';
            showToast('Proposta aceita com sucesso!');
        } else {
            statusCell.className = 'proposta-status status-recusado';
            statusCell.textContent = 'Recusado';
            showToast('Proposta recusada.');
        }

        actionsCell.innerHTML = '<span style="color: var(--light-gray); font-size: 12px;">Respondido</span>';
    }

    // ==================== SUPORTE ====================
    function abrirChamado() {
        const assunto = document.getElementById('assunto').value;
        const mensagem = document.getElementById('mensagem').value;

        if (!assunto || !mensagem) return;

        showToast('Chamado enviado com sucesso! Em breve entraremos em contato.');
        document.getElementById('assunto').value = '';
        document.getElementById('mensagem').value = '';
    }

    // ==================== TOAST SIMPLES ====================
    function showToast(msg) {
        // Cria toast dinamicamente se não existir
        let toast = document.getElementById('toast');
        if (!toast) {
            toast = document.createElement('div');
            toast.id = 'toast';
            toast.className = 'toast';
            toast.innerHTML = '<i class="fas fa-check-circle"></i><span id="toast-msg"></span>';
            document.body.appendChild(toast);
        }
        document.getElementById('toast-msg').textContent = msg;
        toast.classList.add('show');
        setTimeout(() => toast.classList.remove('show'), 3200);
    }
</script>

</body>
</html>