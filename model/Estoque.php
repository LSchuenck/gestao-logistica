<?php
/**
 * Model: Estoque
 *
 * Representa o saldo de um produto em um armazém específico.
 * Cada registro corresponde à intersecção entre produto e armazém,
 * armazenando a quantidade disponível naquele local.
 */
class Estoque
{
    /** @var int|null ID único do registro de estoque no banco de dados */
    public ?int $id_estoque = null;

    /** @var int|null Chave estrangeira para o produto */
    public ?int $id_produto = null;

    /** @var int|null Chave estrangeira para o armazém */
    public ?int $id_armazem = null;

    /** @var int Quantidade atual disponível no armazém */
    public int $quantidade = 0;
}
