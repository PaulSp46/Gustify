-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Creato il: Mag 08, 2025 alle 23:26
-- Versione del server: 10.4.32-MariaDB
-- Versione PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `gustify`
--

-- --------------------------------------------------------

--
-- Struttura della tabella `frigo`
--

CREATE TABLE `frigo` (
  `idrelation` int(11) NOT NULL,
  `prodotto_idprodotto` int(11) NOT NULL,
  `utente_idutente` int(11) NOT NULL,
  `quantita` int(11) NOT NULL,
  `scadenza` date NOT NULL,
  `note` varchar(255) DEFAULT NULL,
  `data_creazione` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dump dei dati per la tabella `frigo`
--

INSERT INTO `frigo` (`idrelation`, `prodotto_idprodotto`, `utente_idutente`, `quantita`, `scadenza`, `note`, `data_creazione`) VALUES
(5, 2, 1, 2, '2025-05-15', 'Bianco intero', '2025-05-08 20:59:11'),
(6, 3, 1, 1, '2025-06-01', 'Senza glutine', '2025-05-08 20:59:11'),
(7, 4, 1, 6, '2025-05-10', 'Bio', '2025-05-08 20:59:11');

-- --------------------------------------------------------

--
-- Struttura della tabella `prodotto`
--

CREATE TABLE `prodotto` (
  `idprodotto` int(11) NOT NULL,
  `des` varchar(255) NOT NULL,
  `categoria` varchar(100) NOT NULL,
  `marca` varchar(100) DEFAULT NULL,
  `bar_code` varchar(13) NOT NULL,
  `created_by` int(11) DEFAULT NULL,
  `source` enum('system','qr','manual') NOT NULL DEFAULT 'system',
  `verified` tinyint(1) NOT NULL DEFAULT 0,
  `creation_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dump dei dati per la tabella `prodotto`
--

INSERT INTO `prodotto` (`idprodotto`, `des`, `categoria`, `marca`, `bar_code`, `created_by`, `source`, `verified`, `creation_date`) VALUES
(2, 'Yogurt bianco', 'dairy', NULL, '1234567890123', 1, 'qr', 1, '2025-05-08 20:47:19'),
(3, 'Pane integrale', 'bakery', NULL, '9781234567897', 1, 'qr', 1, '2025-05-08 20:47:19'),
(4, 'Mele Golden', 'fruit', NULL, '5000159484695', 1, 'qr', 1, '2025-05-08 20:47:19');

-- --------------------------------------------------------

--
-- Struttura della tabella `prodotto_personalizzato`
--

CREATE TABLE `prodotto_personalizzato` (
  `id` int(11) NOT NULL,
  `prodotto_idprodotto` int(11) NOT NULL,
  `utente_idutente` int(11) NOT NULL,
  `des` varchar(255) NOT NULL,
  `categoria` varchar(50) NOT NULL,
  `marca` varchar(100) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `utente`
--

CREATE TABLE `utente` (
  `idutente` int(11) NOT NULL,
  `email` varchar(150) NOT NULL,
  `pwd` varchar(100) NOT NULL,
  `nome` varchar(40) NOT NULL,
  `cognome` varchar(40) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dump dei dati per la tabella `utente`
--

INSERT INTO `utente` (`idutente`, `email`, `pwd`, `nome`, `cognome`) VALUES
(1, 'test@com', '$2y$10$ehFd2hULIszH7c0Uns9K0OKJvGDOK8lbKg4cblFaSsmbxz.re/7jK', 'test', 'testina');

--
-- Indici per le tabelle scaricate
--

--
-- Indici per le tabelle `frigo`
--
ALTER TABLE `frigo`
  ADD PRIMARY KEY (`idrelation`),
  ADD UNIQUE KEY `unique_frigo_user_product` (`utente_idutente`,`prodotto_idprodotto`),
  ADD KEY `fk_prodotto_has_utente_utente1_idx` (`utente_idutente`),
  ADD KEY `fk_prodotto_has_utente_prodotto_idx` (`prodotto_idprodotto`);

--
-- Indici per le tabelle `prodotto`
--
ALTER TABLE `prodotto`
  ADD PRIMARY KEY (`idprodotto`),
  ADD KEY `created_by` (`created_by`);

--
-- Indici per le tabelle `prodotto_personalizzato`
--
ALTER TABLE `prodotto_personalizzato`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `prodotto_idprodotto` (`prodotto_idprodotto`,`utente_idutente`),
  ADD KEY `utente_idutente` (`utente_idutente`);

--
-- Indici per le tabelle `utente`
--
ALTER TABLE `utente`
  ADD PRIMARY KEY (`idutente`),
  ADD UNIQUE KEY `email_UNIQUE` (`email`);

--
-- AUTO_INCREMENT per le tabelle scaricate
--

--
-- AUTO_INCREMENT per la tabella `frigo`
--
ALTER TABLE `frigo`
  MODIFY `idrelation` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT per la tabella `prodotto`
--
ALTER TABLE `prodotto`
  MODIFY `idprodotto` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT per la tabella `prodotto_personalizzato`
--
ALTER TABLE `prodotto_personalizzato`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT per la tabella `utente`
--
ALTER TABLE `utente`
  MODIFY `idutente` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Limiti per le tabelle scaricate
--

--
-- Limiti per la tabella `frigo`
--
ALTER TABLE `frigo`
  ADD CONSTRAINT `fk_prodotto_has_utente_prodotto` FOREIGN KEY (`prodotto_idprodotto`) REFERENCES `prodotto` (`idprodotto`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_prodotto_has_utente_utente1` FOREIGN KEY (`utente_idutente`) REFERENCES `utente` (`idutente`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Limiti per la tabella `prodotto`
--
ALTER TABLE `prodotto`
  ADD CONSTRAINT `prodotto_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `utente` (`idutente`);

--
-- Limiti per la tabella `prodotto_personalizzato`
--
ALTER TABLE `prodotto_personalizzato`
  ADD CONSTRAINT `prodotto_personalizzato_ibfk_1` FOREIGN KEY (`prodotto_idprodotto`) REFERENCES `prodotto` (`idprodotto`),
  ADD CONSTRAINT `prodotto_personalizzato_ibfk_2` FOREIGN KEY (`utente_idutente`) REFERENCES `utente` (`idutente`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
