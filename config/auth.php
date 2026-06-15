<?php
/*
 * Arquivo: config/auth.php
 * Finalidade: Centraliza as funções de autenticação, controle de acesso por perfil
 * e renderização do menu lateral (sidebar) do sistema.
 * Deve ser incluído em todas as páginas que exigem usuário autenticado.
 */

// Inicia a sessão PHP somente se ela ainda não estiver ativa,
// evitando o erro "session already started"
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/*
 * Função: requerLogin()
 * Verifica se o usuário está autenticado (possui sessão ativa).
 * Se não estiver logado, redireciona para a página de login.
 * Se estiver logado mas precisar trocar a senha, redireciona para a
 * página de troca de senha (exceto se já estiver nessa página).
 */
function requerLogin() {
    // Verifica se a variável de sessão do usuário está vazia (não autenticado)
    if (empty($_SESSION['usuario'])) {
        header('Location: login.php');
        exit;
    }
    // Verifica se o usuário tem a flag "trocar_senha" ativa e não está na página de troca
    // Isso garante que usuários com senha temporária sejam forçados a definir uma nova senha
    if (!empty($_SESSION['usuario']['trocar_senha']) && basename($_SERVER['SCRIPT_FILENAME']) !== 'trocar_senha.php') {
        header('Location: trocar_senha.php');
        exit;
    }
}

/*
 * Função: requerPerfil(array $perfis)
 * Verifica se o usuário autenticado possui um dos perfis permitidos para acessar a página.
 * Primeiro garante que o usuário está logado (chama requerLogin()),
 * depois confere se o perfil dele está na lista de perfis autorizados.
 * Se não tiver permissão, redireciona para o dashboard com aviso de acesso negado.
 *
 * Exemplo de uso: requerPerfil(['ADMIN', 'GERENTE']);
 */
function requerPerfil(array $perfis) {
    // Garante autenticação antes de verificar o perfil
    requerLogin();
    // Verifica se o perfil do usuário está entre os perfis permitidos
    if (!in_array($_SESSION['usuario']['perfil'], $perfis)) {
        // Redireciona com parâmetro "acesso=negado" para exibir mensagem de erro no dashboard
        header('Location: index.php?acesso=negado');
        exit;
    }
}

/*
 * Função: renderNavbar()
 * Gera e exibe o menu lateral (sidebar) do sistema com base no perfil do usuário logado.
 * Inclui: logotipo, itens de navegação agrupados por seção, informações do usuário e botão de logout.
 * O menu é responsivo: pode ser expandido/recolhido pelo usuário (preferência salva no localStorage).
 */
function renderNavbar() {
    // Obtém os dados do usuário da sessão com valores padrão vazios para evitar erros
    $u      = $_SESSION['usuario'] ?? [];
    $nome   = htmlspecialchars($u['nome'] ?? '');   // Sanitiza o nome para evitar XSS
    $perfil = $u['perfil'] ?? '';                    // Perfil: ADMIN, GERENTE ou OPERADOR
    $pagina = basename($_SERVER['SCRIPT_FILENAME']); // Nome do arquivo atual para marcar item ativo

    // Conta alertas ativos para exibir badge no item do menu
    $totalAlertas = 0;
    global $pdo;
    if ($pdo) {
        try {
            $totalAlertas = (int) $pdo->query("
                SELECT
                  (SELECT COUNT(*) FROM entrega
                   WHERE data_prevista < CURDATE() AND status NOT IN ('ENTREGUE'))
                + (SELECT COUNT(*) FROM viagem
                   WHERE data_chegada_prevista < NOW() AND status NOT IN ('CONCLUIDA','CANCELADA'))
                + (SELECT COUNT(*) FROM alerta WHERE tipo_alerta IN ('DESVIO_ROTA','PARADA_NAO_PROGRAMADA'))
                + (SELECT COUNT(*) FROM estoque WHERE quantidade < 10)
            ")->fetchColumn();
        } catch (Exception $_) {}
    }

    // Define a classe CSS do badge de perfil conforme o nível de acesso do usuário
    if ($perfil === 'ADMIN')         $badgeClass = 'bg-danger';       // Vermelho para administrador
    elseif ($perfil === 'GERENTE')   $badgeClass = 'bg-warning text-dark'; // Amarelo para gerente
    else                             $badgeClass = 'bg-secondary';    // Cinza para operador

    /*
     * Define os itens do menu lateral.
     * Itens com chave 'divider' são separadores/cabeçalhos de seção.
     * Itens com chave 'href' são links de navegação com ícone Bootstrap Icons.
     */
    $itens = [
        ['href' => 'index.php',           'icon' => 'bi-speedometer2',      'label' => 'Dashboard'],
        ['divider' => 'Cadastros'],
        ['href' => 'transportadoras.php', 'icon' => 'bi-building',          'label' => 'Transportadoras'],
        ['href' => 'motoristas.php',      'icon' => 'bi-person-badge',      'label' => 'Motoristas'],
        ['href' => 'veiculos.php',        'icon' => 'bi-truck-front',       'label' => 'Veículos'],
        ['href' => 'clientes.php',        'icon' => 'bi-people',            'label' => 'Clientes'],
        ['href' => 'armazens.php',        'icon' => 'bi-houses',            'label' => 'Armazéns'],
        ['href' => 'produtos.php',        'icon' => 'bi-box',               'label' => 'Produtos'],
        ['divider' => 'Operacional'],
        ['href' => 'entregas.php',        'icon' => 'bi-box-arrow-right',   'label' => 'Entregas'],
        ['href' => 'operacoes.php',       'icon' => 'bi-map',               'label' => 'Operações'],
        ['divider' => 'Análise'],
        ['href' => 'estoque.php',         'icon' => 'bi-box-seam',          'label' => 'Estoque'],
        ['href' => 'alertas.php',         'icon' => 'bi-exclamation-triangle', 'label' => 'Alertas', 'badge' => $totalAlertas],
        ['href' => 'frete.php',           'icon' => 'bi-receipt-cutoff',    'label' => 'Fretes / NF'],
        ['href' => 'indicadores.php',     'icon' => 'bi-bar-chart',         'label' => 'Indicadores'],
    ];

    // Adiciona o item de gerenciamento de usuários apenas para administradores,
    // pois é uma funcionalidade restrita ao perfil ADMIN
    if ($perfil === 'ADMIN') {
        $itens[] = ['divider' => 'Sistema'];
        $itens[] = ['href' => 'usuarios.php', 'icon' => 'bi-people-fill', 'label' => 'Usuários'];
    }

    // Monta o HTML de cada item do menu iterando sobre o array de itens
    $navItems = '';
    foreach ($itens as $item) {
        if (isset($item['divider'])) {
            // Renderiza separador visual e título de seção do menu
            $navItems .= '
    <div class="sidebar-divider"></div>
    <div class="sidebar-section">' . htmlspecialchars($item['divider']) . '</div>';
        } else {
            // Marca o item como "active" se a página atual corresponde ao link do item
            $active = ($pagina === $item['href']) ? ' active' : '';
            $n      = (int)($item['badge'] ?? 0);

            // Badge visível apenas para itens que definem 'badge' E cujo total supera a última visita
            $seenCount = (int)($_SESSION['alertas_seen_count'] ?? -1);
            $badgeHtml = '';
            if (isset($item['badge']) && $n > $seenCount) {
                $badgeLabel = $n > 99 ? '99+' : $n;
                $badgeHtml  = '<span class="sb-badge" aria-label="' . $n . ' alertas">' . $badgeLabel . '</span>';
            }

            $labelExtra = (isset($item['badge']) && $n > $seenCount)
                ? ' <span class="sb-badge-inline">' . ($n > 99 ? '99+' : $n) . '</span>'
                : '';

            $title = htmlspecialchars($item['label']) . ($n > 0 ? " — $n alerta(s)" : '');

            $navItems .= '
    <a href="' . $item['href'] . '" class="sidebar-item' . $active . '" title="' . $title . '">
      <i class="bi ' . $item['icon'] . '"></i>' . $badgeHtml . '
      <span class="sidebar-label">' . htmlspecialchars($item['label']) . $labelExtra . '</span>
    </a>';
        }
    }

    // Renderiza os estilos CSS do sidebar e o HTML completo do menu lateral
    // Os estilos usam variáveis CSS (--sb-w, --sb-w-open) para controlar a largura
    // recolhida e expandida, com transições suaves via CSS transition
    echo '<style>
:root{--sb-w:65px;--sb-w-open:235px}
body{padding-left:var(--sb-w);padding-top:1.25rem;transition:padding-left .25s ease}
body.sidebar-open{padding-left:var(--sb-w-open)}
.sidebar{position:fixed;left:0;top:0;height:100vh;width:var(--sb-w);background:#1a1d20;z-index:1040;display:flex;flex-direction:column;overflow:hidden;transition:width .25s ease;border-right:1px solid rgba(255,255,255,.07);box-shadow:2px 0 8px rgba(0,0,0,.25)}
.sidebar.expanded{width:var(--sb-w-open)}
.sidebar-header{display:flex;align-items:center;height:56px;border-bottom:1px solid rgba(255,255,255,.07);flex-shrink:0}
.sidebar-toggle{width:var(--sb-w);height:56px;background:none;border:none;color:rgba(255,255,255,.65);font-size:1.4rem;cursor:pointer;flex-shrink:0;transition:color .15s;display:flex;align-items:center;justify-content:center}
.sidebar-toggle:hover{color:#fff}
.sidebar-brand{display:flex;align-items:center;gap:8px;color:#fff;font-weight:700;font-size:.75rem;letter-spacing:.5px;white-space:nowrap;padding-right:12px;opacity:0;transition:opacity .15s .05s}
.sidebar.expanded .sidebar-brand{opacity:1}
.sidebar-nav{flex:1;overflow-y:auto;overflow-x:hidden;padding:6px 0}
.sidebar-nav::-webkit-scrollbar{width:3px}
.sidebar-nav::-webkit-scrollbar-thumb{background:rgba(255,255,255,.15);border-radius:3px}
.sidebar-item{display:flex;align-items:center;height:42px;color:rgba(255,255,255,.6);text-decoration:none;white-space:nowrap;transition:background .15s,color .15s;border-right:3px solid transparent}
.sidebar-item:hover{background:rgba(255,255,255,.08);color:#fff}
.sidebar-item.active{background:rgba(255,255,255,.1);color:#fff;border-right-color:#ffc107}
.sidebar-item i{width:var(--sb-w);text-align:center;font-size:1.1rem;flex-shrink:0}
.sidebar-label{font-size:.82rem;opacity:0;transition:opacity .15s .05s;overflow:hidden}
.sidebar.expanded .sidebar-label{opacity:1}
.sidebar-divider{height:1px;background:rgba(255,255,255,.07);margin:6px 10px}
.sidebar-section{font-size:.6rem;text-transform:uppercase;letter-spacing:1.2px;color:rgba(255,255,255,.3);padding:6px 0 2px 20px;white-space:nowrap;max-height:0;overflow:hidden;opacity:0;transition:max-height .2s,opacity .15s .05s}
.sidebar.expanded .sidebar-section{max-height:24px;opacity:1}
.sidebar-footer{border-top:1px solid rgba(255,255,255,.07);padding:6px 0;flex-shrink:0}
.sidebar-user{cursor:default;height:auto;min-height:42px;padding:6px 0}
.sidebar-user:hover{background:none!important}
.sidebar-user-info{display:flex;flex-direction:column;line-height:1.2;overflow:hidden}
.sidebar-user-name{font-size:.78rem;color:rgba(255,255,255,.75);white-space:nowrap}
.sidebar-logout{color:rgba(255,100,100,.75)!important}
.sidebar-logout:hover{background:rgba(255,50,50,.1)!important;color:#ff7070!important}
.sidebar-item{position:relative}
.sb-badge{position:absolute;top:6px;left:calc(var(--sb-w)/2 + 4px);background:#dc3545;color:#fff;border-radius:10px;font-size:.55rem;font-weight:700;min-width:15px;height:15px;display:flex;align-items:center;justify-content:center;padding:0 3px;line-height:1;border:1.5px solid #1a1d20;animation:sb-pulse 2.5s ease-in-out infinite;pointer-events:none}
.sb-badge-inline{background:#dc3545;color:#fff;border-radius:10px;font-size:.6rem;font-weight:700;padding:1px 5px;vertical-align:middle;margin-left:4px}
@keyframes sb-pulse{0%,100%{transform:scale(1);opacity:1}50%{transform:scale(1.18);opacity:.85}}
</style>
<aside class="sidebar" id="sidebar">
  <div class="sidebar-header">
    <button class="sidebar-toggle" id="sidebarToggle" title="Expandir/recolher menu">
      <i class="bi bi-list"></i>
    </button>
    <span class="sidebar-brand">
      <i class="bi bi-truck text-warning"></i> GESTÃO LOG.
    </span>
  </div>

  <nav class="sidebar-nav">' . $navItems . '
  </nav>

  <div class="sidebar-footer">
    <div class="sidebar-item sidebar-user" title="' . $nome . '">
      <i class="bi bi-person-circle"></i>
      <span class="sidebar-label">
        <span class="sidebar-user-info">
          <span class="sidebar-user-name">' . $nome . '</span>
          <span class="badge ' . $badgeClass . ' mt-1" style="font-size:.6rem;width:fit-content">' . $perfil . '</span>
        </span>
      </span>
    </div>
    <a href="logout.php" class="sidebar-item sidebar-logout" title="Sair">
      <i class="bi bi-box-arrow-right"></i>
      <span class="sidebar-label">Sair</span>
    </a>
  </div>
</aside>
<script>
(function(){
  // Recupera a preferência de estado do sidebar salva no localStorage do navegador
  var s=document.getElementById("sidebar");
  if(localStorage.getItem("sidebarExpanded")==="1"){
    s.classList.add("expanded");
    document.body.classList.add("sidebar-open");
  }
  // Alterna o estado expandido/recolhido do sidebar ao clicar no botão de toggle
  // e persiste a preferência no localStorage para manter entre páginas
  document.getElementById("sidebarToggle").onclick=function(){
    s.classList.toggle("expanded");
    document.body.classList.toggle("sidebar-open");
    localStorage.setItem("sidebarExpanded",s.classList.contains("expanded")?"1":"0");
  };
})();
</script>';
}
