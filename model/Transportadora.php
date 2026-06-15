<?php
/**
 * Model: Transportadora
 * Representa a entidade transportadora do sistema.
 * Mapeamento direto das colunas da tabela 'transportadora'.
 */
class Transportadora {
    public int    $id_transportadora;
    public string $cnpj;
    public string $razao_social;
    public string $nome_fantasia;
    public string $telefone;
    public string $email;
    public string $status;          // ATIVA | INATIVA
    public ?int   $id_endereco;
}
