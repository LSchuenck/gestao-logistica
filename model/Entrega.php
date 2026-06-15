<?php
/**
 * Model: Entrega
 *
 * Representa a entidade entrega do sistema de gestão logística.
 * Uma entrega é composta por um cliente destinatário, um armazém de origem
 * opcional, datas prevista e realizada, peso, volume e status de andamento.
 * Os produtos vinculados são gerenciados pela tabela entrega_produto.
 */
class Entrega
{
    /** @var int|null ID único da entrega no banco de dados */
    public ?int $id_entrega = null;

    /** @var int|null Chave estrangeira para o cliente destinatário */
    public ?int $id_cliente = null;

    /** @var int|null Chave estrangeira para o armazém de origem (opcional) */
    public ?int $id_armazem = null;

    /** @var string|null Data prevista para a realização da entrega (formato Y-m-d) */
    public ?string $data_prevista = null;

    /** @var string|null Data em que a entrega foi efetivamente realizada (formato Y-m-d) */
    public ?string $data_realizada = null;

    /** @var float|null Peso total da carga em quilogramas */
    public ?float $peso_total = null;

    /** @var float|null Volume total da carga em metros cúbicos */
    public ?float $volume_total = null;

    /** @var string Status atual da entrega: PENDENTE, EM_TRANSITO, ENTREGUE ou ATRASADA */
    public string $status = 'PENDENTE';
}
