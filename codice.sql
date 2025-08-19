CREATE TABLE OSPEDALE(
    Codice CHAR(20) PRIMARY KEY,
    Nome VARCHAR(50) NOT NULL,
    Indirizzo VARCHAR(255) NOT NULL
);

CREATE TABLE Reparto (
    Telefono CHAR(20) PRIMARY KEY,
    Nome VARCHAR(50) NOT NULL,   
    Ospedale CHAR(20) NOT NULL,
    Direttore CHAR(16),
    UNIQUE(Nome, Ospedale),
    FOREIGN KEY (Ospedale) REFERENCES Ospedale(Codice)
);

CREATE TABLE STANZA (
    NumeroStanza INTEGER CHECK (NumeroStanza > 0),
    Reparto CHAR(20),
    Piano INTEGER NOT NULL CHECK (Piano > 0),
    Tipologia VARCHAR(255),
    CHECK(Tipologia = 'Ambulatorio' OR Tipologia = 'Sala operatoria' OR Tipologia = 'Ricovero'),
    PRIMARY KEY (NumeroStanza, Reparto),
    FOREIGN KEY (Reparto) REFERENCES Reparto(Telefono)
);

CREATE TABLE ORARIO(
    Apertura TIME,
    Chiusura TIME,
    Giorno VARCHAR(9),
    CHECK(Apertura < Chiusura),
    PRIMARY KEY (Apertura, Chiusura, Giorno)
);

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

CREATE TABLE AMBULATORIOESTERNO(
    Telefono CHAR(13) PRIMARY KEY,
    Indirizzo VARCHAR(255) NOT NULL
);

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

CREATE TABLE SPECIALIZZAZIONE(
    Nome VARCHAR(255) PRIMARY KEY
);

CREATE TABLE ESAME(
    Codice CHAR(9) PRIMARY KEY,
    Descrizione VARCHAR(255),
    CostoPubblico DECIMAL(10,2) NOT NULL CHECK (CostoPubblico > 0),
    CostoPrivato DECIMAL(10,2) NOT NULL CHECK (CostoPrivato > 0),
    Specializzazione VARCHAR(255),
    FOREIGN KEY (Specializzazione) REFERENCES SPECIALIZZAZIONE(Nome)
);

CREATE TABLE PATOLOGIA(
    Nome VARCHAR(255) PRIMARY KEY
);

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

CREATE TABLE PAZIENTEHAPATOLOGIA(
    NumTesseraSanitaria CHAR(20),
    Patologia VARCHAR(255),
    PRIMARY KEY (NumTesseraSanitaria, Patologia),
    FOREIGN KEY (NumTesseraSanitaria) REFERENCES PAZIENTE(NumTesseraSanitaria),
    FOREIGN KEY (Patologia) REFERENCES PATOLOGIA(Nome)
);

CREATE TABLE LETTO(
    Stanza INTEGER,
    Reparto CHAR(20),
    NumeroLetto INTEGER CHECK (NumeroLetto > 0),
    PRIMARY KEY (Stanza, Reparto, NumeroLetto),
    FOREIGN KEY (Stanza, Reparto) REFERENCES STANZA(NumeroStanza, Reparto)
);

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
 
CREATE TABLE MEDICOESTERNO(
    CodiceFiscale CHAR(16) PRIMARY KEY
);

CREATE TABLE MEDICOESTERNOHASPECIALIZZAZIONE(
    MedicoEsterno CHAR(16),
    Specializzazione VARCHAR(255),
    PRIMARY KEY (MedicoEsterno, Specializzazione),
    FOREIGN KEY (MedicoEsterno) REFERENCES MEDICOESTERNO(CodiceFiscale),
    FOREIGN KEY (Specializzazione) REFERENCES SPECIALIZZAZIONE(Nome)
);

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

CREATE TABLE RICOVEROPATOLOGIA(
    Ricovero DATE,
    Patologia VARCHAR(255),
    Paziente CHAR(20),
    PRIMARY KEY (Ricovero, Patologia, Paziente),
    FOREIGN KEY (Ricovero, Paziente) REFERENCES RICOVERO(DataInizio, Paziente),
    FOREIGN KEY (Patologia) REFERENCES PATOLOGIA(Nome),
    FOREIGN KEY (Paziente) REFERENCES PAZIENTE(NumTesseraSanitaria)
);

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

CREATE TABLE PRIMARIOHASPECIALIZZAZIONE(
    Primario CHAR(16),
    Specializzazione VARCHAR(255),
    PRIMARY KEY (Primario, Specializzazione),
    FOREIGN KEY (Primario) REFERENCES PERSONALE(CodiceFiscale),
    FOREIGN KEY (Specializzazione) REFERENCES SPECIALIZZAZIONE(Nome)
);

CREATE TABLE AVVERTENZE(
    Descrizione VARCHAR(255) PRIMARY KEY
);

CREATE TABLE ESAMEHAAVVERTENZA(
    Esame CHAR(9),
    Avvertenza VARCHAR(255),
    PRIMARY KEY (Esame, Avvertenza),
    FOREIGN KEY (Esame) REFERENCES ESAME(Codice),
    FOREIGN KEY (Avvertenza) REFERENCES AVVERTENZE(Descrizione)
);

CREATE TABLE CONVENZIONEOSPEDALEAMBULATORIO(
    Ospedale CHAR(20),
    AmbulatorioEsterno CHAR(9),
    PRIMARY KEY (Ospedale, AmbulatorioEsterno),
    FOREIGN KEY (Ospedale) REFERENCES OSPEDALE(Codice),
    FOREIGN KEY (AmbulatorioEsterno) REFERENCES AMBULATORIOESTERNO(Telefono)
);

CREATE TABLE UTENTE(
    username VARCHAR(255) PRIMARY KEY,
    password VARCHAR(255) NOT NULL,
    ruolo VARCHAR(255) NOT NULL,
    CHECK (ruolo = 'Medico Interno' OR ruolo = 'Medico Esterno' OR ruolo = 'Infermiere' OR ruolo = 'Paziente' OR ruolo = 'Amministrativo'),
    UNIQUE(username, password)
);