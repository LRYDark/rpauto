# RPauto

Plugin GLPI d'automatisation d'envoi de rapports RP par email. Il s'appuie sur le plugin `rp` pour générer les PDF, puis sur un cron GLPI pour traiter les tickets concernés selon des règles définies par entité.

Le plugin sert à envoyer des rapports de façon périodique et homogène sans intervention manuelle ticket par ticket.

## Ce que fait le plugin

- Crée des configurations (surveys) RPauto par entité.
- Définit le contenu du PDF à générer (tâches, suivis, images, description, temps de trajet).
- Définit le gabarit / mode d'envoi email.
- Exécute un cron (`RpautoMail`) qui génère et envoie automatiquement.

## Fonctionnement (parcours type)

1. Créer une configuration RPauto depuis l'écran du plugin.
2. Choisir l'entité cible et l'activation récursive si besoin.
3. Définir les options de contenu PDF (public/privé/images/description/temps de trajet).
4. Renseigner le destinataire (email alternatif si prévu par votre workflow).
5. Activer la configuration.
6. Laisser le cron GLPI `RpautoMail` traiter les tickets de la période.

## Configuration plugin (ce que chaque zone active)

### Configuration d'une survey RPauto

Champs fréquents:
- `name`: nom de la configuration (ex: "Envoi RP hebdo - Hotline")
- `entities_id`: entité concernée
- `is_recursive`: inclut les entités filles
- `gabarit`: modèle/format de notification ou d'envoi utilisé
- `is_active`: active réellement le traitement par le cron

### Options de contenu PDF

- `tasks_private`: inclure les tâches privées
- `tasks_img`: inclure les images des tâches
- `suivis_private`: inclure les suivis privés
- `suivis_img`: inclure les images des suivis
- `ticket_desc`: inclure la description ticket
- `route_time`: inclure le temps de trajet (si `rt` est disponible)

Ces options servent à produire un document adapté:
- version client (plus filtrée)
- version interne (plus complète)

### Destinataire

- `mail` (email alternatif): permet d'envoyer le rapport à une adresse différente du circuit standard si nécessaire.

## Prérequis

- Plugin `rp` obligatoire (RPauto s'appuie sur sa génération PDF)
- GLPI cron fonctionnel
- PHP compatible GLPI
- GLPI 11.x (selon version installée)

## Droits / profils

- L'écran d'administration RPauto est réservé aux profils ayant les droits du plugin.
- La gestion des configurations (surveys) suit les droits plugin `rpauto`.

## Tâches cron

Le plugin enregistre la tâche cron `RpautoMail`.

Rôle du cron:
- rechercher les tickets correspondant aux règles
- générer les PDF via `rp`
- préparer les envois / archives selon la configuration
- envoyer les emails

## Architecture (résumé court)

- `rpauto` stocke des configurations par entité.
- Un cron central relit ces configurations et déclenche la génération via `rp`.
- Le plugin isole les règles d'automatisation sans modifier le flux manuel du plugin `rp`.

## Vérifications rapides après mise à jour

- Créer une configuration test active.
- Lancer le cron GLPI (ou attendre l'exécution planifiée).
- Vérifier génération PDF + envoi email.
- Vérifier les logs GLPI/PHP en cas de ticket non traité.
