<?php
/**
 * Model: Veiculo
 * Representa a entidade veículo do sistema.
 * Mapeamento direto das colunas da tabela 'veiculo'.
 */
class Veiculo {
    public int    $id_veiculo;
    public int    $id_transportadora;
    public string $placa;
    public string $modelo;
    public string $tipo_veiculo;      // Van | Caminhão | Carreta | Bitrem
    public float  $capacidade_carga;  // Capacidade em quilogramas
    public string $status;            // DISPONIVEL | EM_VIAGEM | MANUTENCAO
}
