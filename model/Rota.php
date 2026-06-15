<?php
/**
 * Model: Rota
 * Representa a entidade rota do sistema de gestão logística.
 * Mapeamento direto das colunas da tabela 'rota'.
 */
class Rota {
    public int     $id_rota;
    public int     $id_veiculo;
    public int     $id_motorista;
    public ?float  $distancia;       // Distância total estimada em km; NULL quando não informada
    public string  $status;          // PLANEJADA | EM_ANDAMENTO | FINALIZADA
}
