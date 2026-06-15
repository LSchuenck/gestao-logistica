<?php
/**
 * Controller: UsuarioController
 * Gerencia o módulo de usuários do sistema.
 * Responsável por:
 *  - Processar requisições GET (excluir, toggle, listar) e POST (cadastrar, editar)
 *  - Validar entradas do formulário
 *  - Orquestrar chamadas ao UsuarioDao
 *  - Definir as variáveis $erro, $sucesso e $usuarios para a view
 *  - Incluir a view ao final do fluxo
 * Acesso exclusivo ao perfil ADMIN (verificado no entry point).
 */
class UsuarioController {

    /** @var UsuarioDao DAO responsável pelas operações no banco */
    private UsuarioDao $dao;

    /**
     * Construtor: recebe o DAO via injeção de dependência.
     *
     * @param UsuarioDao $dao Instância já inicializada com a conexão PDO
     */
    public function __construct(UsuarioDao $dao) {
        $this->dao = $dao;
    }

    /* -------------------------------------------------------
     * FUNÇÃO AUXILIAR: gerarSenhaAleatoria
     * Gera uma senha aleatória com letras maiúsculas, minúsculas
     * e dígitos usando random_int (criptograficamente seguro).
     *
     * @param int $tamanho Comprimento da senha (padrão 10)
     * @return string Senha aleatória gerada
     * ------------------------------------------------------- */
    private function gerarSenhaAleatoria(int $tamanho = 10): string {
        // Conjunto de caracteres permitidos na senha temporária
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $senha = '';
        for ($i = 0; $i < $tamanho; $i++) {
            // Escolhe um caractere aleatório de forma segura
            $senha .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $senha;
    }

    /* -------------------------------------------------------
     * MÉTODO PRINCIPAL: handle
     * Ponto de entrada do controller — despacha a ação correta
     * com base no método HTTP e nos parâmetros da requisição.
     * Ao final, define as variáveis de template e inclui a view.
     * ------------------------------------------------------- */
    public function handle(): void {
        // Variáveis de feedback inicializadas como vazio
        $erro    = '';
        $sucesso = '';

        /* -------------------------------------------------------
         * EXCLUSÃO DE USUÁRIO (GET ?excluir=id)
         * Proteções:
         *  1. Conta master (id=1) nunca pode ser excluída
         *  2. Admin não pode excluir a própria conta
         * ------------------------------------------------------- */
        if (isset($_GET['excluir'])) {
            $id = (int) $_GET['excluir'];

            if ($id === 1) {
                $erro = 'Essa conta não pode ser excluída!';
            } elseif ($id === (int) $_SESSION['usuario']['id']) {
                $erro = 'Você não pode excluir sua própria conta.';
            } else {
                $this->dao->excluir($id);
                // Redireciona para atualizar a listagem (PRG pattern)
                header('Location: usuarios.php');
                exit;
            }
        }

        /* -------------------------------------------------------
         * ATIVAR / DESATIVAR USUÁRIO (GET ?toggle=id)
         * Alterna o status entre ATIVO e INATIVO.
         * Mesmas proteções da exclusão.
         * ------------------------------------------------------- */
        if (isset($_GET['toggle'])) {
            $id = (int) $_GET['toggle'];

            if ($id === 1) {
                $erro = 'Essa conta não pode ser desativada!';
            } elseif ($id === (int) $_SESSION['usuario']['id']) {
                $erro = 'Você não pode desativar sua própria conta.';
            } else {
                $this->dao->alternarStatus($id);
                // Redireciona para atualizar a listagem (PRG pattern)
                header('Location: usuarios.php');
                exit;
            }
        }

        /* -------------------------------------------------------
         * CADASTRO E EDIÇÃO (POST do formulário modal)
         * id_usuario > 0 → edição; id_usuario = 0 → cadastro
         * ------------------------------------------------------- */
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Coleta e sanitiza os dados enviados pelo formulário
            $id     = (int)   ($_POST['id_usuario'] ?? 0);
            $nome   = trim(         $_POST['nome']   ?? '');
            $email  = trim(         $_POST['email']  ?? '');
            $perfil =               $_POST['perfil'] ?? '';
            $senha  =               $_POST['senha']  ?? '';
            $status =               $_POST['status'] ?? 'ATIVO';

            // Validação dos campos obrigatórios
            if (
                $nome   === '' ||
                $email  === '' ||
                !filter_var($email, FILTER_VALIDATE_EMAIL) ||
                !in_array($perfil, ['ADMIN', 'GERENTE', 'OPERADOR'])
            ) {
                $erro = 'Preencha todos os campos obrigatórios.';
            } else {
                try {
                    if ($id > 0) {
                        /* --- EDIÇÃO DE USUÁRIO EXISTENTE --- */
                        if ($senha !== '') {
                            // Nova senha informada: gera hash bcrypt e atualiza junto com os demais campos
                            $hash = password_hash($senha, PASSWORD_BCRYPT);
                            $this->dao->atualizarComSenha($id, $nome, $email, $perfil, $status, $hash);
                        } else {
                            // Sem nova senha: mantém o hash atual inalterado
                            $this->dao->atualizar($id, $nome, $email, $perfil, $status);
                        }
                        $sucesso = 'Usuário atualizado com sucesso.';
                    } else {
                        /* --- CADASTRO DE NOVO USUÁRIO --- */

                        // Gera senha temporária aleatória de 10 caracteres
                        $senhaTemp = $this->gerarSenhaAleatoria(10);

                        // Armazena apenas o hash bcrypt no banco (nunca texto puro)
                        $hash = password_hash($senhaTemp, PASSWORD_BCRYPT);

                        // Insere o usuário com trocar_senha=1 para forçar troca no primeiro login
                        $this->dao->inserir($nome, $email, $hash, $perfil, $status);

                        // Dispara o e-mail de boas-vindas com a senha temporária via Power Automate
                        $emailEnviado = $this->dao->enviarEmailBoasVindas($email, $nome, $senhaTemp);

                        // Mensagem de sucesso; se o e-mail falhou, exibe a senha na tela como fallback
                        $sucesso = 'Usuário cadastrado.'
                                 . ($emailEnviado
                                     ? ' Senha enviada por e-mail.'
                                     : ' Aviso: falha ao enviar o e-mail — senha temporária: ' . $senhaTemp);
                    }

                    // Após salvar com sucesso, redireciona (PRG pattern) passando a mensagem pela URL
                    header('Location: usuarios.php?ok=' . urlencode($sucesso));
                    exit;

                } catch (Exception $e) {
                    // Trata e-mail duplicado (constraint UNIQUE) e outros erros do banco
                    $erro = 'E-mail já cadastrado ou erro ao salvar.';
                }
            }
        }

        /* -------------------------------------------------------
         * MENSAGEM DE SUCESSO PÓS-REDIRECT (GET ?ok=...)
         * Recupera a mensagem de sucesso passada na URL após o PRG.
         * htmlspecialchars previne XSS na exibição.
         * ------------------------------------------------------- */
        if (isset($_GET['ok'])) {
            $sucesso = htmlspecialchars($_GET['ok']);
        }

        /* -------------------------------------------------------
         * LISTAGEM DE USUÁRIOS
         * Busca todos os usuários para popular a tabela na view.
         * ------------------------------------------------------- */
        $usuarios = $this->dao->listarTodos();

        // Carrega a view; $erro, $sucesso e $usuarios ficam disponíveis no escopo dela
        include __DIR__ . '/../views/usuarios.php';
    }
}
