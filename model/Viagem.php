<?php
/*
 * Arquivo: model/Viagem.php
 * Finalidade: Representa a entidade Viagem do sistema de gestão logística.
 * Contém apenas as propriedades públicas que espelham as colunas da tabela
 * `viagem` no banco de dados, sem lógica de negócio ou acesso a dados.
 */

class Viagem
{
    /** @var int|null Identificador único da viagem */
    public ?int $id_viagem = null;

    /** @var int Chave estrangeira para a rota associada */
    public int $id_rota;

    /** @var string|null Data e hora de saída do veículo */
    public ?string $data_saida = null;

    /** @var string|null Data e hora prevista de chegada ao destino */
    public ?string $data_chegada_prevista = null;

    /** @var string|null Data e hora real de chegada (preenchida ao concluir) */
    public ?string $data_chegada_real = null;

    /** @var string Status atual da viagem: INICIADA, EM_TRANSITO, CONCLUIDA ou CANCELADA */
    public string $status = 'INICIADA';

    // Propriedades extras vindas de JOINs (não são colunas da tabela viagem)

    /** @var float|null Distância da rota em quilômetros */
    public ?float $distancia = null;

    /** @var string|null Nome do motorista vinculado à rota */
    public ?string $motorista = null;

    /** @var string|null Placa do veículo vinculado à rota */
    public ?string $placa = null;

    /** @var string|null Tipo/categoria do veículo */
    public ?string $tipo_veiculo = null;
}
