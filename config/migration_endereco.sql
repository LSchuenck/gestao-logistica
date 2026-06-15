-- Migration: desmembramento do campo endereco em tabela separada
-- Execute UMA VEZ no banco gestao_logistica

CREATE TABLE IF NOT EXISTS endereco (
    id_endereco INT AUTO_INCREMENT PRIMARY KEY,
    cep         VARCHAR(9),
    logradouro  VARCHAR(255),
    numero      VARCHAR(20),
    complemento VARCHAR(100),
    bairro      VARCHAR(100),
    cidade      VARCHAR(100),
    estado      CHAR(2)
);

ALTER TABLE transportadora
    DROP COLUMN endereco,
    ADD COLUMN id_endereco INT NULL,
    ADD CONSTRAINT fk_transportadora_endereco
        FOREIGN KEY (id_endereco) REFERENCES endereco(id_endereco);

ALTER TABLE cliente
    DROP COLUMN endereco,
    ADD COLUMN id_endereco INT NULL,
    ADD CONSTRAINT fk_cliente_endereco
        FOREIGN KEY (id_endereco) REFERENCES endereco(id_endereco);

ALTER TABLE armazem
    DROP COLUMN endereco,
    DROP COLUMN cidade,
    DROP COLUMN estado,
    ADD COLUMN id_endereco INT NULL,
    ADD CONSTRAINT fk_armazem_endereco
        FOREIGN KEY (id_endereco) REFERENCES endereco(id_endereco);
