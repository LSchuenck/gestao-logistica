<?php
/**
 * Model: Produto
 *
 * Representa a entidade produto (SKU) do sistema de gestão logística.
 * Ao ser cadastrado, o produto recebe automaticamente um registro de
 * estoque com quantidade inicial zero na tabela 'estoque'.
 */
class Produto
{
    /** @var int|null ID único do produto no banco de dados */
    public ?int $id_produto = null;

    /** @var string Descrição ou nome do produto */
    public string $descricao = '';

    /** @var float|null Peso unitário em quilogramas (opcional) */
    public ?float $peso = null;

    /** @var float|null Volume unitário em metros cúbicos (opcional) */
    public ?float $volume = null;

    /** @var string|null Data de validade no formato Y-m-d (opcional) */
    public ?string $validade = null;
}
