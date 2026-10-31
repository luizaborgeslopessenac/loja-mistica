-- =====================================================================
-- Arcana Lunar — Loja de produtos místicos
-- Script de recriação completa do banco (MySQL / MariaDB) + carga inicial
-- Uso: importe no phpMyAdmin ou rode:  mysql -u root -p < database/schema.sql
-- ATENÇÃO: recria as tabelas (DROP TABLE) — apaga dados existentes.
-- =====================================================================

CREATE DATABASE IF NOT EXISTS loja_mistica
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;
USE loja_mistica;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS itens_pedido;
DROP TABLE IF EXISTS pedidos;
DROP TABLE IF EXISTS produtos;
DROP TABLE IF EXISTS categorias;
DROP TABLE IF EXISTS usuarios;
SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------
-- Autenticação e perfis: e-mail único, senha armazenada como hash
-- (password_hash do PHP) e perfil que diferencia cliente de administrador.
-- ---------------------------------------------------------------------
CREATE TABLE usuarios (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nome        VARCHAR(120) NOT NULL,
    email       VARCHAR(150) NOT NULL,
    senha_hash  VARCHAR(255) NOT NULL,
    perfil      ENUM('cliente','admin') NOT NULL DEFAULT 'cliente',
    criado_em   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_usuarios_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Catálogo: categorias e produtos (preço, estoque e imagem)
-- ---------------------------------------------------------------------
CREATE TABLE categorias (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nome       VARCHAR(80) NOT NULL,
    descricao  VARCHAR(255) NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_categorias_nome (nome)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE produtos (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    categoria_id  INT UNSIGNED NOT NULL,
    nome          VARCHAR(150) NOT NULL,
    descricao     TEXT NOT NULL,
    preco         DECIMAL(10,2) NOT NULL,
    estoque       INT UNSIGNED NOT NULL DEFAULT 0,
    imagem        VARCHAR(255) NULL,
    ativo         TINYINT(1) NOT NULL DEFAULT 1,
    criado_em     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_produtos_categoria (categoria_id),
    KEY idx_produtos_ativo (ativo),
    CONSTRAINT fk_produtos_categoria FOREIGN KEY (categoria_id)
        REFERENCES categorias (id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT ck_produtos_preco CHECK (preco >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Transações de venda: cliente, data, forma de pagamento e total
-- ---------------------------------------------------------------------
CREATE TABLE pedidos (
    id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    usuario_id       INT UNSIGNED NOT NULL,
    data_pedido      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    forma_pagamento  ENUM('pix','boleto','cartao') NOT NULL,
    total            DECIMAL(10,2) NOT NULL,
    status           VARCHAR(20) NOT NULL DEFAULT 'pago',
    PRIMARY KEY (id),
    KEY idx_pedidos_usuario (usuario_id),
    CONSTRAINT fk_pedidos_usuario FOREIGN KEY (usuario_id)
        REFERENCES usuarios (id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT ck_pedidos_total CHECK (total >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Itens do pedido: resolve a relação N:N entre pedidos e produtos,
-- grava a quantidade e CONGELA o preço unitário praticado na compra.
-- ---------------------------------------------------------------------
CREATE TABLE itens_pedido (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    pedido_id       INT UNSIGNED NOT NULL,
    produto_id      INT UNSIGNED NOT NULL,
    quantidade      INT UNSIGNED NOT NULL,
    preco_unitario  DECIMAL(10,2) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_item_pedido_produto (pedido_id, produto_id),
    KEY idx_itens_produto (produto_id),
    CONSTRAINT fk_itens_pedido FOREIGN KEY (pedido_id)
        REFERENCES pedidos (id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_itens_produto FOREIGN KEY (produto_id)
        REFERENCES produtos (id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT ck_itens_quantidade CHECK (quantidade > 0),
    CONSTRAINT ck_itens_preco CHECK (preco_unitario >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- CARGA INICIAL DE TESTE
-- =====================================================================

-- Senhas de teste (já criptografadas com password_hash / bcrypt):
--   admin@arcanalunar.com  ->  Admin@123
--   cliente@teste.com      ->  Cliente@123
INSERT INTO usuarios (id, nome, email, senha_hash, perfil) VALUES
(1, 'Administrador Arcana', 'admin@arcanalunar.com', '$2y$12$NOc1/JErg7Yt0djua0TH2.GfuoRk987nNmCa8Dv2eJN.89LNDj3De', 'admin'),
(2, 'Cliente de Teste',     'cliente@teste.com',     '$2y$12$Km7woaQFRB2EE8dCWUsPI.P/RKSEs1Zpnymj8SevKK84G2STKnBRC', 'cliente');

INSERT INTO categorias (id, nome, descricao) VALUES
(1, 'Cristais e Pedras',   'Pedras brutas, roladas e esferas para meditação e decoração'),
(2, 'Tarô e Oráculos',     'Baralhos de tarô e oráculos para leitura e autoconhecimento'),
(3, 'Velas e Incensos',    'Velas rituais, incensos e defumadores'),
(4, 'Amuletos e Joias',    'Talismãs e joias simbólicas'),
(5, 'Ervas e Rituais',     'Ervas, kits de banho e utensílios rituais');

INSERT INTO produtos (id, categoria_id, nome, descricao, preco, estoque, imagem, ativo) VALUES
(1, 1, 'Ametista Bruta', 'Drusa de ametista natural, de cor violeta profunda. Tradicionalmente associada à calma e à intuição; peça única, cada unidade tem formato diferente. Tamanho médio (aprox. 8 cm).', 49.90, 25, 'assets/img/produtos/ametista.svg', 1),
(2, 1, 'Quartzo Rosa Rolado', 'Pedra rolada de quartzo rosa, polida à mão, perfeita para levar no bolso ou compor altares. Simboliza afeto e acolhimento. Aprox. 3 cm.', 29.90, 38, 'assets/img/produtos/quartzo-rosa.svg', 1),
(3, 1, 'Esfera de Obsidiana Negra', 'Esfera de obsidiana negra polida com base de madeira. Usada em práticas de proteção e contemplação. Aprox. 6 cm de diâmetro.', 89.90, 12, 'assets/img/produtos/obsidiana.svg', 1),
(4, 1, 'Pêndulo de Cristal de Quartzo', 'Pêndulo com ponta facetada de quartzo transparente e corrente prateada. Acompanha cartela com 3 modelos de tabuleiro para consulta.', 59.90, 18, 'assets/img/produtos/pendulo.svg', 1),
(5, 2, 'Tarô Rider-Waite Clássico', 'Baralho com 78 cartas (22 Arcanos Maiores e 56 Menores), ilustrações tradicionais e caixa rígida. Acompanha livreto introdutório em português.', 119.90, 15, 'assets/img/produtos/taro.svg', 1),
(6, 2, 'Oráculo das Fases da Lua', 'Baralho oráculo de 44 cartas dedicadas aos ciclos lunares, com bordas douradas e guia ilustrado para tiragens simples.', 99.90, 10, 'assets/img/produtos/oraculo.svg', 1),
(7, 3, 'Vela Ritual de Lua Cheia', 'Vela artesanal de cera vegetal com essência de lavanda e flocos de cristal. Queima aproximada de 40 horas. Feita para rituais de intenção.', 34.90, 49, 'assets/img/produtos/vela-lua.svg', 1),
(8, 3, 'Kit Incenso de Sálvia Branca', 'Caixa com 20 varetas de incenso de sálvia branca e um pequeno suporte de cerâmica. Aroma herbal intenso para limpeza de ambientes.', 39.90, 30, 'assets/img/produtos/incenso.svg', 1),
(9, 4, 'Amuleto Olho Grego', 'Pingente de vidro azul artesanal com argola de metal, símbolo de proteção contra o mau-olhado em várias culturas. Aprox. 4 cm.', 44.90, 35, 'assets/img/produtos/olho.svg', 1),
(10, 4, 'Colar Pentagrama de Prata', 'Colar com pingente de pentagrama em prata 925 e corrente de 45 cm. Acompanha saquinho de veludo.', 79.90, 8, 'assets/img/produtos/pentagrama.svg', 1),
(11, 5, 'Kit de Ervas para Banho de Proteção', 'Mistura de alecrim, arruda, manjericão e sal grosso, embalada em saquinho de algodão. Uso ritual e aromático. Rende 3 banhos.', 27.90, 3, 'assets/img/produtos/ervas.svg', 1),
(12, 5, 'Caldeirão Ritual de Ferro', 'Caldeirão de ferro fundido com três pés e tampa, ideal para queima de ervas e incensos em altares. Capacidade de 500 ml.', 149.90, 0, 'assets/img/produtos/caldeirao.svg', 1);

-- Pedido de exemplo (o estoque acima já considera estas unidades vendidas)
INSERT INTO pedidos (id, usuario_id, data_pedido, forma_pagamento, total, status) VALUES
(1, 2, '2026-10-20 14:32:00', 'pix', 94.70, 'pago');

INSERT INTO itens_pedido (pedido_id, produto_id, quantidade, preco_unitario) VALUES
(1, 2, 2, 29.90),
(1, 7, 1, 34.90);
