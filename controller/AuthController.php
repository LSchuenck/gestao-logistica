<?php
/**
 * Controller: AuthController
 * Centraliza toda a lógica de autenticação do sistema:
 *  - login()       → valida credenciais, cria sessão e redireciona
 *  - logout()      → destrói a sessão e redireciona para o login
 *  - trocarSenha() → valida, persiste a nova senha e redireciona
 * Cada método carrega sua respectiva view quando necessário.
 */
class AuthController {

    /** @var UsuarioDao|null DAO responsável pelas operações no banco (null apenas no logout) */
    private ?UsuarioDao $dao;

    /**
     * Construtor: recebe o DAO via injeção de dependência.
     * O DAO pode ser null quando apenas o método logout() for utilizado,
     * pois esse método não realiza nenhuma consulta ao banco de dados.
     *
     * @param UsuarioDao|null $dao Instância já inicializada com a conexão PDO, ou null
     */
    public function __construct(?UsuarioDao $dao = null) {
        $this->dao = $dao;
    }

    /* -------------------------------------------------------
     * MÉTODO: login
     * Processa o formulário de autenticação.
     * Fluxo:
     *  1. Se já existe sessão ativa, redireciona para o dashboard
     *  2. No POST, valida os campos e confere as credenciais
     *  3. Em caso de sucesso, cria a sessão e redireciona
     *  4. Em caso de erro, define $erro e exibe a view de login
     * ------------------------------------------------------- */
    public function login(): void {
        // Inicia a sessão se ainda não estiver ativa
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Usuário já autenticado: redireciona direto para o dashboard
        if (!empty($_SESSION['usuario'])) {
            header('Location: index.php');
            exit;
        }

        // Variável de feedback para a view
        $erro = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Obtém e sanitiza os campos enviados pelo formulário
            $email = trim($_POST['email'] ?? '');
            $senha =      $_POST['senha'] ?? '';

            // Validação básica: ambos os campos são obrigatórios
            if ($email === '' || $senha === '') {
                $erro = 'Preencha e-mail e senha.';
            } else {
                // Consulta o usuário no banco pelo e-mail informado
                $user = $this->dao->buscarPorEmail($email);

                if (!$user || !password_verify($senha, $user['senha'])) {
                    // Mensagem genérica intencional: não revela se o e-mail existe (segurança)
                    $erro = 'E-mail ou senha inválidos.';
                } elseif ($user['status'] === 'INATIVO') {
                    // Bloqueia o acesso de usuários desativados pelo administrador
                    $erro = 'Usuário inativo. Contate o administrador.';
                } else {
                    // Autenticação bem-sucedida: registra os dados na sessão
                    // Nunca armazenamos a senha ou campos desnecessários na sessão
                    $_SESSION['usuario'] = [
                        'id'           => $user['id_usuario'],
                        'nome'         => $user['nome'],
                        'perfil'       => $user['perfil'],
                        'trocar_senha' => (bool) $user['trocar_senha'],
                    ];

                    // Redireciona para troca de senha (primeiro acesso) ou direto para o dashboard
                    header('Location: ' . ($user['trocar_senha'] ? 'trocar_senha.php' : 'index.php'));
                    exit;
                }
            }
        }

        // Exibe a view do formulário de login com a variável $erro disponível
        include __DIR__ . '/../views/login.php';
    }

    /* -------------------------------------------------------
     * MÉTODO: logout
     * Encerra a sessão do usuário e redireciona para o login.
     * ------------------------------------------------------- */
    public function logout(): void {
        // Inicia a sessão caso ainda não esteja ativa, para garantir
        // que ela existe antes de ser destruída
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Destrói completamente a sessão, removendo todos os dados armazenados
        session_destroy();

        // Redireciona para a tela de login após o logout
        header('Location: login.php');
        exit;
    }

    /* -------------------------------------------------------
     * MÉTODO: trocarSenha
     * Permite que o usuário autenticado defina uma nova senha pessoal.
     * Fluxo:
     *  1. Garante que o usuário está logado (via requerLogin())
     *  2. No POST, valida os campos nova_senha e confirma_senha
     *  3. Persiste o hash bcrypt e desativa a flag trocar_senha
     *  4. Atualiza a sessão e redireciona para o dashboard
     * ------------------------------------------------------- */
    public function trocarSenha(): void {
        // Garante que apenas usuários autenticados acessem esta página
        requerLogin();

        // Variáveis de feedback para a view
        $erro    = '';
        $sucesso = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Obtém os campos do formulário com valor padrão vazio
            $nova     = $_POST['nova_senha']     ?? '';
            $confirma = $_POST['confirma_senha'] ?? '';

            // Validação: comprimento mínimo de segurança
            if (strlen($nova) < 6) {
                $erro = 'A senha deve ter no mínimo 6 caracteres.';
            // Validação: os dois campos devem ser idênticos
            } elseif ($nova !== $confirma) {
                $erro = 'As senhas não conferem.';
            } else {
                // Gera o hash bcrypt da nova senha (nunca armazena texto puro)
                $hash = password_hash($nova, PASSWORD_BCRYPT);

                // Obtém o ID do usuário logado a partir da sessão
                $id = (int) $_SESSION['usuario']['id'];

                // Persiste a nova senha e desativa a flag de troca obrigatória
                $this->dao->atualizarSenha($id, $hash);

                // Atualiza a sessão para evitar novo redirecionamento para esta página
                $_SESSION['usuario']['trocar_senha'] = false;

                // Redireciona para o dashboard após a troca bem-sucedida
                header('Location: index.php');
                exit;
            }
        }

        // Exibe a view do formulário de troca de senha com as variáveis de feedback
        include __DIR__ . '/../views/trocar_senha.php';
    }
}
