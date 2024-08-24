# ██    ██ ██    ██ ███    ██ ██ ██████  ███████      ██████  ██████   ██████  ███████ ████████ ███████ ██████  
# ██    ██ ██    ██ ████   ██ ██ ██   ██ ██          ██    ██ ██   ██ ██    ██ ██         ██    ██      ██   ██ 
# ██    ██ ██    ██ ██ ██  ██ ██ ██   ██ █████       ██    ██ ██████  ██    ██ ███████    ██    █████   ██████  
# ██    ██ ██    ██ ██  ██ ██ ██ ██   ██ ██          ██    ██ ██   ██ ██    ██      ██    ██    ██      ██   ██ 
#  ██████   ██████  ██   ████ ██ ██████  ███████      ██████  ██   ██  ██████  ███████    ██    ███████ ██   ██ 

---

## Description

**Unique Code Generator** est un plugin WordPress pour WooCommerce qui génère des codes uniques pour les articles commandés. Ce plugin est parfait pour les promotions, les concours ou toute autre situation où vous avez besoin de fournir un code unique à vos clients après un achat.

### Fonctionnalités principales

- Génération de codes uniques pour chaque commande.
- Gestion des tags de produits pour ajouter des chances supplémentaires (ex. : "10 chances de plus").
- Sauvegarde des codes dans une table personnalisée dans la base de données.
- Envoi automatique des codes par e-mail aux clients après l'achat.
- Affichage des codes dans la page de commande du client.

## Installation

1. Téléchargez le plugin ou clonez ce dépôt dans le répertoire `wp-content/plugins/` de votre installation WordPress.
2. Activez le plugin via le menu `Plugins` dans WordPress.
3. Configurez vos paramètres si nécessaire.

## Utilisation

1. **Génération de codes** : Après chaque achat, le plugin génère automatiquement un code unique pour chaque produit acheté. Les produits avec le tag "10 chances de plus" génèrent des codes supplémentaires.
2. **Envoi d'e-mail** : Les codes générés sont envoyés automatiquement par e-mail au client après l'achat.
3. **Affichage des codes** : Les codes sont visibles par le client dans leur page de commande.

## Personnalisation

### Fichiers principaux :

- `class-unique-code-generator.php` : Le cœur du plugin qui gère la génération des codes et l'intégration avec WooCommerce.
- `class-unique-code-generator-email.php` : Gère l'envoi des e-mails aux clients avec les codes générés.

### Comment ajouter de nouvelles fonctionnalités ?

1. **Hooks WordPress** : Utilisez les hooks WordPress et WooCommerce pour ajouter ou modifier les fonctionnalités.
2. **Personnalisation des e-mails** : Modifiez le fichier `class-unique-code-generator-email.php` pour personnaliser le contenu des e-mails envoyés aux clients.

## Support

Pour toute question ou problème, merci de créer un ticket via la section [Issues](https://github.com/votre-repo/Unique-Code-Generator/issues) de ce dépôt GitHub.


## Licence

Ce projet est sous licence MIT - voir le fichier [LICENSE](LICENSE) pour plus de détails.

---