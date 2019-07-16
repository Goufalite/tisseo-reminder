# Tisseo reminder
Utilisation de l'API Tisseo pour créer une page web et un script pour obtenir un rappel du prochain bus ainsi que le temps pour aller à l'arrêt.

[La documentation officielle est disponible ici](https://data.toulouse-metropole.fr/explore/dataset/api-temps-reel-tisseo/table/)

Si vous devez forker ce projet, pensez à mettre un message citant la source de l'API comme stipulé dans la documentation et les conditions d'utilisation de l'API.

## Prérequis important
Il faut d'abord demander [une clef d'API](mailto:opendata(at)tisseo.fr). Ensuite mettre à jour la base sqlite tisseo.db3 pour inclure la clef ainsi : 

    UPDATE Parameter SET stringValue = "<clef>" WHERE nameParam = "key";
	
## Schéma de la base

### Table Parameter
Table de paramétrage stockant des clefs et une valeur numérique ou en chaîne de caractères
- nameParam : nom du paramètre
- stringValue : valeur du paramètre de type string
- numericValue : valeur du paramètre de type number

### Table Location
Table contenant les scénarii avec les arrêts filtrés
- idLocation : identifiant technique
- url : URL contenant l'appel à l'API. Vous pouvez appeler cette URL en passant le paramètre `&key=<clef>`
- offset : décalage le temps d'accès à l'arrêt
- ordre : ordre d'affichage pour la page web
- label : libellé du scénario
- coords : coordonnées géographiques pour déterminer l'arrêt en fonction de notre position

## Fichiers

### Page web
- ajax_quickreminder.php : mise à jour de la localisation via le GPS
- quickreminder.php : page maîtresse permettant la géolocalisation. **Attention, nécessite du https**
- reminder.php : page web affichant les différents réglages

### Script de rappel
- notif.php : script de notification à appeler pour envoyer une notification via pushbullet par exemple

### Générateur d'URL
- stopsearch.php : page d'accueil pour la recherche des arrêts
- val_stopsearch.php : réponse au script pour rechercher les éléments

### Divers
- .htaccess : masquage des fichiers par défaut
- robots.txt : éloignement des bots de recherche (google, bing,...)
- fonctions/config.inc.php : configuration des applications, en l'occurence le chemin vers la base de données sqlite3
- fonctions/connector.php : mappeur de connection vers sqlite3