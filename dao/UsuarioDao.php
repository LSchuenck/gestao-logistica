<?php
/**
 * DAO: UsuarioDao
 * Encapsula todas as operações SQL relacionadas à tabela 'usuario'.
 * Recebe uma instância PDO pelo construtor (injeção de dependência).
 */
class UsuarioDao {

    /** Conexão PDO com o banco de dados */
    private PDO $pdo;

    /**
     * Construtor: recebe e armazena a conexão PDO.
     *
     * @param PDO $pdo Instância PDO já configurada
     */
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /* -------------------------------------------------------
     * CONSULTAS DE LEITURA
     * ------------------------------------------------------- */

    /**
     * Retorna todos os usuários ordenados por nome.
     * A coluna 'senha' é omitida intencionalmente por segurança.
     *
     * @return array Lista de usuários como arrays associativos
     */
    public function listarTodos(): array {
        $stmt = $this->pdo->query(
            "SELECT id_usuario, nome, email, perfil, status, data_cadastro
               FROM usuario
           ORDER BY nome"
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Busca um único usuário pelo endereço de e-mail.
     * Usado no processo de autenticação (login).
     * Inclui a coluna 'senha' pois é necessária para verificar o hash.
     *
     * @param string $email E-mail a pesquisar
     * @return array|false Array associativo do usuário ou false se não encontrado
     */
    public function buscarPorEmail(string $email): array|false {
        $stmt = $this->pdo->prepare(
            "SELECT id_usuario, nome, senha, perfil, status, trocar_senha
               FROM usuario
              WHERE email = ?"
        );
        $stmt->execute([$email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Consulta o status atual de um usuário pelo ID.
     * Usado internamente pelo toggle de ativar/desativar.
     *
     * @param int $id ID do usuário
     * @return string|false Status ('ATIVO' ou 'INATIVO') ou false se não encontrado
     */
    public function buscarStatusPorId(int $id): string|false {
        $stmt = $this->pdo->prepare(
            "SELECT status FROM usuario WHERE id_usuario = ?"
        );
        $stmt->execute([$id]);
        return $stmt->fetchColumn();
    }

    /* -------------------------------------------------------
     * OPERAÇÕES DE ESCRITA
     * ------------------------------------------------------- */

    /**
     * Insere um novo usuário no banco de dados.
     * O campo trocar_senha é definido como 1 para forçar a troca
     * de senha no primeiro acesso.
     *
     * @param string $nome   Nome completo do usuário
     * @param string $email  Endereço de e-mail (deve ser único)
     * @param string $hash   Hash bcrypt da senha temporária
     * @param string $perfil Perfil de acesso: ADMIN | GERENTE | OPERADOR
     * @param string $status Status inicial: ATIVO | INATIVO
     * @return void
     * @throws PDOException Em caso de e-mail duplicado (UNIQUE) ou outro erro SQL
     */
    public function inserir(
        string $nome,
        string $email,
        string $hash,
        string $perfil,
        string $status
    ): void {
        $this->pdo->prepare(
            "INSERT INTO usuario (nome, email, senha, perfil, status, trocar_senha)
             VALUES (?, ?, ?, ?, ?, 1)"
        )->execute([$nome, $email, $hash, $perfil, $status]);
    }

    /**
     * Atualiza os dados de um usuário existente sem alterar a senha.
     *
     * @param int    $id     ID do usuário a atualizar
     * @param string $nome   Novo nome
     * @param string $email  Novo e-mail
     * @param string $perfil Novo perfil
     * @param string $status Novo status
     * @return void
     */
    public function atualizar(
        int    $id,
        string $nome,
        string $email,
        string $perfil,
        string $status
    ): void {
        $this->pdo->prepare(
            "UPDATE usuario
                SET nome = ?, email = ?, perfil = ?, status = ?
              WHERE id_usuario = ?"
        )->execute([$nome, $email, $perfil, $status, $id]);
    }

    /**
     * Atualiza os dados de um usuário existente incluindo uma nova senha.
     *
     * @param int    $id     ID do usuário a atualizar
     * @param string $nome   Novo nome
     * @param string $email  Novo e-mail
     * @param string $perfil Novo perfil
     * @param string $status Novo status
     * @param string $hash   Hash bcrypt da nova senha
     * @return void
     */
    public function atualizarComSenha(
        int    $id,
        string $nome,
        string $email,
        string $perfil,
        string $status,
        string $hash
    ): void {
        $this->pdo->prepare(
            "UPDATE usuario
                SET nome = ?, email = ?, perfil = ?, status = ?, senha = ?
              WHERE id_usuario = ?"
        )->execute([$nome, $email, $perfil, $status, $hash, $id]);
    }

    /**
     * Atualiza apenas a senha de um usuário e desativa a flag de troca obrigatória.
     * Usado pela tela de troca de senha (primeiro acesso).
     *
     * @param int    $id   ID do usuário
     * @param string $hash Hash bcrypt da nova senha
     * @return void
     */
    public function atualizarSenha(int $id, string $hash): void {
        $this->pdo->prepare(
            "UPDATE usuario
                SET senha = ?, trocar_senha = 0
              WHERE id_usuario = ?"
        )->execute([$hash, $id]);
    }

    /**
     * Alterna o status do usuário entre ATIVO e INATIVO.
     * Consulta o status atual e aplica o inverso.
     *
     * @param int $id ID do usuário a ter o status alternado
     * @return void
     */
    public function alternarStatus(int $id): void {
        // Obtém o status atual para calcular o novo valor
        $atual = $this->buscarStatusPorId($id);
        $novo  = ($atual === 'ATIVO') ? 'INATIVO' : 'ATIVO';

        $this->pdo->prepare(
            "UPDATE usuario SET status = ? WHERE id_usuario = ?"
        )->execute([$novo, $id]);
    }

    /**
     * Remove um usuário do banco de dados pelo ID.
     *
     * @param int $id ID do usuário a excluir
     * @return void
     */
    public function excluir(int $id): void {
        $this->pdo->prepare(
            "DELETE FROM usuario WHERE id_usuario = ?"
        )->execute([$id]);
    }

    /* -------------------------------------------------------
     * WEBHOOK — Power Automate
     * ------------------------------------------------------- */

    /**
     * Envia um e-mail de boas-vindas para o novo usuário via webhook
     * do Power Automate (Microsoft Flow).
     * Passa o e-mail do destinatário, o nome e a senha temporária.
     * SSL_VERIFYPEER está desabilitado por ser ambiente de desenvolvimento.
     *
     * @param string $email E-mail do destinatário
     * @param string $nome  Nome do usuário
     * @param string $senha Senha temporária em texto puro (antes do hash)
     * @return bool true se o webhook respondeu HTTP 2xx; false caso contrário
     */
    public function enviarEmailBoasVindas(string $email, string $nome, string $senha): bool {
        // URL do webhook do Power Automate responsável pelo envio do e-mail
        $url = 'https://default8b07e900cef146e7bb21cd2020d417.0f.environment.api.powerplatform.com:443'
             . '/powerautomate/automations/direct/workflows/7927792c7ce54b238ab0ebe0b6803c20'
             . '/triggers/manual/paths/invoke?api-version=1&sp=%2Ftriggers%2Fmanual%2Frun&sv=1.0'
             . '&sig=pWgVH4jfUX1U26Xr_orQm5b7frnTrwXFPysPTpPYKbc';

        // Monta o payload JSON com os dados que o Power Automate usará para compor o e-mail
        $body = json_encode([
            'email'  => $email,
            'titulo' => 'Gestão Logística — Seu acesso foi criado',
            'corpo'  => "Olá, {$nome}!\n\n"
                      . "Seu acesso ao sistema de Gestão Logística foi criado.\n\n"
                      . "Sua senha temporária é: {$senha}\n\n"
                      . "No primeiro acesso você será obrigado a criar uma nova senha.\n\n"
                      . " O link do site é: http://localhost/Gestao%20Logistica \n\n"
                      . "Atenção: não compartilhe essa senha com ninguém.",
        ]);

        // Dispara a requisição HTTP POST via cURL
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,  // Captura a resposta sem imprimir
            CURLOPT_TIMEOUT        => 15,    // Timeout de 15 s para não travar a requisição
            CURLOPT_SSL_VERIFYPEER => false, // Desabilitado em ambiente de desenvolvimento local
        ]);
        curl_exec($ch);

        // Verifica o código HTTP retornado pelo webhook
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Retorna true apenas se o servidor respondeu com sucesso (HTTP 200–299)
        return $httpCode >= 200 && $httpCode < 300;
    }
}
