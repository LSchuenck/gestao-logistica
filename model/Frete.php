<?php
/*
 * Arquivo: model/Frete.php
 * Finalidade: Representa a entidade Frete do sistema de gestão logística.
 * Contém apenas as propriedades públicas que espelham as colunas da tabela
 * `frete` no banco de dados, sem lógica de negócio ou acesso a dados.
 */

class Frete
{
    /** @var int|null Identificador único do frete */
    public ?int $id_frete = null;

    /** @var int Chave estrangeira para a viagem associada */
    public int $id_viagem;

    /** @var int Chave estrangeira para a transportadora responsável */
    public int $id_transportadora;

    /** @var float Valor cobrado pelo frete (receita) */
    public float $valor;

    /** @var float|null Custo operacional associado ao frete */
    public ?float $custo_operacional = null;

    /** @var string|null Número da nota fiscal emitida */
    public ?string $nota_fiscal = null;

    /** @var string|null Data de emissão da nota fiscal */
    public ?string $data_emissao = null;

    // Propriedades extras vindas de JOINs (não são colunas da tabela frete)

    /** @var string|null Status atual da viagem vinculada */
    public ?string $viagem_status = null;

    /** @var string|null Nome do motorista vinculado à rota da viagem */
    public ?string $motorista = null;

    /** @var string|null Placa do veículo vinculado à rota */
    public ?string $placa = null;

    /** @var string|null Nome fantasia da transportadora */
    public ?string $transportadora = null;

    /** @var string|null CNH do motorista (usado na DANFE) */
    public ?string $cnh = null;

    /** @var string|null Tipo do veículo (usado na DANFE) */
    public ?string $tipo_veiculo = null;

    /** @var string|null CNPJ da transportadora (usado na DANFE) */
    public ?string $cnpj = null;

    /** @var string|null Data de saída da viagem (usado na DANFE) */
    public ?string $data_saida = null;

    /** @var string|null Data prevista de chegada (usado na DANFE) */
    public ?string $data_chegada_prevista = null;

    /** @var string|null Data real de chegada (usado na DANFE) */
    public ?string $data_chegada_real = null;
}
