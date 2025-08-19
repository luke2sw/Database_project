CREATE TABLE OSPEDALE(
    Codice CHAR(20) PRIMARY KEY,
    Nome VARCHAR(50) NOT NULL,
    Indirizzo VARCHAR(255) NOT NULL
);

INSERT INTO OSPEDALE (Codice, Nome, Indirizzo) VALUES
('OSP001', 'Ospedale Maggiore', 'Via Roma 1, Milano'),
('OSP002', 'Ospedale Centrale', 'Piazza Duomo 10, Napoli'),
('OSP003', 'Ospedale di Bologna', 'Via Garibaldi 20, Bologna'),
('OSP004', 'Ospedale di Torino', 'Corso Vittorio Emanuele II 15, Torino'),
('OSP005', 'Ospedale di Firenze', 'Piazza Santa Maria Novella 3, Firenze');



CREATE TABLE Reparto (
    Telefono CHAR(20) PRIMARY KEY,
    Nome VARCHAR(50) NOT NULL,   
    Ospedale CHAR(20) NOT NULL,
    Direttore CHAR(16),
    UNIQUE(Nome, Ospedale),
    FOREIGN KEY (Ospedale) REFERENCES Ospedale(Codice)
);

INSERT INTO REPARTO (Telefono, Nome, Ospedale, Direttore) VALUES

('0246881234', 'Cardiologia', 'OSP001', NULL),
('0815589856', 'Diagnostica', 'OSP001', NULL),
('0559876543', 'Ortopedia', 'OSP001', NULL),

('0815489867', 'Oftalmologia', 'OSP002', NULL),
('0265589876', 'Cardiologia', 'OSP002', NULL),
('0315589876', 'Chirurgia', 'OSP002', NULL),
('0879589823', 'Neurologia', 'OSP002', NULL),
('0815584676', 'Ematologia', 'OSP002', NULL),
('0815779876', 'Radiologia', 'OSP002', NULL),
('0246885556', 'Diagnostica', 'OSP002', NULL),

('0511234567', 'Oncologia', 'OSP003', NULL),
('0116543210', 'Pediatria', 'OSP003', NULL),
('0815586576', 'Ginecologia', 'OSP003', NULL),

('0559876354', 'Ortopedia', 'OSP004', NULL),
('0815623412', 'Dermatologia', 'OSP004', NULL),

('0815589876', 'Neurologia', 'OSP005', NULL),
('0834589870', 'Radiologia', 'OSP005', NULL);

CREATE TABLE STANZA (
    NumeroStanza INTEGER CHECK (NumeroStanza > 0),
    Reparto CHAR(20),
    Piano INTEGER NOT NULL CHECK (Piano > 0),
    Tipologia VARCHAR(255),
    CHECK(Tipologia = 'Ambulatorio' OR Tipologia = 'Sala operatoria' OR Tipologia = 'Ricovero'),
    PRIMARY KEY (NumeroStanza, Reparto),
    FOREIGN KEY (Reparto) REFERENCES Reparto(Telefono)
);

INSERT INTO STANZA (NumeroStanza, Reparto, Piano, Tipologia) VALUES
(101, '0246881234', 1, 'Ricovero'),
(102, '0246881234', 1, 'Ricovero'),
(201, '0246881234', 2, 'Ambulatorio'),
(202, '0246881234', 2, 'Ambulatorio'),
(301, '0246881234', 3, 'Sala operatoria'),
(302, '0246881234', 3, 'Sala operatoria'),

(101, '0815589876', 1, 'Ricovero'),
(102, '0815589876', 1, 'Ricovero'),
(201, '0815589876', 2, 'Ambulatorio'),
(301, '0815589876', 3, 'Sala operatoria'),

(101, '0246885556', 1, 'Ricovero'),
(102, '0246885556', 1, 'Ricovero'),
(201, '0246885556', 2, 'Ambulatorio'),
(301, '0246885556', 2, 'Sala operatoria'),

(101, '0815779876', 1, 'Ricovero'),
(102, '0815779876', 1, 'Ricovero'),
(103, '0815779876', 1, 'Ricovero'),
(201, '0815779876', 2, 'Ambulatorio'),
(301, '0815779876', 2, 'Sala operatoria');


CREATE TABLE ORARIO(
    Apertura TIME,
    Chiusura TIME,
    Giorno VARCHAR(9),
    CHECK(Apertura < Chiusura),
    PRIMARY KEY (Apertura, Chiusura, Giorno)
);

INSERT INTO ORARIO(Apertura, Chiusura, Giorno) VALUES
('08:00:00', '18:00:00', 'Lunedì'),
('08:00:00', '18:00:00', 'Martedì'),
('08:00:00', '18:00:00', 'Mercoledì'),
('08:00:00', '18:00:00', 'Giovedì'),
('08:00:00', '18:00:00', 'Venerdì'),
('08:00:00', '15:00:00', 'Sabato'),
('08:00:00', '15:00:00', 'Domenica'),

('08:00:00', '12:00:00', 'Sabato'),
('08:00:00', '12:00:00', 'Domenica'),

('08:00:00', '16:00:00', 'Lunedì'),
('08:00:00', '16:00:00', 'Martedì'),
('08:00:00', '16:00:00', 'Mercoledì'),
('08:00:00', '16:00:00', 'Giovedì'),
('08:00:00', '16:00:00', 'Venerdì');




CREATE TABLE REPARTOPOSSIEDEORARIO(
    Reparto CHAR(20),
    Apertura TIME,
    Chiusura TIME,
    Giorno VARCHAR(9),
    CHECK (Apertura < Chiusura),

    PRIMARY KEY (Reparto, Apertura, Chiusura, Giorno),
    FOREIGN KEY (Reparto) REFERENCES REPARTO(Telefono),
    FOREIGN KEY (Apertura, Chiusura, Giorno) REFERENCES ORARIO(Apertura, Chiusura, Giorno)
);

INSERT INTO REPARTOPOSSIEDEORARIO (Reparto, Apertura, Chiusura, Giorno) VALUES

/*Cardiologia*/
('0246881234', '08:00:00', '18:00:00', 'Lunedì'),
('0246881234', '08:00:00', '18:00:00', 'Martedì'),
('0246881234', '08:00:00', '18:00:00', 'Mercoledì'),
('0246881234', '08:00:00', '18:00:00', 'Giovedì'),
('0246881234', '08:00:00', '18:00:00', 'Venerdì'),
('0246881234', '08:00:00', '15:00:00', 'Sabato'),
('0246881234', '08:00:00', '15:00:00', 'Domenica'),

/*Diagnostica*/
('0815589856', '08:00:00', '18:00:00', 'Lunedì'),
('0815589856', '08:00:00', '18:00:00', 'Martedì'),
('0815589856', '08:00:00', '18:00:00', 'Mercoledì'),
('0815589856', '08:00:00', '18:00:00', 'Giovedì'),
('0815589856', '08:00:00', '18:00:00', 'Venerdì'),
('0815589856', '08:00:00', '15:00:00', 'Sabato'),
('0815589856', '08:00:00', '15:00:00', 'Domenica'),

/*Ortopedia*/
('0559876543', '08:00:00', '18:00:00', 'Lunedì'),
('0559876543', '08:00:00', '18:00:00', 'Martedì'),
('0559876543', '08:00:00', '18:00:00', 'Mercoledì'),
('0559876543', '08:00:00', '18:00:00', 'Giovedì'),
('0559876543', '08:00:00', '18:00:00', 'Venerdì'),
('0559876543', '08:00:00', '15:00:00', 'Sabato'),
('0559876543', '08:00:00', '15:00:00', 'Domenica'),

/*Oftalmologia*/
('0815489867', '08:00:00', '18:00:00', 'Lunedì'),
('0815489867', '08:00:00', '18:00:00', 'Martedì'),
('0815489867', '08:00:00', '18:00:00', 'Mercoledì'),
('0815489867', '08:00:00', '18:00:00', 'Giovedì'),
('0815489867', '08:00:00', '18:00:00', 'Venerdì'),
('0815489867', '08:00:00', '15:00:00', 'Sabato'),
('0815489867', '08:00:00', '15:00:00', 'Domenica'),

/*Cardiologia*/
('0265589876', '08:00:00', '18:00:00', 'Lunedì'),
('0265589876', '08:00:00', '18:00:00', 'Martedì'),
('0265589876', '08:00:00', '18:00:00', 'Mercoledì'),
('0265589876', '08:00:00', '18:00:00', 'Giovedì'),
('0265589876', '08:00:00', '18:00:00', 'Venerdì'),
('0265589876', '08:00:00', '15:00:00', 'Sabato'),
('0265589876', '08:00:00', '15:00:00', 'Domenica'),

/*Chirurgia*/
('0315589876', '08:00:00', '18:00:00', 'Lunedì'),
('0315589876', '08:00:00', '18:00:00', 'Martedì'),
('0315589876', '08:00:00', '18:00:00', 'Mercoledì'),
('0315589876', '08:00:00', '18:00:00', 'Giovedì'),
('0315589876', '08:00:00', '18:00:00', 'Venerdì'),
('0315589876', '08:00:00', '15:00:00', 'Sabato'),
('0315589876', '08:00:00', '15:00:00', 'Domenica'),

/*Neurologia*/
('0879589823', '08:00:00', '18:00:00', 'Lunedì'),
('0879589823', '08:00:00', '18:00:00', 'Martedì'),
('0879589823', '08:00:00', '18:00:00', 'Mercoledì'),
('0879589823', '08:00:00', '18:00:00', 'Giovedì'),
('0879589823', '08:00:00', '18:00:00', 'Venerdì'),
('0879589823', '08:00:00', '12:00:00', 'Sabato'),
('0879589823', '08:00:00', '12:00:00', 'Domenica'),

/*Ematologia*/
('0815584676', '08:00:00', '18:00:00', 'Lunedì'),
('0815584676', '08:00:00', '18:00:00', 'Martedì'),
('0815584676', '08:00:00', '18:00:00', 'Mercoledì'),
('0815584676', '08:00:00', '18:00:00', 'Giovedì'),
('0815584676', '08:00:00', '18:00:00', 'Venerdì'),
('0815584676', '08:00:00', '15:00:00', 'Sabato'),
('0815584676', '08:00:00', '15:00:00', 'Domenica'),

/*Radiologia*/
('0815779876', '08:00:00', '18:00:00', 'Lunedì'),
('0815779876', '08:00:00', '18:00:00', 'Martedì'),
('0815779876', '08:00:00', '18:00:00', 'Mercoledì'),
('0815779876', '08:00:00', '18:00:00', 'Giovedì'),
('0815779876', '08:00:00', '18:00:00', 'Venerdì'),
('0815779876', '08:00:00', '15:00:00', 'Sabato'),
('0815779876', '08:00:00', '15:00:00', 'Domenica'),

/*Diagnostica*/
('0246885556', '08:00:00', '18:00:00', 'Lunedì'),
('0246885556', '08:00:00', '18:00:00', 'Martedì'),
('0246885556', '08:00:00', '18:00:00', 'Mercoledì'),
('0246885556', '08:00:00', '18:00:00', 'Giovedì'),
('0246885556', '08:00:00', '18:00:00', 'Venerdì'),
('0246885556', '08:00:00', '12:00:00', 'Sabato'),
('0246885556', '08:00:00', '12:00:00', 'Domenica'),

/*Oncologia*/
('0511234567', '08:00:00', '18:00:00', 'Lunedì'),
('0511234567', '08:00:00', '18:00:00', 'Martedì'),
('0511234567', '08:00:00', '18:00:00', 'Mercoledì'),
('0511234567', '08:00:00', '18:00:00', 'Giovedì'),
('0511234567', '08:00:00', '18:00:00', 'Venerdì'),
('0511234567', '08:00:00', '15:00:00', 'Sabato'),
('0511234567', '08:00:00', '15:00:00', 'Domenica'),

/*Pediatria*/
('0116543210', '08:00:00', '18:00:00', 'Lunedì'),
('0116543210', '08:00:00', '18:00:00', 'Martedì'),
('0116543210', '08:00:00', '18:00:00', 'Mercoledì'),
('0116543210', '08:00:00', '18:00:00', 'Giovedì'),
('0116543210', '08:00:00', '18:00:00', 'Venerdì'),
('0116543210', '08:00:00', '15:00:00', 'Sabato'),
('0116543210', '08:00:00', '15:00:00', 'Domenica'),

/*Ginecologia*/
('0815586576', '08:00:00', '18:00:00', 'Lunedì'),
('0815586576', '08:00:00', '18:00:00', 'Martedì'),
('0815586576', '08:00:00', '18:00:00', 'Mercoledì'),
('0815586576', '08:00:00', '18:00:00', 'Giovedì'),
('0815586576', '08:00:00', '18:00:00', 'Venerdì'),
('0815586576', '08:00:00', '12:00:00', 'Sabato'),
('0815586576', '08:00:00', '12:00:00', 'Domenica'),

/*Ortopedia*/
('0559876354', '08:00:00', '18:00:00', 'Lunedì'),
('0559876354', '08:00:00', '18:00:00', 'Martedì'),
('0559876354', '08:00:00', '18:00:00', 'Mercoledì'),
('0559876354', '08:00:00', '18:00:00', 'Giovedì'),
('0559876354', '08:00:00', '18:00:00', 'Venerdì'),
('0559876354', '08:00:00', '15:00:00', 'Sabato'),
('0559876354', '08:00:00', '15:00:00', 'Domenica'),

/*Dermatologia*/
('0815623412', '08:00:00', '18:00:00', 'Lunedì'),
('0815623412', '08:00:00', '18:00:00', 'Martedì'),
('0815623412', '08:00:00', '18:00:00', 'Mercoledì'),
('0815623412', '08:00:00', '18:00:00', 'Giovedì'),
('0815623412', '08:00:00', '18:00:00', 'Venerdì'),
('0815623412', '08:00:00', '15:00:00', 'Sabato'),
('0815623412', '08:00:00', '15:00:00', 'Domenica'),

/*Neurologia*/
('0815589876', '08:00:00', '18:00:00', 'Lunedì'),
('0815589876', '08:00:00', '18:00:00', 'Martedì'),
('0815589876', '08:00:00', '18:00:00', 'Mercoledì'),
('0815589876', '08:00:00', '18:00:00', 'Giovedì'),
('0815589876', '08:00:00', '18:00:00', 'Venerdì'),
('0815589876', '08:00:00', '12:00:00', 'Sabato'),
('0815589876', '08:00:00', '12:00:00', 'Domenica'),

/*Radiologia*/
('0834589870', '08:00:00', '18:00:00', 'Lunedì'),
('0834589870', '08:00:00', '18:00:00', 'Martedì'),
('0834589870', '08:00:00', '18:00:00', 'Mercoledì'),
('0834589870', '08:00:00', '18:00:00', 'Giovedì'),
('0834589870', '08:00:00', '18:00:00', 'Venerdì'),
('0834589870', '08:00:00', '15:00:00', 'Sabato'),
('0834589870', '08:00:00', '15:00:00', 'Domenica');




CREATE TABLE AMBULATORIOESTERNO(
    Telefono CHAR(13) PRIMARY KEY,
    Indirizzo VARCHAR(255) NOT NULL
);

INSERT INTO AMBULATORIOESTERNO (Telefono, Indirizzo) VALUES
('028645231', 'Via Verdi 15, Milano'),
('081562341', 'Via Toledo 20, Napoli'),
('050203899', 'Via Parri 8, Bologna');

CREATE TABLE ORARIOAMBULATORIOESTERNO(
    Ambulatorio CHAR(9),
    Apertura TIME,
    Chiusura TIME,
    Giorno VARCHAR(9),
    CHECK (Apertura < Chiusura),
    PRIMARY KEY (Ambulatorio, Apertura, Chiusura, Giorno),
    FOREIGN KEY (Ambulatorio) REFERENCES AMBULATORIOESTERNO(Telefono),
    FOREIGN KEY (Apertura, Chiusura, Giorno) REFERENCES ORARIO(Apertura, Chiusura, Giorno)
);

INSERT INTO ORARIOAMBULATORIOESTERNO (Ambulatorio, Apertura, Chiusura, Giorno) VALUES
('028645231', '08:00:00', '16:00:00', 'Lunedì'),
('028645231', '08:00:00', '16:00:00', 'Martedì'),
('028645231', '08:00:00', '16:00:00', 'Mercoledì'),
('028645231', '08:00:00', '16:00:00', 'Giovedì'),
('028645231', '08:00:00', '16:00:00', 'Venerdì'),
('028645231', '08:00:00', '12:00:00', 'Sabato'),
('028645231', '08:00:00', '12:00:00', 'Domenica'),
    
('081562341', '08:00:00', '16:00:00', 'Lunedì'),
('081562341', '08:00:00', '16:00:00', 'Martedì'),
('081562341', '08:00:00', '16:00:00', 'Mercoledì'),
('081562341', '08:00:00', '16:00:00', 'Giovedì'),
('081562341', '08:00:00', '16:00:00', 'Venerdì'),
('081562341', '08:00:00', '12:00:00', 'Sabato'),
('081562341', '08:00:00', '12:00:00', 'Domenica'),

('050203899', '08:00:00', '18:00:00', 'Lunedì'),
('050203899', '08:00:00', '18:00:00', 'Martedì'),
('050203899', '08:00:00', '18:00:00', 'Mercoledì'),
('050203899', '08:00:00', '18:00:00', 'Giovedì'),
('050203899', '08:00:00', '18:00:00', 'Venerdì'),
('050203899', '08:00:00', '15:00:00', 'Sabato'),
('050203899', '08:00:00', '15:00:00', 'Domenica');

CREATE TABLE SPECIALIZZAZIONE(
    Nome VARCHAR(255) PRIMARY KEY
);
INSERT INTO SPECIALIZZAZIONE (Nome) VALUES
('Cardiologia'),
('Chirurgia'),
('Neurologia'),
('Ematologia'),
('Radiologia'),
('Diagnostica'),
('Oncologia'),
('Ortopedia'),
('Dermatologia'),
('Pediatria'),
('Oftalmologia'),
('Ginecologia');


CREATE TABLE ESAME(
    Codice CHAR(9) PRIMARY KEY,
    Descrizione VARCHAR(255),
    CostoPubblico DECIMAL(10,2) NOT NULL CHECK (CostoPubblico > 0),
    CostoPrivato DECIMAL(10,2) NOT NULL CHECK (CostoPrivato > 0),
    Specializzazione VARCHAR(255),
    FOREIGN KEY (Specializzazione) REFERENCES SPECIALIZZAZIONE(Nome)
);

INSERT INTO ESAME (Codice, Descrizione, CostoPubblico, CostoPrivato, Specializzazione) VALUES
('ESM001', 'Ecocardiogramma', 100.00, 150.00, 'Cardiologia'),
('ESM002', 'Risonanza Magnetica', 200.00, 300.00, 'Neurologia'),
('ESM003', 'TAC', 200.00, 400.00, 'Radiologia'),
('ESM004', 'Ecografia', 75.00, 150.00, 'Diagnostica'),
('ESM005', 'Elettrocardiogramma', 60.00, 120.00, 'Cardiologia');

CREATE TABLE PATOLOGIA(
    Nome VARCHAR(255) PRIMARY KEY
);

INSERT INTO PATOLOGIA (Nome) VALUES
('Ipertensione'),
('Diabete'),
('Asma'),
('Insufficienza cardiaca'),
('Cancro');


CREATE TABLE PAZIENTE(
    NumTesseraSanitaria CHAR(20) PRIMARY KEY,
    Indirizzo VARCHAR(255) NOT NULL,
    Telefono CHAR(13) UNIQUE NOT NULL,
    Nome VARCHAR(255) NOT NULL,
    Cognome VARCHAR(255) NOT NULL,
    DataNascita DATE NOT NULL,
    CHECK (LENGTH(NumTesseraSanitaria) = 20),
    CHECK (DataNascita <= CURRENT_DATE)
);

INSERT INTO PAZIENTE (NumTesseraSanitaria, Indirizzo, Telefono, Nome, Cognome, DataNascita) VALUES
('TS001000000000000001', 'Via delle Rose 2, Torino', '011123456', 'Mario', 'Rossi', '1985-01-15'),
('TS001000000000000002', 'Via della Libertà 8, Palermo', '091654321', 'Luigi', 'Verdi', '1977-06-21'),
('TS001000000000000003', 'Via dei Mille 10, Roma', '066123456', 'Giuseppe', 'Bianchi', '1990-12-02'),
('TS001000000000000004', 'Via Garibaldi 2, Genova', '010987654', 'Giovanni', 'Bianchi', '1970-02-15'),
('TS001000000000000005', 'Via Cavour 10, Roma', '064123456', 'Mario', 'Rossi', '1988-03-22'),
('TS001000000000000006', 'Via Mazzini 50, Napoli', '081789123', 'Lucia', 'Verdi', '1990-11-01'),
('TS001000000000000007', 'Corso Italia, 15, Napoli', '036789012', 'Giulia', 'Ferrari', '1995-02-28'),
('TS001000000000000008', 'Via Mazzini, 25, Roma', '037890123', 'Paolo', 'Lombardi', '1987-12-07'),
('TS001000000000000009', 'Via Leonardo da Vinci, 12, Milano', '038901234', 'Laura', 'Martini', '1991-06-10'),
('TS001000000000000010', 'Via Garibaldi, 30, Venezia', '039012345', 'Roberto', 'Conti', '1984-04-03');

CREATE TABLE PAZIENTEHAPATOLOGIA(
    NumTesseraSanitaria CHAR(20),
    Patologia VARCHAR(255),
    PRIMARY KEY (NumTesseraSanitaria, Patologia),
    FOREIGN KEY (NumTesseraSanitaria) REFERENCES PAZIENTE(NumTesseraSanitaria),
    FOREIGN KEY (Patologia) REFERENCES PATOLOGIA(Nome)
);

INSERT INTO PAZIENTEHAPATOLOGIA(NumTesseraSanitaria, Patologia)VALUES 
('TS001000000000000001', 'Ipertensione'),
('TS001000000000000002', 'Asma'),
('TS001000000000000003', 'Cancro'),
('TS001000000000000004', 'Ipertensione'),
('TS001000000000000005', 'Diabete'),
('TS001000000000000006', 'Asma'),
('TS001000000000000007', 'Insufficienza cardiaca'),
('TS001000000000000009', 'Ipertensione'),
('TS001000000000000010', 'Diabete');





CREATE TABLE LETTO(
    Stanza INTEGER,
    Reparto CHAR(20),
    NumeroLetto INTEGER CHECK (NumeroLetto > 0),
    PRIMARY KEY (Stanza, Reparto, NumeroLetto),
    FOREIGN KEY (Stanza, Reparto) REFERENCES STANZA(NumeroStanza, Reparto)
);



INSERT INTO LETTO (Stanza, Reparto, NumeroLetto) VALUES

/*Cardiologia*/
(101, '0246881234', 1),
(101, '0246881234', 2),
(101, '0246881234', 3),
(101, '0246881234', 4),
(101, '0246881234', 5),
(102, '0246881234', 1),
(102, '0246881234', 2),
(102, '0246881234', 3),
(102, '0246881234', 4),
(102, '0246881234', 5),

/*Neurologia*/
(101, '0815589876', 1),
(101, '0815589876', 2),
(101, '0815589876', 3),
(101, '0815589876', 4),
(101, '0815589876', 5),
(102, '0815589876', 1),
(102, '0815589876', 2),
(102, '0815589876', 3),
(102, '0815589876', 4),
(102, '0815589876', 5),


/*Diagnostica*/
(101, '0246885556', 1),
(101, '0246885556', 2),
(101, '0246885556', 3),
(101, '0246885556', 4),
(101, '0246885556', 5),
(102, '0246885556', 1),
(102, '0246885556', 2),
(102, '0246885556', 3),
(102, '0246885556', 4),
(102, '0246885556', 5),

/*Radiologia*/
(101, '0815779876', 1),
(101, '0815779876', 2),
(101, '0815779876', 3),
(101, '0815779876', 4),
(101, '0815779876', 5),
(102, '0815779876', 1),
(102, '0815779876', 2),
(102, '0815779876', 3),
(102, '0815779876', 4),
(102, '0815779876', 5),
(103, '0815779876', 1),
(103, '0815779876', 2),
(103, '0815779876', 3),
(103, '0815779876', 4),
(103, '0815779876', 5);


CREATE TABLE PERSONALE(
    CodiceFiscale CHAR(16) PRIMARY KEY,
    Reparto CHAR(20),
    Ruolo VARCHAR(255) NOT NULL,
    DataPromozionePrimario DATE,
    DataPromozioneVicePrimario DATE,
    DataAssunzione DATE NOT NULL,
    CHECK (Ruolo = 'Medico' OR Ruolo = 'Infermiere' OR Ruolo = 'Amministrativo'),
    CHECK (DataAssunzione <= DataPromozionePrimario OR DataPromozionePrimario IS NULL),
    CHECK (DataAssunzione <= DataPromozioneVicePrimario OR DataPromozioneVicePrimario IS NULL),
    CHECK (DataPromozioneVicePrimario <= DataPromozionePrimario OR DataPromozioneVicePrimario IS NULL),
    FOREIGN KEY (Reparto) REFERENCES Reparto(Telefono)

);

/*rispettivamente nell'ordine: Primario, Vice-Primario, Medico, Infermiere, Amministrativo*/
INSERT INTO PERSONALE (CodiceFiscale, Reparto, Ruolo, DataPromozionePrimario, DataPromozioneVicePrimario, DataAssunzione) VALUES

/*Reparto: Cardiologia, OSP: OSP001*/
('PRSNL0016CHAR001', '0246881234', 'Medico', '2002-03-03', '2001-02-02', '2000-01-01'),
('PRSNL0026CHAR002', '0246881234', 'Medico', NULL, '2001-09-09', '2000-08-08'),
('PRSNL0036CHAR003', '0246881234', 'Medico', NULL, '2001-09-19', '2000-08-03'),
('PRSNL0046CHAR004', '0246881234', 'Medico', NULL, NULL, '2000-01-01'),/*T.P.*/
('PRSNL0056CHAR005', '0246881234', 'Medico', NULL, NULL, '2000-01-02'),
('PRSNL0066CHAR006', '0246881234', 'Medico', NULL, NULL, '2000-01-03'),
('PRSNL0076CHAR007', '0246881234', 'Infermiere',  NULL, NULL,  '2007-06-13'),
('PRSNL0086CHAR008', '0246881234', 'Infermiere',  NULL, NULL, '2008-04-15'),/*T.P.*/
('PRSNL0096CHAR009', '0246881234', 'Amministrativo', NULL, NULL, '2018-01-01'),

/*Reparto: Diagnostica, OSP: OSP001*/
('PRSNL0106CHAR010', '0815589856', 'Medico', '2003-03-03', '2002-02-02', '2001-01-01'),/*SOST*/
('PRSNL0116CHAR011', '0815589856', 'Medico', NULL, '2002-09-09', '2001-08-08'),/*SOST*/
('PRSNL0126CHAR012', '0815589856', 'Medico', NULL, NULL, '2001-01-01'),
('PRSNL0136CHAR013', '0815589856', 'Medico', NULL, NULL, '2001-01-02'),
('PRSNL0146CHAR014', '0815589856', 'Medico', NULL, NULL, '2001-01-03'),
('PRSNL0156CHAR015', '0815589856', 'Infermiere', NULL, NULL, '2016-11-21'),
('PRSNL0166CHAR016', '0815589856', 'Infermiere', NULL, NULL, '2018-10-09' ),
('PRSNL0176CHAR017', '0815589856', 'Amministrativo', NULL, NULL, '2018-03-21'),

/*Reparto: Ortopedia, OSP: OSP001*/
('PRSNL0186CHAR018', '0559876543', 'Medico', '2004-03-03', '2003-02-02', '2002-01-01'),
('PRSNL0196CHAR019', '0559876543', 'Medico', NULL, '2002-09-09', '2001-08-08'),
('PRSNL0206CHAR020', '0559876543', 'Medico', NULL, '2002-01-19', '2001-08-18'),
('PRSNL0216CHAR021', '0559876543', 'Medico', NULL, NULL, '2002-01-01'),
('PRSNL0226CHAR022', '0559876543', 'Medico', NULL, NULL, '2002-01-02'),
('PRSNL0236CHAR023', '0559876543', 'Medico', NULL, NULL, '2002-01-03'),
('PRSNL0246CHAR024', '0559876543', 'Infermiere', NULL, NULL, '2005-12-20'),
('PRSNL0256CHAR025', '0559876543', 'Infermiere', NULL, NULL, '2015-12-20'),
('PRSNL0266CHAR026', '0559876543', 'Amministrativo', NULL, NULL, '2018-03-13'),

/*Reparto: Oftalmologia, OSP: OSP002*/
('PRSNL0276CHAR027', '0815489867', 'Medico', '2005-03-03', '2004-02-02', '2003-01-01'),
('PRSNL0286CHAR028', '0815489867', 'Medico', NULL, '2003-09-09', '2002-08-08'),
('PRSNL0296CHAR029', '0815489867', 'Medico', NULL, NULL, '2003-01-01'),
('PRSNL0306CHAR030', '0815489867', 'Medico', NULL, NULL, '2003-01-02'),
('PRSNL0316CHAR031', '0815489867', 'Medico', NULL, NULL, '2003-01-03'),
('PRSNL0326CHAR032', '0815489867', 'Infermiere', NULL, NULL, '2004-12-20' ),
('PRSNL0336CHAR033', '0815489867', 'Infermiere', NULL, NULL, '2015-12-20' ),
('PRSNL0346CHAR034', '0815489867', 'Amministrativo', NULL, NULL, '2018-02-21'),

/*Reparto: Cardiologia, OSP: OSP002*/
('PRSNL0356CHAR035', '0265589876', 'Medico', '2006-03-03', '2005-02-02', '2004--01-01'),/*SOST*/
('PRSNL0366CHAR036', '0265589876', 'Medico', NULL, '2004-09-09', '2003-08-08'),
('PRSNL0376CHAR037', '0265589876', 'Medico', NULL, '2004-07-09', '2003-08-18'),
('PRSNL0386CHAR038', '0265589876', 'Medico', NULL, '2004-08-09', '2003-08-28'),/*SOST*/
('PRSNL0396CHAR039', '0265589876', 'Medico', NULL, NULL, '2004-01-01'),
('PRSNL0406CHAR040', '0265589876', 'Medico', NULL, NULL, '2004-01-02'),
('PRSNL0416CHAR041', '0265589876', 'Medico', NULL, NULL, '2004-01-03'),
('PRSNL0426CHAR042', '0265589876', 'Infermiere', NULL, NULL, '2000-12-20'),
('PRSNL0436CHAR043', '0265589876', 'Infermiere', NULL, NULL, '2002-12-20'),
('PRSNL0446CHAR044', '0265589876', 'Amministrativo', NULL, NULL, '2018-04-21'),

/*Reparto: Chirurgia, OSP: OSP002*/
('PRSNL0456CHAR045', '0315589876', 'Medico', '2007-03-03', '2006-02-02', '2005-01-01'),
('PRSNL0466CHAR046', '0315589876', 'Medico', NULL, '2005-09-09', '2004-08-08'),
('PRSNL0476CHAR047', '0315589876', 'Medico', NULL, NULL, '2005-01-01'),/*T.P.*/
('PRSNL0486CHAR048', '0315589876', 'Medico', NULL, NULL, '2005-01-02'),
('PRSNL0496CHAR049', '0315589876', 'Medico', NULL, NULL, '2005-01-03'),
('PRSNL0506CHAR050', '0315589876', 'Infermiere', NULL, NULL, '2005-12-21'),
('PRSNL0516CHAR051', '0315589876', 'Infermiere', NULL, NULL, '2005-08-20'),/*T.P.*/
('PRSNL0526CHAR052', '0315589876', 'Amministrativo', NULL, NULL, '2019-03-21'),

/*Reparto: Neurologia, OSP: OSP002*/
('PRSNL0536CHAR053', '0879589823', 'Medico', '2008-03-03', '2007-02-02', '2001-01-02'),
('PRSNL0546CHAR054', '0879589823', 'Medico', NULL, '2006-09-09', '2005-08-08'),
('PRSNL0556CHAR055', '0879589823', 'Medico', NULL, NULL, '2006-01-01'),
('PRSNL0566CHAR056', '0879589823', 'Medico', NULL, NULL, '2006-01-02'),
('PRSNL0576CHAR057', '0879589823', 'Medico', NULL, NULL, '2006-01-03'),
('PRSNL0586CHAR058', '0879589823', 'Infermiere', NULL, NULL, '2017-12-20'),
('PRSNL0596CHAR059', '0879589823', 'Infermiere', NULL, NULL, '2019-12-20'),
('PRSNL0606CHAR060', '0879589823', 'Amministrativo', NULL, NULL, '2020-03-21'),

/*Reparto: Ematologia, OSP: OSP002*/
('PRSNL0616CHAR061', '0815584676', 'Medico', '2009-03-03', '2008-02-02', '2002-01-02'),/*SOST*/
('PRSNL0626CHAR062', '0815584676', 'Medico', NULL, '2007-09-09', '2006-08-08'),/*SOST*/
('PRSNL0636CHAR063', '0815584676', 'Medico', NULL, '2007-01-09', '2006-08-13'),
('PRSNL0646CHAR064', '0815584676', 'Medico', NULL, NULL, '2007-01-01'),
('PRSNL0656CHAR065', '0815584676', 'Medico', NULL, NULL, '2007-01-02'),
('PRSNL0666CHAR066', '0815584676', 'Medico', NULL, NULL, '2007-01-03'),
('PRSNL0676CHAR067', '0815584676', 'Infermiere', NULL, NULL, '2005-12-02'),
('PRSNL0686CHAR068', '0815584676', 'Infermiere', NULL, NULL, '2005-06-20'),
('PRSNL0696CHAR069', '0815584676', 'Amministrativo', NULL, NULL, '2021-03-21'),

/*Reparto: Radiologia, OSP: OSP002*/
('PRSNL0706CHAR070', '0815779876', 'Medico', '2010-03-03', '2008-02-02', '2003-01-03'),
('PRSNL0716CHAR071', '0815779876', 'Medico', NULL, '2008-09-09', '2006-08-08'),
('PRSNL0726CHAR072', '0815779876', 'Medico', NULL, '2008-08-01', '2006-02-08'),
('PRSNL0736CHAR073', '0815779876', 'Medico', NULL, '2008-09-13', '2006-03-08'),
('PRSNL0746CHAR074', '0815779876', 'Medico', NULL, '2008-09-12', '2006-12-08'),
('PRSNL0756CHAR075', '0815779876', 'Medico', NULL, NULL, '2008-01-01'),
('PRSNL0766CHAR076', '0815779876', 'Medico', NULL, NULL, '2008-01-02'),
('PRSNL0776CHAR077', '0815779876', 'Medico', NULL, NULL, '2008-01-03'),
('PRSNL0786CHAR078', '0815779876', 'Infermiere', NULL, NULL, '2005-03-20'),
('PRSNL0796CHAR079', '0815779876', 'Infermiere', NULL, NULL, '2005-01-20'),
('PRSNL0806CHAR080', '0815779876', 'Amministrativo', NULL, NULL, '2018-03-14'),

/*Reparto: Diagnostica, OSP: OSP002*/
('PRSNL0816CHAR081', '0246885556', 'Medico', '2011-03-03', '2008-02-02', '2004-01-04'),/*SOST*/
('PRSNL0826CHAR082', '0246885556', 'Medico', NULL, '2009-09-09', '2008-08-08'),/*SOST*/
('PRSNL0836CHAR083', '0246885556', 'Medico', NULL, NULL, '2009-01-01'),
('PRSNL0846CHAR084', '0246885556', 'Medico', NULL, NULL, '2009-01-02'),
('PRSNL0856CHAR085', '0246885556', 'Medico', NULL, NULL, '2009-01-03'),
('PRSNL0866CHAR086', '0246885556', 'Infermiere', NULL, NULL, '2001-12-20'),
('PRSNL0876CHAR087', '0246885556', 'Infermiere', NULL, NULL, '2001-09-11'),
('PRSNL0886CHAR088', '0246885556', 'Amministrativo', NULL, NULL, '2018-03-15'),

/*Reparto: Oncologia, OSP: OSP003*/
('PRSNL0896CHAR089', '0511234567', 'Medico', '2017-03-03', '2016-02-02', '2005-01-05'),
('PRSNL0906CHAR090', '0511234567', 'Medico', NULL, '2010-09-09', '2009-08-08'),
('PRSNL0916CHAR091', '0511234567', 'Medico', NULL, NULL, '2010-01-01'),
('PRSNL0926CHAR092', '0511234567', 'Medico', NULL, NULL, '2010-01-02'),
('PRSNL0936CHAR093', '0511234567', 'Medico', NULL, NULL, '2010-01-03'),
('PRSNL0946CHAR094', '0511234567', 'Infermiere', NULL, NULL, '2018-12-20'),
('PRSNL0956CHAR095', '0511234567', 'Infermiere', NULL, NULL, '2021-12-20'),
('PRSNL0966CHAR096', '0511234567', 'Amministrativo', NULL, NULL, '2018-03-16'),

/*Reparto: Pediatria, OSP: OSP003*/
('PRSNL0976CHAR097', '0116543210', 'Medico', '2016-03-03', '2015-02-02', '2004-01-01'),
('PRSNL0986CHAR098', '0116543210', 'Medico', NULL, '2011-09-09', '2010-08-08'),
('PRSNL0996CHAR099', '0116543210', 'Medico', NULL, '2011-11-09', '2010-11-08'),
('PRSNL1006CHAR100', '0116543210', 'Medico', NULL, NULL, '2010-01-01'),/*TP*/
('PRSNL1016CHAR101', '0116543210', 'Medico', NULL, NULL, '2010-01-02'),
('PRSNL1026CHAR102', '0116543210', 'Medico', NULL, NULL, '2010-01-03'),
('PRSNL1036CHAR103', '0116543210', 'Infermiere', NULL, NULL, '2002-12-20'),
('PRSNL1046CHAR104', '0116543210', 'Infermiere', NULL, NULL, '2005-05-20'),/*TP*/
('PRSNL1056CHAR105', '0116543210', 'Amministrativo', NULL, NULL, '2018-03-17'),

/*Reparto: Ginecologia, OSP: OSP003*/
('PRSNL1066CHAR106', '0815586576', 'Medico', '2017-03-03', '2016-02-02', '2005-01-01'),
('PRSNL1076CHAR107', '0815586576', 'Medico', NULL, '2012-09-09', '2011-08-08'),
('PRSNL1086CHAR108', '0815586576', 'Medico', NULL, NULL, '2011-01-01'),
('PRSNL1096CHAR109', '0815586576', 'Medico', NULL, NULL, '2011-01-02'),
('PRSNL1106CHAR110', '0815586576', 'Medico', NULL, NULL, '2011-01-03'),
('PRSNL1116CHAR111', '0815586576', 'Infermiere', NULL, NULL, '2005-12-15'),
('PRSNL1126CHAR112', '0815586576', 'Infermiere', NULL, NULL, '2005-12-09'),
('PRSNL1136CHAR113', '0815586576', 'Amministrativo', NULL, NULL, '2018-03-18'),

/*Reparto: Ortopedia, OSP: OSP004*/
('PRSNL1146CHAR114', '0559876354', 'Medico', '2018-03-03', '2017-02-02', '2006--01-01'),
('PRSNL1156CHAR115', '0559876354', 'Medico', NULL, '2012-09-09', '2011-08-08'),
('PRSNL1166CHAR116', '0559876354', 'Medico', NULL, NULL, '2012-01-01'),
('PRSNL1176CHAR117', '0559876354', 'Medico', NULL, NULL, '2012-01-02'),
('PRSNL1186CHAR118', '0559876354', 'Medico', NULL, NULL, '2012-01-03'),
('PRSNL1196CHAR119', '0559876354', 'Infermiere', NULL, NULL, '2005-05-20'),
('PRSNL1206CHAR120', '0559876354', 'Infermiere', NULL, NULL, '2005-05-13'),
('PRSNL1216CHAR121', '0559876354', 'Amministrativo', NULL, NULL, '2018-03-19'),

/*Reparto: Dermatologia, OSP: OSP004*/
('PRSNL1226CHAR122', '0815623412', 'Medico', '2019-03-03', '2018-02-02', '2007--01-01'),
('PRSNL1236CHAR123', '0815623412', 'Medico', NULL, '2013-09-09', '2012-08-08'),
('PRSNL1246CHAR124', '0815623412', 'Medico', NULL, NULL, '2013-01-01'),
('PRSNL1256CHAR125', '0815623412', 'Medico', NULL, NULL, '2013-01-02'),
('PRSNL1266CHAR126', '0815623412', 'Medico', NULL, NULL, '2013-01-03'),
('PRSNL1276CHAR127', '0815623412', 'Infermiere', NULL, NULL, '2005-04-20'),
('PRSNL1286CHAR128', '0815623412', 'Infermiere', NULL, NULL, '2005-04-04'),
('PRSNL1296CHAR129', '0815623412', 'Amministrativo', NULL, NULL, '2018-08-21'),

/*Reparto: Neurologia, OSP: OSP005*/
('PRSNL1306CHAR130', '0815589876', 'Medico', '2020-03-03', '2019-02-02', '2008--01-01'),
('PRSNL1316CHAR131', '0815589876', 'Medico', NULL, '2014-09-09', '2013-08-08'),
('PRSNL1326CHAR132', '0815589876', 'Medico', NULL, NULL, '2014-01-01'),
('PRSNL1336CHAR133', '0815589876', 'Medico', NULL, NULL, '2014-01-02'),
('PRSNL1346CHAR134', '0815589876', 'Medico', NULL, NULL, '2014-01-03'),
('PRSNL1356CHAR135', '0815589876', 'Infermiere', NULL, NULL, '2004-05-20'),
('PRSNL1366CHAR136', '0815589876', 'Infermiere', NULL, NULL, '2004-11-14'),
('PRSNL1376CHAR137', '0815589876', 'Amministrativo', NULL, NULL, '2018-09-21'),

/*Reparto: Radiologia, OSP: OSP005*/
('PRSNL1386CHAR138', '0834589870', 'Medico', '2021-03-03', '2020-02-02', '2009--01-01'),
('PRSNL1396CHAR139', '0834589870', 'Medico', NULL, '2015-09-09', '2014-08-08'),
('PRSNL1406CHAR140', '0834589870', 'Medico', NULL, NULL, '2015-01-01'),
('PRSNL1416CHAR141', '0834589870', 'Medico', NULL, NULL, '2015-01-02'),
('PRSNL1426CHAR142', '0834589870', 'Medico', NULL, NULL, '2015-01-03'),
('PRSNL1436CHAR143', '0834589870', 'Infermiere', NULL, NULL, '2000-11-11'),
('PRSNL1446CHAR144', '0834589870', 'Infermiere', NULL, NULL, '2000-01-20'),
('PRSNL1456CHAR145', '0834589870', 'Amministrativo', NULL, NULL, '2018-10-21');

-- Aggiornamento della tabella Reparto per impostare i direttori
UPDATE Reparto
SET Direttore = 'PRSNL0016CHAR001'
WHERE Telefono = '0246881234';

UPDATE Reparto
SET Direttore = 'PRSNL0106CHAR010'
WHERE Telefono = '0815589856';

UPDATE Reparto
SET Direttore = 'PRSNL0186CHAR018'
WHERE Telefono = '0559876543';

UPDATE Reparto
SET Direttore = 'PRSNL0276CHAR027'
WHERE Telefono = '0815489867';

UPDATE Reparto
SET Direttore = 'PRSNL0356CHAR035'
WHERE Telefono = '0265589876';

UPDATE Reparto
SET Direttore = 'PRSNL0456CHAR045'
WHERE Telefono = '0315589876';

UPDATE Reparto
SET Direttore = 'PRSNL0536CHAR053'
WHERE Telefono = '0879589823';

UPDATE Reparto
SET Direttore = 'PRSNL0616CHAR061'
WHERE Telefono = '0815584676';

UPDATE Reparto
SET Direttore = 'PRSNL0706CHAR070'
WHERE Telefono = '0815779876';

UPDATE Reparto
SET Direttore = 'PRSNL0816CHAR081'
WHERE Telefono = '0246885556';

UPDATE Reparto
SET Direttore = 'PRSNL0896CHAR089'
WHERE Telefono = '0511234567';

UPDATE Reparto
SET Direttore = 'PRSNL0976CHAR097'
WHERE Telefono = '0116543210';

UPDATE Reparto
SET Direttore = 'PRSNL1066CHAR106'
WHERE Telefono = '0815586576';

UPDATE Reparto
SET Direttore = 'PRSNL1146CHAR114'
WHERE Telefono = '0559876354';

UPDATE Reparto
SET Direttore = 'PRSNL1226CHAR122'
WHERE Telefono = '0815623412';

UPDATE Reparto
SET Direttore = 'PRSNL1306CHAR130'
WHERE Telefono = '0815589876';

UPDATE Reparto
SET Direttore = 'PRSNL1386CHAR138'
WHERE Telefono = '0834589870';

-- Modifica della tabella Reparto per aggiungere il vincolo UNIQUE NOT NULL sulla colonna Direttore
ALTER TABLE Reparto ALTER COLUMN Direttore SET NOT NULL;
ALTER TABLE Reparto ADD CONSTRAINT unique_direttore UNIQUE (Direttore);
ALTER TABLE Reparto ADD CONSTRAINT fk_direttore FOREIGN KEY (Direttore) REFERENCES PERSONALE(CodiceFiscale);
 
CREATE TABLE MEDICOESTERNO(
    CodiceFiscale CHAR(16) PRIMARY KEY
);

INSERT INTO MEDICOESTERNO (CodiceFiscale) VALUES
('MEDEST0000000001'),
('MEDEST0000000002'),
('MEDEST0000000003'),
('MEDEST0000000004'),
('MEDEST0000000005'),
('MEDEST0000000006'),
('MEDEST0000000007'),
('MEDEST0000000008');

CREATE TABLE MEDICOESTERNOHASPECIALIZZAZIONE(
    MedicoEsterno CHAR(16),
    Specializzazione VARCHAR(255),
    PRIMARY KEY (MedicoEsterno, Specializzazione),
    FOREIGN KEY (MedicoEsterno) REFERENCES MEDICOESTERNO(CodiceFiscale),
    FOREIGN KEY (Specializzazione) REFERENCES SPECIALIZZAZIONE(Nome)
);

INSERT INTO MEDICOESTERNOHASPECIALIZZAZIONE (MedicoEsterno, Specializzazione) VALUES
('MEDEST0000000001', 'Cardiologia'),
('MEDEST0000000002', 'Diagnostica'),
('MEDEST0000000003', 'Chirurgia'),
('MEDEST0000000004', 'Neurologia'),
('MEDEST0000000005', 'Oftalmologia'),
('MEDEST0000000006', 'Radiologia'),
('MEDEST0000000007', 'Pediatria'),
('MEDEST0000000008', 'Ginecologia');


CREATE TABLE PRESCRIZIONE(
    Codice CHAR(20) PRIMARY KEY,
    Paziente CHAR(20), 
    MedicoInterno CHAR(16),
    MedicoEsterno CHAR(16),
    CHECK (MedicoEsterno IS NULL AND MedicoInterno IS NOT NULL 
    OR
    (MedicoEsterno IS NOT NULL AND MedicoInterno IS NULL)),
    FOREIGN KEY (MedicoInterno) REFERENCES PERSONALE(CodiceFiscale),
    FOREIGN KEY (MedicoEsterno) REFERENCES MEDICOESTERNO(CodiceFiscale),
    FOREIGN KEY (Paziente) REFERENCES PAZIENTE(NumTesseraSanitaria)
);
INSERT INTO PRESCRIZIONE (Codice,Paziente, MedicoInterno, MedicoEsterno ) VALUES
('PRESC001', 'TS001000000000000001', 'PRSNL0056CHAR005', NULL), /*Cardiologia*/
('PRESC002', 'TS001000000000000002', 'PRSNL0566CHAR056', NULL), /*Neurologia*/
('PRESC003', 'TS001000000000000003', NULL, 'MEDEST0000000006'), /*Radiologia*/
('PRESC004', 'TS001000000000000004', 'PRSNL0396CHAR039', NULL), /*Cardiologia*/
('PRESC005', 'TS001000000000000005', 'PRSNL0856CHAR085', NULL), /*Diagnostica*/
('PRESC006', 'TS001000000000000006', NULL, 'MEDEST0000000001'), /*Cardiologia*/
('PRESC007', 'TS001000000000000007', 'PRSNL1336CHAR133', NULL), /*Neurologia*/
('PRESC008', 'TS001000000000000008', 'PRSNL0376CHAR037', NULL), /*Cardiologia*/
('PRESC009', 'TS001000000000000009', 'PRSNL0756CHAR075', NULL), /*Radiologia*/
('PRESC010','TS001000000000000010', NULL, 'MEDEST0000000006'); /*Radiologia*/


CREATE TABLE PRENOTAZIONE(
    Urgenza VARCHAR(6) NOT NULL,
    DataPrenotazione DATE NOT NULL,
    DataOraEsame TIMESTAMP,
    Paziente CHAR(20),
    AmbulatorioEsterno CHAR(9),
    NumeroStanza INTEGER,
    Reparto CHAR(20),
    Esame CHAR(20),
    Prescrizione CHAR(20) UNIQUE,

    CHECK (Urgenza = 'Verde' OR Urgenza = 'Giallo' OR Urgenza = 'Rosso'),
    CHECK (DataPrenotazione <= CURRENT_DATE),
    CHECK (DataPrenotazione <= DataOraEsame),
    CHECK (
    (NumeroStanza IS NULL AND Reparto IS NULL AND AmbulatorioEsterno IS NOT NULL)
    OR
    (NumeroStanza IS NOT NULL AND Reparto IS NOT NULL AND AmbulatorioEsterno IS NULL)
    ),
    UNIQUE(Esame, DataPrenotazione, Paziente),
    PRIMARY KEY (DataOraEsame, Paziente),
    FOREIGN KEY (AmbulatorioEsterno) REFERENCES AMBULATORIOESTERNO(Telefono),
    FOREIGN KEY (Paziente) REFERENCES PAZIENTE(NumTesseraSanitaria),
    FOREIGN KEY (NumeroStanza, Reparto) REFERENCES STANZA(NumeroStanza, Reparto),  
    FOREIGN KEY (Esame) REFERENCES ESAME(Codice),
    FOREIGN KEY (Prescrizione) REFERENCES PRESCRIZIONE(Codice)
);

INSERT INTO PRENOTAZIONE (Urgenza, DataPrenotazione, DataOraEsame, Paziente, AmbulatorioEsterno, NumeroStanza, Reparto, Esame, Prescrizione) VALUES
('Rosso', '2023-06-22', '2023-07-10 11:30:00', 'TS001000000000000002', NULL, 102, '0815589876', 'ESM002', 'PRESC002'),
('Giallo', '2023-12-02', '2023-12-12 11:30:00','TS001000000000000003', '050203899', NULL, NULL, 'ESM003', 'PRESC003'),
('Verde', '2023-08-01', '2023-08-10 10:00:00', 'TS001000000000000004', NULL, 201, '0815589876', 'ESM001', 'PRESC004'),
('Rosso', '2023-06-22', '2023-07-11 11:30:00', 'TS001000000000000005', NULL, 101, '0246885556', 'ESM004', 'PRESC005'),
('Giallo', '2023-12-02', '2023-12-12 11:30:00','TS001000000000000006', '081562341', NULL, NULL, 'ESM005', 'PRESC006'),
('Giallo', '2023-11-14', '2023-11-24 11:30:00','TS001000000000000007', NULL, 102, '0815589876', 'ESM002', 'PRESC007'),
('Verde', '2023-08-01', '2023-08-10 10:00:00', 'TS001000000000000008', NULL, 302, '0246881234', 'ESM001', 'PRESC008'),
('Rosso', '2023-06-22', '2023-07-12 11:30:00', 'TS001000000000000009', NULL, 103, '0815779876', 'ESM003', 'PRESC009'),
('Giallo', '2023-12-02', '2023-12-12 11:30:00','TS001000000000000010', '050203899', NULL, NULL, 'ESM003', 'PRESC010');




CREATE TABLE RICOVERO(
    DataInizio DATE,
    DataDimissione DATE,
    Paziente CHAR(20),
    Letto INTEGER,
    NumeroStanza INTEGER,
    Reparto CHAR(20),
    CHECK (DataInizio <= DataDimissione OR DataDimissione IS NULL),
    CHECK (DataInizio <= CURRENT_DATE),
    CHECK (DataDimissione <= CURRENT_DATE OR DataDimissione IS NULL),
    UNIQUE(Letto, NumeroStanza, Reparto),
    PRIMARY KEY (DataInizio, Paziente),
    FOREIGN KEY (Paziente) REFERENCES PAZIENTE(NumTesseraSanitaria),

    FOREIGN KEY (NumeroStanza, Reparto, Letto) REFERENCES LETTO(Stanza, Reparto, NumeroLetto),  

    FOREIGN KEY (NumeroStanza, Reparto) REFERENCES STANZA(NumeroStanza, Reparto) 
);

INSERT INTO RICOVERO (DataInizio, DataDimissione, Paziente, NumeroStanza, Letto, Reparto) VALUES
('2023-07-10', '2023-08-12', 'TS001000000000000002', 102, 2, '0815589876'),
('2023-07-11', '2023-07-24', 'TS001000000000000005', 101, 1, '0246885556'),
('2023-11-24', NULL, 'TS001000000000000007', 102, 1, '0815589876'),
('2023-07-12', NULL, 'TS001000000000000009', 103, 2, '0815779876');




CREATE TABLE RICOVEROPATOLOGIA(
    Ricovero DATE,
    Patologia VARCHAR(255),
    Paziente CHAR(20),
    PRIMARY KEY (Ricovero, Patologia, Paziente),
    FOREIGN KEY (Ricovero, Paziente) REFERENCES RICOVERO(DataInizio, Paziente), 
    FOREIGN KEY (Patologia) REFERENCES PATOLOGIA(Nome),
    FOREIGN KEY (Paziente) REFERENCES PAZIENTE(NumTesseraSanitaria)
);

INSERT INTO RICOVEROPATOLOGIA (Ricovero, Patologia, Paziente) VALUES
('2023-07-10', 'Asma', 'TS001000000000000002'),
('2023-07-11', 'Diabete', 'TS001000000000000005'),
('2023-11-24', 'Insufficienza cardiaca', 'TS001000000000000007'),
('2023-07-12', 'Ipertensione', 'TS001000000000000009');






CREATE TABLE TURNOPRONTOSOCCORSO(
    Giorno VARCHAR(9),    
    OrarioInizio TIME,
    OrarioFine TIME,
    Personale CHAR(16),
    Ospedale CHAR(20) NOT NULL,
    CHECK (OrarioInizio < OrarioFine),
    PRIMARY KEY (Giorno, OrarioInizio, OrarioFine, Personale),
    FOREIGN KEY (Ospedale) REFERENCES OSPEDALE(Codice),
    FOREIGN KEY (Personale) REFERENCES PERSONALE(CodiceFiscale)
);

INSERT INTO TURNOPRONTOSOCCORSO (Giorno, OrarioInizio, OrarioFine, Personale, Ospedale) VALUES
('Luendì', '08:00:00', '14:00:00', 'PRSNL0046CHAR004', 'OSP001'),
('Luendì', '14:00:00', '20:00:00', 'PRSNL0086CHAR008', 'OSP001'),

('Martedì', '08:00:00', '14:00:00', 'PRSNL0476CHAR047', 'OSP002'),
('Martedì', '14:00:00', '20:00:00', 'PRSNL0516CHAR051', 'OSP002'),

('Sabato', '08:00:00', '14:00:00', 'PRSNL1006CHAR100', 'OSP003'),
('Sabato', '14:00:00', '20:00:00', 'PRSNL1046CHAR104', 'OSP003');




CREATE TABLE SOSTITUZIONE(
    DataInizio DATE,
    Primario CHAR(16),
    VicePrimario CHAR(16),
    DataFine DATE,

    CHECK (DataInizio <= DataFine),
    CHECK (DataInizio <= CURRENT_DATE),
    CHECK (DataFine <= CURRENT_DATE),
    CHECK (Primario != VicePrimario),

    PRIMARY KEY (DataInizio, Primario, VicePrimario),
    FOREIGN KEY (Primario) REFERENCES PERSONALE(CodiceFiscale),
    FOREIGN KEY (VicePrimario) REFERENCES PERSONALE(CodiceFiscale)
);

INSERT INTO SOSTITUZIONE (DataInizio, Primario, VicePrimario, DataFine) VALUES
('2024-07-01', 'PRSNL0106CHAR010', 'PRSNL0116CHAR011', '2024-07-15'),

('2024-07-05', 'PRSNL0356CHAR035', 'PRSNL0386CHAR038', '2024-08-01'),

('2024-07-15', 'PRSNL0616CHAR061', 'PRSNL0626CHAR062', '2024-08-11'),

('2024-07-20', 'PRSNL0816CHAR081', 'PRSNL0826CHAR082', '2024-08-15');





CREATE TABLE PRIMARIOHASPECIALIZZAZIONE(
    Primario CHAR(16),
    Specializzazione VARCHAR(255),
    PRIMARY KEY (Primario, Specializzazione),
    FOREIGN KEY (Primario) REFERENCES PERSONALE(CodiceFiscale),
    FOREIGN KEY (Specializzazione) REFERENCES SPECIALIZZAZIONE(Nome)
);

INSERT INTO PRIMARIOHASPECIALIZZAZIONE (Primario, Specializzazione) VALUES
/*OSP: OSP001*/
('PRSNL0016CHAR001', 'Cardiologia'),
('PRSNL0106CHAR010', 'Diagnostica'),
('PRSNL0186CHAR018', 'Ortopedia'),

/*OSP: OSP002*/
('PRSNL0276CHAR027', 'Oftalmologia'),
('PRSNL0356CHAR035', 'Cardiologia'),
('PRSNL0456CHAR045', 'Chirurgia'),
('PRSNL0536CHAR053', 'Neurologia'),
('PRSNL0616CHAR061', 'Ematologia'),
('PRSNL0706CHAR070', 'Radiologia'),
('PRSNL0816CHAR081', 'Diagnostica'),
('PRSNL0896CHAR089', 'Oncologia'),

/*OSP: OSP003*/
('PRSNL0976CHAR097', 'Pediatria'),
('PRSNL1066CHAR106', 'Ginecologia'),
('PRSNL1146CHAR114', 'Ortopedia'),
('PRSNL1226CHAR122', 'Dermatologia'),

/*OSP: OSP004*/
('PRSNL1306CHAR130', 'Neurologia'),
('PRSNL1386CHAR138', 'Radiologia');






CREATE TABLE AVVERTENZE(
    Descrizione VARCHAR(255) PRIMARY KEY
);

INSERT INTO AVVERTENZE (Descrizione) VALUES
('A digiuno da 12 ore prima'),
('Portare documentazione medica precedente'),
('Non fumare nelle 12 ore precedenti'),
('Non bere alcolici 24 ore prima'),
('Indossare abiti comodi e senza parti metalliche');


CREATE TABLE ESAMEHAAVVERTENZA(
    Esame CHAR(9),
    Avvertenza VARCHAR(255),
    PRIMARY KEY (Esame, Avvertenza),
    FOREIGN KEY (Esame) REFERENCES ESAME(Codice),
    FOREIGN KEY (Avvertenza) REFERENCES AVVERTENZE(Descrizione)
);

INSERT INTO ESAMEHAAVVERTENZA (Esame, Avvertenza) VALUES
('ESM001', 'A digiuno da 12 ore prima'),
('ESM002', 'Portare documentazione medica precedente'),
('ESM002', 'Indossare abiti comodi e senza parti metalliche'),
('ESM003', 'Non bere alcolici 24 ore prima'),
('ESM004', 'Non fumare nelle 12 ore precedenti');



CREATE TABLE CONVENZIONEOSPEDALEAMBULATORIO(
    Ospedale CHAR(20),
    AmbulatorioEsterno CHAR(9),
    PRIMARY KEY (Ospedale, AmbulatorioEsterno),
    FOREIGN KEY (Ospedale) REFERENCES OSPEDALE(Codice),
    FOREIGN KEY (AmbulatorioEsterno) REFERENCES AMBULATORIOESTERNO(Telefono)
);
INSERT INTO CONVENZIONEOSPEDALEAMBULATORIO (Ospedale, AmbulatorioEsterno) VALUES
('OSP001', '028645231'),
('OSP002', '081562341'),
('OSP003', '050203899');



CREATE TABLE UTENTE(
    username VARCHAR(255) PRIMARY KEY,
    password VARCHAR(255) NOT NULL,
    ruolo VARCHAR(255) NOT NULL,
    CHECK (ruolo = 'Medico Interno' OR ruolo = 'Medico Esterno' OR ruolo = 'Infermiere' OR ruolo = 'Paziente' OR ruolo = 'Amministrativo'),
    UNIQUE(username, password)
);

INSERT INTO UTENTE (Username, Password, Ruolo) VALUES
-- Inserimento dei dati nella tabella Utente per i Pazienti
('TS001000000000000001', '1234', 'Paziente'),
('TS001000000000000002', '1234', 'Paziente'),
('TS001000000000000003', '1234', 'Paziente'),
('TS001000000000000004', '1234', 'Paziente'),
('TS001000000000000005', '1234', 'Paziente'),
('TS001000000000000006', '1234', 'Paziente'),
('TS001000000000000007', '1234', 'Paziente'),
('TS001000000000000008', '1234', 'Paziente'),
('TS001000000000000009', '1234', 'Paziente'),
('TS001000000000000010', '1234', 'Paziente'),

-- Inserimento dei dati nella tabella Utente per i Medici Esterni
('MEDEST0000000001', '1234', 'Medico Esterno'),
('MEDEST0000000002', '1234', 'Medico Esterno'),
('MEDEST0000000003', '1234', 'Medico Esterno'),
('MEDEST0000000004', '1234', 'Medico Esterno'),
('MEDEST0000000005', '1234', 'Medico Esterno'),
('MEDEST0000000006', '1234', 'Medico Esterno'),
('MEDEST0000000007', '1234', 'Medico Esterno'),
('MEDEST0000000008', '1234', 'Medico Esterno'),

-- Inserimento dei dati nella tabella Utente per il Personale
('PRSNL0016CHAR001', '1234', 'Medico Interno'),
('PRSNL0026CHAR002', '1234', 'Medico Interno'),
('PRSNL0036CHAR003', '1234', 'Medico Interno'),
('PRSNL0046CHAR004', '1234', 'Medico Interno'),
('PRSNL0056CHAR005', '1234', 'Medico Interno'),
('PRSNL0066CHAR006', '1234', 'Medico Interno'),
('PRSNL0076CHAR007', '1234', 'Infermiere'),
('PRSNL0086CHAR008', '1234', 'Infermiere'),
('PRSNL0096CHAR009', '1234', 'Amministrativo'),

('PRSNL0106CHAR010', '1234', 'Medico Interno'),
('PRSNL0116CHAR011', '1234', 'Medico Interno'),
('PRSNL0126CHAR012', '1234', 'Medico Interno'),
('PRSNL0136CHAR013', '1234', 'Medico Interno'),
('PRSNL0146CHAR014', '1234', 'Medico Interno'),
('PRSNL0156CHAR015', '1234', 'Infermiere'),
('PRSNL0166CHAR016', '1234', 'Infermiere'),
('PRSNL0176CHAR017', '1234', 'Amministrativo'),

('PRSNL0186CHAR018', '1234', 'Medico Interno'),
('PRSNL0196CHAR019', '1234', 'Medico Interno'),
('PRSNL0206CHAR020', '1234', 'Medico Interno'),
('PRSNL0216CHAR021', '1234', 'Medico Interno'),
('PRSNL0226CHAR022', '1234', 'Medico Interno'),
('PRSNL0236CHAR023', '1234', 'Medico Interno'),
('PRSNL0246CHAR024', '1234', 'Infermiere'),
('PRSNL0256CHAR025', '1234', 'Infermiere'),
('PRSNL0266CHAR026', '1234', 'Amministrativo'),

('PRSNL0276CHAR027', '1234', 'Medico Interno'),
('PRSNL0286CHAR028', '1234', 'Medico Interno'),
('PRSNL0296CHAR029', '1234', 'Medico Interno'),
('PRSNL0306CHAR030', '1234', 'Medico Interno'),
('PRSNL0316CHAR031', '1234', 'Medico Interno'),
('PRSNL0326CHAR032', '1234', 'Infermiere'),
('PRSNL0336CHAR033', '1234', 'Infermiere'),
('PRSNL0346CHAR034', '1234', 'Amministrativo'),

('PRSNL0356CHAR035', '1234', 'Medico Interno'),
('PRSNL0366CHAR036', '1234', 'Medico Interno'),
('PRSNL0376CHAR037', '1234', 'Medico Interno'),
('PRSNL0386CHAR038', '1234', 'Medico Interno'),
('PRSNL0396CHAR039', '1234', 'Medico Interno'),
('PRSNL0406CHAR040', '1234', 'Medico Interno'),
('PRSNL0416CHAR041', '1234', 'Medico Interno'),
('PRSNL0426CHAR042', '1234', 'Infermiere'),
('PRSNL0436CHAR043', '1234', 'Infermiere'),
('PRSNL0446CHAR044', '1234', 'Amministrativo'),

('PRSNL0456CHAR045', '1234', 'Medico Interno'),
('PRSNL0466CHAR046', '1234', 'Medico Interno'),
('PRSNL0476CHAR047', '1234', 'Medico Interno'),
('PRSNL0486CHAR048', '1234', 'Medico Interno'),
('PRSNL0496CHAR049', '1234', 'Medico Interno'),
('PRSNL0506CHAR050', '1234', 'Infermiere'),
('PRSNL0516CHAR051', '1234', 'Infermiere'),
('PRSNL0526CHAR052', '1234', 'Amministrativo'),

('PRSNL0536CHAR053', '1234', 'Medico Interno'),
('PRSNL0546CHAR054', '1234', 'Medico Interno'),
('PRSNL0556CHAR055', '1234', 'Medico Interno'),
('PRSNL0566CHAR056', '1234', 'Medico Interno'),
('PRSNL0576CHAR057', '1234', 'Medico Interno'),
('PRSNL0586CHAR058', '1234', 'Infermiere'),
('PRSNL0596CHAR059', '1234', 'Infermiere'),
('PRSNL0606CHAR060', '1234', 'Amministrativo'),

('PRSNL0616CHAR061', '1234', 'Medico Interno'),
('PRSNL0626CHAR062', '1234', 'Medico Interno'),
('PRSNL0636CHAR063', '1234', 'Medico Interno'),
('PRSNL0646CHAR064', '1234', 'Medico Interno'),
('PRSNL0656CHAR065', '1234', 'Medico Interno'),
('PRSNL0666CHAR066', '1234', 'Medico Interno'),
('PRSNL0676CHAR067', '1234', 'Infermiere'),
('PRSNL0686CHAR068', '1234', 'Infermiere'),
('PRSNL0696CHAR069', '1234', 'Amministrativo'),

('PRSNL0706CHAR070', '1234', 'Medico Interno'),
('PRSNL0716CHAR071', '1234', 'Medico Interno'),
('PRSNL0726CHAR072', '1234', 'Medico Interno'),
('PRSNL0736CHAR073', '1234', 'Medico Interno'),
('PRSNL0746CHAR074', '1234', 'Medico Interno'),
('PRSNL0756CHAR075', '1234', 'Medico Interno'),
('PRSNL0766CHAR076', '1234', 'Medico Interno'),
('PRSNL0776CHAR077', '1234', 'Medico Interno'),
('PRSNL0786CHAR078', '1234', 'Infermiere'),
('PRSNL0796CHAR079', '1234', 'Infermiere'),
('PRSNL0806CHAR080', '1234', 'Amministrativo'),

('PRSNL0816CHAR081', '1234', 'Medico Interno'),
('PRSNL0826CHAR082', '1234', 'Medico Interno'),
('PRSNL0836CHAR083', '1234', 'Medico Interno'),
('PRSNL0846CHAR084', '1234', 'Medico Interno'),
('PRSNL0856CHAR085', '1234', 'Medico Interno'),
('PRSNL0866CHAR086', '1234', 'Infermiere'),
('PRSNL0876CHAR087', '1234', 'Infermiere'),
('PRSNL0886CHAR088', '1234', 'Amministrativo'),

('PRSNL0896CHAR089', '1234', 'Medico Interno'),
('PRSNL0906CHAR090', '1234', 'Medico Interno'),
('PRSNL0916CHAR091', '1234', 'Medico Interno'),
('PRSNL0926CHAR092', '1234', 'Medico Interno'),
('PRSNL0936CHAR093', '1234', 'Medico Interno'),
('PRSNL0946CHAR094', '1234', 'Infermiere'),
('PRSNL0956CHAR095', '1234', 'Infermiere'),
('PRSNL0966CHAR096', '1234', 'Amministrativo'),

('PRSNL0976CHAR097', '1234', 'Medico Interno'),
('PRSNL0986CHAR098', '1234', 'Medico Interno'),
('PRSNL0996CHAR099', '1234', 'Medico Interno'),
('PRSNL1006CHAR100', '1234', 'Medico Interno'),
('PRSNL1016CHAR101', '1234', 'Medico Interno'),
('PRSNL1026CHAR102', '1234', 'Medico Interno'),
('PRSNL1036CHAR103', '1234', 'Infermiere'),
('PRSNL1046CHAR104', '1234', 'Infermiere'),
('PRSNL1056CHAR105', '1234', 'Amministrativo'),

('PRSNL1066CHAR106', '1234', 'Medico Interno'),
('PRSNL1076CHAR107', '1234', 'Medico Interno'),
('PRSNL1086CHAR108', '1234', 'Medico Interno'),
('PRSNL1096CHAR109', '1234', 'Medico Interno'),
('PRSNL1106CHAR110', '1234', 'Medico Interno'),
('PRSNL1116CHAR111', '1234', 'Infermiere'),
('PRSNL1126CHAR112', '1234', 'Infermiere'),
('PRSNL1136CHAR113', '1234', 'Amministrativo'),

('PRSNL1146CHAR114', '1234', 'Medico Interno'),
('PRSNL1156CHAR115', '1234', 'Medico Interno'),
('PRSNL1166CHAR116', '1234', 'Medico Interno'),
('PRSNL1176CHAR117', '1234', 'Medico Interno'),
('PRSNL1186CHAR118', '1234', 'Medico Interno'),
('PRSNL1196CHAR119', '1234', 'Infermiere'),
('PRSNL1206CHAR120', '1234', 'Infermiere'),
('PRSNL1216CHAR121', '1234', 'Amministrativo'),

('PRSNL1226CHAR122', '1234', 'Medico Interno'),
('PRSNL1236CHAR123', '1234', 'Medico Interno'),
('PRSNL1246CHAR124', '1234', 'Medico Interno'),
('PRSNL1256CHAR125', '1234', 'Medico Interno'),
('PRSNL1266CHAR126', '1234', 'Medico Interno'),
('PRSNL1276CHAR127', '1234', 'Infermiere'),
('PRSNL1286CHAR128', '1234', 'Infermiere'),
('PRSNL1296CHAR129', '1234', 'Amministrativo'),

('PRSNL1306CHAR130', '1234', 'Medico Interno'),
('PRSNL1316CHAR131', '1234', 'Medico Interno'),
('PRSNL1326CHAR132', '1234', 'Medico Interno'),
('PRSNL1336CHAR133', '1234', 'Medico Interno'),
('PRSNL1346CHAR134', '1234', 'Medico Interno'),
('PRSNL1356CHAR135', '1234', 'Infermiere'),
('PRSNL1366CHAR136', '1234', 'Infermiere'),
('PRSNL1376CHAR137', '1234', 'Amministrativo'),

('PRSNL1386CHAR138', '1234', 'Medico Interno'),
('PRSNL1396CHAR139', '1234', 'Medico Interno'),
('PRSNL1406CHAR140', '1234', 'Medico Interno'),
('PRSNL1416CHAR141', '1234', 'Medico Interno'),
('PRSNL1426CHAR142', '1234', 'Medico Interno'),
('PRSNL1436CHAR143', '1234', 'Infermiere'),
('PRSNL1446CHAR144', '1234', 'Infermiere'),
('PRSNL1456CHAR145', '1234', 'Amministrativo');

