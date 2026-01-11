sql basico para a utilização e atualização das politicas de privacidade e os termos de uso. 

INSERT INTO configuracoes (tipo, conteudo) VALUES 
('politica_privacidade', '<h1>Política de Privacidade</h1><p>Aqui vai o texto da política...</p>'),
('termos_uso', '<h1>Termos de Uso</h1><p>Aqui vai o texto dos termos...</p>');

CREATE TABLE configuracoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo VARCHAR(50) NOT NULL,  -- Ex: 'politica_privacidade' ou 'termos_uso'
    conteudo TEXT NOT NULL,     -- O texto completo (pode ser HTML para formatação)
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
https://grok.com/share/c2hhcmQtMw_eab3651f-0d84-49f7-94bd-003737030016