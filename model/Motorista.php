<?php
/**
 * Model: Motorista
 * Representa a entidade motorista do sistema.
 * Mapeamento direto das colunas da tabela 'motorista'.
 */
class Motorista {
    public int     $id_motorista;
    public int     $id_transportadora;
    public string  $nome;
    public string  $cpf;
    public string  $cnh;
    public string  $categoria_cnh;    // B | C | D | E
    public ?string $validade_cnh;     // NULL quando não informada
    public string  $telefone;
    public string  $status;           // ATIVO | INATIVO
}
