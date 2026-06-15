<?php
/**
 * Model: Usuario
 * Representa a entidade usuário do sistema.
 * Mapeamento direto das colunas da tabela 'usuario'.
 */
class Usuario {
    public int    $id_usuario;
    public string $nome;
    public string $email;
    public string $senha;           // Hash bcrypt — nunca texto puro
    public string $perfil;          // ADMIN | GERENTE | OPERADOR
    public string $status;          // ATIVO | INATIVO
    public int    $trocar_senha;    // 1 = deve trocar no próximo acesso; 0 = acesso liberado
    public string $data_cadastro;
}
