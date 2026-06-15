<?php
/**
 * Model: Cliente
 *
 * Representa a entidade cliente do sistema de gestão logística.
 * Pode ser pessoa física (CPF) ou pessoa jurídica (CNPJ).
 * Possui endereço vinculado via chave estrangeira id_endereco.
 */
class Cliente
{
    /** @var int|null ID único do cliente no banco de dados */
    public ?int $id_cliente = null;

    /** @var string Nome ou razão social do cliente */
    public string $nome = '';

    /** @var string|null CPF ou CNPJ do cliente (opcional) */
    public ?string $cpf_cnpj = null;

    /** @var string|null Telefone de contato do cliente */
    public ?string $telefone = null;

    /** @var int|null Chave estrangeira para a tabela endereço */
    public ?int $id_endereco = null;
}
