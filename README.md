# Sistema de Gestão Logística

Sistema web para controle de operações logísticas, desenvolvido em PHP puro com MySQL, seguindo o padrão MVC + DAO.

## Tecnologias

- **Backend:** PHP 8+ (sem framework)
- **Banco de dados:** MySQL 8 via PDO
- **Frontend:** Bootstrap 5.3, Bootstrap Icons 1.11.1
- **Mapas:** Leaflet.js, Nominatim (geocoding), OSRM (roteamento)
- **Ambiente:** XAMPP (Apache + MySQL)

## Módulos

| Módulo | Descrição |
|---|---|
| Transportadoras | Cadastro de empresas com CNPJ, contato e status |
| Motoristas | Vínculo com transportadora, CNH, categoria e validade |
| Veículos | Placa, tipo, capacidade e status de disponibilidade |
| Clientes | Cadastro com endereço de entrega |
| Armazéns | Centros de distribuição com endereço |
| Produtos & Estoque | Controle de saldo por armazém com movimentações |
| Rotas | Planejamento com origem, destino e distância (mapa interativo) |
| Operações | Ciclo completo: rota → viagem → entregas |
| Fretes | Cálculo por km, peso ou valor fixo com emissão de NF |
| Alertas | ATRASO, VIAGEM, ESTOQUE, DESVIO_ROTA, PARADA_NAO_PROGRAMADA |
| Dashboard | KPIs em tempo real com badge de alertas ativos |
| Indicadores | Relatórios de desempenho operacional |
| Usuários | Gestão de acesso com perfis ADMIN, GERENTE e OPERADOR |

## Instalação

### Pré-requisitos

- XAMPP com PHP 8+ e MySQL 8
- Navegador moderno (Chrome, Firefox ou Edge)

### Passos

1. Clone o repositório na pasta `htdocs` do XAMPP:
   ```bash
   git clone https://github.com/LSchuenck/gestao-logistica.git "Gestao Logistica"
   ```

2. Inicie o Apache e o MySQL pelo painel do XAMPP.

3. Acesse o instalador no navegador:
   ```
   http://localhost/Gestao%20Logistica/instalar.php
   ```
   O instalador cria o banco `gestao_logistica` e carrega todos os dados de exemplo.

4. Acesse o sistema:
   ```
   http://localhost/Gestao%20Logistica/
   ```

### Credenciais padrão

| Campo | Valor |
|---|---|
| E-mail | `admin@gestao.com` |
| Senha  | `admin123` |

> Na primeira alteração de usuário, a senha temporária é enviada automaticamente por e-mail via integração com o **Microsoft Power Automate**.

## Estrutura do Projeto

```
/
├── assets/
│   ├── css/          # Estilos customizados
│   └── js/           # Scripts por módulo (alertas, entregas, frete, operacoes)
├── config/
│   ├── auth.php          # Autenticação, sessão e navbar
│   ├── conexao.php       # Conexão PDO com o MySQL
│   └── gestao_logistica.sql  # Script SQL autoritativo com dados de exemplo
├── controller/       # Controllers MVC (um por módulo)
├── dao/              # Data Access Objects (queries SQL)
├── model/            # Modelos de domínio
├── views/            # Templates PHP (HTML + PHP)
└── *.php             # Entry points de cada módulo
```

## Funcionalidades em destaque

- **Operações logísticas:** planejamento de rotas com mapa Leaflet, inicialização de viagens com desconto automático de estoque e estorno em caso de cancelamento
- **Simular Desvio:** recalcula a distância da rota a partir de uma nova posição no mapa usando OSRM (fallback Haversine)
- **Paradas não programadas:** registro de paradas durante viagens ativas com motivo e localização
- **Alertas dinâmicos:** gerados automaticamente a cada acesso sem necessidade de agendamento
- **Frete:** transportadora determinada automaticamente pela viagem selecionada

## Banco de dados

O script `config/gestao_logistica.sql` é o arquivo autoritativo. Ele cria todas as tabelas e insere dados de exemplo (transportadoras, motoristas, veículos, clientes, armazéns, produtos e estoque).

Para recriar o banco do zero, basta acessar `instalar.php`.
