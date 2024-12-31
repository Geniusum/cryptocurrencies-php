-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Hôte : localhost
-- Généré le : mar. 31 déc. 2024 à 16:25
-- Version du serveur : 10.4.27-MariaDB
-- Version de PHP : 7.4.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `cryptocurrencies`
--

-- --------------------------------------------------------

--
-- Structure de la table `bitcoin`
--

CREATE TABLE `bitcoin` (
  `id` int(11) NOT NULL,
  `rank` int(11) NOT NULL,
  `symbol` text NOT NULL,
  `name` text NOT NULL,
  `supply` double NOT NULL,
  `maxSupply` double NOT NULL,
  `marketCapUsd` double NOT NULL,
  `volumeUsd24Hr` double NOT NULL,
  `priceUsd` double NOT NULL,
  `changePercent24Hr` double NOT NULL,
  `vwap24hr` double NOT NULL,
  `explorer` text NOT NULL,
  `timestamp` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `bitcoin-cash`
--

CREATE TABLE `bitcoin-cash` (
  `id` int(11) NOT NULL,
  `rank` int(11) NOT NULL,
  `symbol` text NOT NULL,
  `name` text NOT NULL,
  `supply` double NOT NULL,
  `maxSupply` double NOT NULL,
  `marketCapUsd` double NOT NULL,
  `volumeUsd24Hr` double NOT NULL,
  `priceUsd` double NOT NULL,
  `changePercent24Hr` double NOT NULL,
  `vwap24hr` double NOT NULL,
  `explorer` text NOT NULL,
  `timestamp` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `cardano`
--

CREATE TABLE `cardano` (
  `id` int(11) NOT NULL,
  `rank` int(11) NOT NULL,
  `symbol` text NOT NULL,
  `name` text NOT NULL,
  `supply` double NOT NULL,
  `maxSupply` double NOT NULL,
  `marketCapUsd` double NOT NULL,
  `volumeUsd24Hr` double NOT NULL,
  `priceUsd` double NOT NULL,
  `changePercent24Hr` double NOT NULL,
  `vwap24hr` double NOT NULL,
  `explorer` text NOT NULL,
  `timestamp` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `eos`
--

CREATE TABLE `eos` (
  `id` int(11) NOT NULL,
  `rank` int(11) NOT NULL,
  `symbol` text NOT NULL,
  `name` text NOT NULL,
  `supply` double NOT NULL,
  `maxSupply` double NOT NULL,
  `marketCapUsd` double NOT NULL,
  `volumeUsd24Hr` double NOT NULL,
  `priceUsd` double NOT NULL,
  `changePercent24Hr` double NOT NULL,
  `vwap24hr` double NOT NULL,
  `explorer` text NOT NULL,
  `timestamp` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `ethereum`
--

CREATE TABLE `ethereum` (
  `id` int(11) NOT NULL,
  `rank` int(11) NOT NULL,
  `symbol` text NOT NULL,
  `name` text NOT NULL,
  `supply` double NOT NULL,
  `maxSupply` double NOT NULL,
  `marketCapUsd` double NOT NULL,
  `volumeUsd24Hr` double NOT NULL,
  `priceUsd` double NOT NULL,
  `changePercent24Hr` double NOT NULL,
  `vwap24hr` double NOT NULL,
  `explorer` text NOT NULL,
  `timestamp` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `litecoin`
--

CREATE TABLE `litecoin` (
  `id` int(11) NOT NULL,
  `rank` int(11) NOT NULL,
  `symbol` text NOT NULL,
  `name` text NOT NULL,
  `supply` double NOT NULL,
  `maxSupply` double NOT NULL,
  `marketCapUsd` double NOT NULL,
  `volumeUsd24Hr` double NOT NULL,
  `priceUsd` double NOT NULL,
  `changePercent24Hr` double NOT NULL,
  `vwap24hr` double NOT NULL,
  `explorer` text NOT NULL,
  `timestamp` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `stellar`
--

CREATE TABLE `stellar` (
  `id` int(11) NOT NULL,
  `rank` int(11) NOT NULL,
  `symbol` text NOT NULL,
  `name` text NOT NULL,
  `supply` double NOT NULL,
  `maxSupply` double NOT NULL,
  `marketCapUsd` double NOT NULL,
  `volumeUsd24Hr` double NOT NULL,
  `priceUsd` double NOT NULL,
  `changePercent24Hr` double NOT NULL,
  `vwap24hr` double NOT NULL,
  `explorer` text NOT NULL,
  `timestamp` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `bitcoin`
--
ALTER TABLE `bitcoin`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `bitcoin-cash`
--
ALTER TABLE `bitcoin-cash`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `cardano`
--
ALTER TABLE `cardano`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `eos`
--
ALTER TABLE `eos`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `ethereum`
--
ALTER TABLE `ethereum`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `litecoin`
--
ALTER TABLE `litecoin`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `stellar`
--
ALTER TABLE `stellar`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `bitcoin`
--
ALTER TABLE `bitcoin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT pour la table `bitcoin-cash`
--
ALTER TABLE `bitcoin-cash`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT pour la table `cardano`
--
ALTER TABLE `cardano`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT pour la table `eos`
--
ALTER TABLE `eos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT pour la table `ethereum`
--
ALTER TABLE `ethereum`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT pour la table `litecoin`
--
ALTER TABLE `litecoin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT pour la table `stellar`
--
ALTER TABLE `stellar`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
