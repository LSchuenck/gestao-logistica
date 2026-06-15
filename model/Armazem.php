<?php
/**
 * Model: Armazem
 *
 * Representa a entidade armazém (centro de distribuição) do sistema.
 * Cada armazém possui um endereço físico vinculado via id_endereco.
 */
class Armazem
{
    /** @var int|null ID único do armazém no banco de dados */
    public ?int $id_armazem = null;

    /** @var string Nome do armazém ou centro de distribuição */
    public string $nome = '';

    /** @var int|null Chave estrangeira para a tabela endereço */
    public ?int $id_endereco = null;
}
