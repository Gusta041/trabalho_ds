<?php
session_start();

// Proteção simples
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

$mensagem = '';
$tipo_msg = ''; // success | error

// ========== SINCRONIZAR VIA GOOGLE SHEETS (exemplo) ==========
if (isset($_POST['sincronizar_sheets'])) {
    // Aqui você coloca a lógica real de buscar do Google Sheets
    // Por enquanto só simula sucesso
    $mensagem = "Planilha sincronizada com sucesso!";
    $tipo_msg = "success";
}

// ========== UPLOAD CSV LOCAL ==========
if (isset($_POST['enviar_csv']) && isset($_FILES['arquivo_csv'])) {
    $arquivo = $_FILES['arquivo_csv'];

    if ($arquivo['error'] === UPLOAD_ERR_OK && pathinfo($arquivo['name'], PATHINFO_EXTENSION) === 'csv') {
        $handle = fopen($arquivo['tmp_name'], 'r');
        $header = fgetcsv($handle, 0, ','); // ou ';' se for o caso

        $importados = 0;
        while (($dados = fgetcsv($handle, 0, ',')) !== false) {
            // Ajuste os índices conforme as colunas da sua planilha
            // Exemplo: Nome, Posição, data_nascimento, altura...
            $nome     = $dados[1] ?? null;
            $posicao  = $dados[2] ?? null;
            $altura   = $dados[5] ?? null;

            if ($nome) {
                $stmt = $pdo->prepare("INSERT INTO jogadores (nome, posicao, altura, usuario_id) VALUES (?, ?, ?, ?)");
                $stmt->execute([$nome, $posicao, $altura, $_SESSION['usuario_id']]);
                $importados++;
            }
        }
        fclose($handle);

        $mensagem = "$importados jogadores importados com sucesso!";
        $tipo_msg = "success";
    } else {
        $mensagem = "Erro ao enviar o arquivo. Envie um CSV válido.";
        $tipo_msg = "error";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>VôleiHub | Sincronizar Jogadores</title>
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    /* ===== Página de Importação ===== */
    body {
      background: linear-gradient(135deg, #0b0e14 0%, #0A4D68 50%, #12151F 100%);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 40px 20px;
      position: relative;
      overflow: hidden;
    }

    /* Efeito de ondas no fundo (igual login) */
    body::before {
      content: '';
      position: absolute;
      inset: 0;
      background: 
        radial-gradient(ellipse at 20% 50%, rgba(43, 92, 255, 0.12) 0%, transparent 50%),
        radial-gradient(ellipse at 80% 20%, rgba(10, 77, 104, 0.18) 0%, transparent 45%);
      pointer-events: none;
    }

    .import-wrapper {
      width: 100%;
      max-width: 520px;
      position: relative;
      z-index: 2;
    }

    .import-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 28px;
    }

    .import-header h1 {
      font-size: 26px;
      font-weight: 700;
      margin-bottom: 4px;
    }

    .import-header p {
      color: var(--light-gray);
      font-size: 13px;
    }

    .btn-voltar {
      background: rgba(255,255,255,0.06);
      border: 1px solid var(--border);
      color: var(--white);
      padding: 9px 16px;
      border-radius: 10px;
      text-decoration: none;
      font-size: 13px;
      font-weight: 500;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      transition: all 0.2s;
    }

    .btn-voltar:hover {
      background: rgba(255,255,255,0.1);
      border-color: var(--royal);
    }

    .import-card {
      background: rgba(26, 31, 46, 0.85);
      backdrop-filter: blur(16px);
      border: 1px solid var(--border);
      border-radius: 20px;
      padding: 36px 32px;
      box-shadow: 0 25px 60px rgba(0,0,0,0.45);
    }

    .sheets-box {
      text-align: center;
      padding: 28px 20px;
      background: rgba(11, 14, 20, 0.5);
      border-radius: 16px;
      border: 1px solid var(--border);
      margin-bottom: 28px;
    }

    .sheets-icon {
      width: 64px;
      height: 64px;
      background: linear-gradient(135deg, #34A853, #0F9D58);
      border-radius: 16px;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 18px;
      font-size: 28px;
      color: white;
      box-shadow: 0 8px 24px rgba(52, 168, 83, 0.3);
    }

    .sheets-box h2 {
      font-size: 18px;
      font-weight: 600;
      margin-bottom: 8px;
      color: #4ADE80;
    }

    .sheets-box p {
      color: var(--light-gray);
      font-size: 13px;
      line-height: 1.5;
      margin-bottom: 22px;
    }

    .btn-sync {
      background: linear-gradient(135deg, #22C55E, #16A34A);
      color: white;
      border: none;
      padding: 13px 32px;
      border-radius: 12px;
      font-weight: 600;
      font-size: 14px;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 10px;
      transition: all 0.25s;
      box-shadow: 0 6px 20px rgba(34, 197, 94, 0.35);
    }

    .btn-sync:hover {
      transform: translateY(-2px);
      box-shadow: 0 10px 28px rgba(34, 197, 94, 0.45);
    }

    .btn-sync:active {
      transform: translateY(0);
    }

    .divider {
      display: flex;
      align-items: center;
      gap: 14px;
      margin: 28px 0;
      color: var(--light-gray);
      font-size: 12px;
      font-weight: 500;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .divider::before,
    .divider::after {
      content: '';
      flex: 1;
      height: 1px;
      background: var(--border);
    }

    .csv-drop {
      border: 2px dashed var(--border);
      border-radius: 16px;
      padding: 36px 24px;
      text-align: center;
      transition: all 0.25s;
      cursor: pointer;
      position: relative;
    }

    .csv-drop:hover,
    .csv-drop.dragover {
      border-color: var(--royal);
      background: rgba(43, 92, 255, 0.06);
    }

    .csv-drop i {
      font-size: 36px;
      color: var(--royal);
      margin-bottom: 12px;
    }

    .csv-drop h3 {
      font-size: 15px;
      font-weight: 600;
      margin-bottom: 4px;
    }

    .csv-drop p {
      color: var(--light-gray);
      font-size: 12.5px;
    }

    .csv-drop input[type="file"] {
      position: absolute;
      inset: 0;
      opacity: 0;
      cursor: pointer;
    }

    .alert {
      padding: 12px 16px;
      border-radius: 10px;
      font-size: 13px;
      margin-bottom: 20px;
      text-align: center;
    }

    .alert.success {
      background: rgba(34, 197, 94, 0.15);
      border: 1px solid rgba(34, 197, 94, 0.3);
      color: #4ADE80;
    }

    .alert.error {
      background: rgba(239, 68, 68, 0.15);
      border: 1px solid rgba(239, 68, 68, 0.3);
      color: #fca5a5;
    }
  </style>
</head>
<body>

  <div class="import-wrapper">

    <div class="import-header">
      <div>
        <h1>Sincronizar Jogadores</h1>
        <p>Integração em tempo real com o Google Sheets</p>
      </div>
      <a href="index.php" class="btn-voltar">
        <i class="fas fa-arrow-left"></i> Voltar ao Painel
      </a>
    </div>

    <div class="import-card">

      <?php if ($mensagem): ?>
        <div class="alert <?= $tipo_msg ?>">
          <?= htmlspecialchars($mensagem) ?>
        </div>
      <?php endif; ?>

      <!-- Google Sheets -->
      <div class="sheets-box">
        <div class="sheets-icon">
          <i class="fab fa-google-drive"></i>
        </div>
        <h2>Planilha Conectada!</h2>
        <p>
          Faça suas edições no Google Sheets.<br>
          Para atualizar os dados no sistema, clique no botão abaixo.
        </p>

        <form method="POST">
          <button type="submit" name="sincronizar_sheets" class="btn-sync">
            <i class="fas fa-sync-alt"></i> Sincronizar Agora
          </button>
        </form>
      </div>

      <div class="divider">ou enviar CSV local</div>

      <!-- Upload CSV -->
      <form method="POST" enctype="multipart/form-data" id="form-csv">
        <div class="csv-drop" id="drop-zone">
          <i class="fas fa-file-csv"></i>
          <h3>Drag & Drop</h3>
          <p>Selecione um arquivo .CSV do computador</p>
          <input type="file" name="arquivo_csv" accept=".csv" id="input-csv">
        </div>
        <input type="hidden" name="enviar_csv" value="1">
      </form>

    </div>
  </div>

  <script>
    const dropZone = document.getElementById('drop-zone');
    const inputCsv = document.getElementById('input-csv');
    const formCsv  = document.getElementById('form-csv');

    // Drag & Drop visual
    ['dragenter', 'dragover'].forEach(evt => {
      dropZone.addEventListener(evt, e => {
        e.preventDefault();
        dropZone.classList.add('dragover');
      });
    });

    ['dragleave', 'drop'].forEach(evt => {
      dropZone.addEventListener(evt, e => {
        e.preventDefault();
        dropZone.classList.remove('dragover');
      });
    });

    dropZone.addEventListener('drop', e => {
      const files = e.dataTransfer.files;
      if (files.length) {
        inputCsv.files = files;
        formCsv.submit();
      }
    });

    // Auto-submit ao selecionar arquivo
    inputCsv.addEventListener('change', () => {
      if (inputCsv.files.length) {
        formCsv.submit();
      }
    });
  </script>
</body>
</html>