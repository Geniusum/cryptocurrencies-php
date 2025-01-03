# cryptocurrencies-php
Librairie PHP pour récupérer les cours des cryptomonnaies et les stocker dans une base de donnée.

---

Pour télécharger le code source :
```console
$ git clone https://github.com/Geniusum/cryptocurrencies-php.git
```

La librarie [[crypto.php|`crypto.php`]] contient les fonctions nécéssaire pour récupérer les données de l'API et les mettre dans la base de donnée.
Un exemple d'utilisation de la librairie est présent : [[index.php|`index.php`]].
Pour éviter d'utiliser l'API durant les tests, un exemple de réponse de l'API se trouve dans [[sample.json|`sample.json`]].
Un fichier `phpserver` est présent pour démarrer le serveur PHP.
Pour la connexion à la base de donnée, il faut changer les valeurs au début de [[crypto.php|`crypto.php`]].
La base de donnée se trouve dans [[cryptocurrencies.sql|`cryptocurrencies.sql`]]

---

Champs donnés par l'API :
- `id` identifiant de la cryptomonnaie.
- `rank` rang de la cryptomonnaie par rapport à la capitalisation boursière.
- `symbol` sombole de la cryptomonnaie
- `name` nom propre de la cryptomonnaie
- `supply` nombre de tokens circulant sur le marché de cette cryptomonnaie.
- `maxSupply` nombre de tokens disponible maximum sur le marché de cette cryptomonnaie (exemple: 21M de Bitcoins).
- `marketCapUsd` produit du nombre de tokens (`supply`) et du prix d'un token (`priceUsd`).
- `volumeUsd24Hr` **quantité de volume échangé en USD dans les dernière 24 heures.**
- `priceUsd` prix d'un token en USD.
- `changePercent24Hr` pourcentage de changement + direction dans les dernières 24 heures.
- `vwap24Hr` prix moyen dans les dernières 24 heures.

Structure d'une réponse de l'API :
```json
{
    "data": [
        { // Cryptomonnaies
            // Champs : id, rank, symbol, ...
        },
        // ...
    ],
    "timestamp": // timestamp pris durant la requête
}
```