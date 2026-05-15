# API Messages

Cette API permet de gérer les messages provenant des formulaires de contact et de rendez-vous.

## Endpoints

### Créer un message
- **URL**: `POST /api/messages`
- **Description**: Crée un nouveau message.
- **Body** (JSON):
  ```json
  {
    "type": "contact",
    "email": "user@example.com",
    "message": "Contenu du message", 
    "name": "Nom complet",
    "subject": "Sujet",
    "firstName": "Prénom",
    "lastName": "Nom",
    "phone": "Téléphone",
    "date": "2023-12-31" 
  }
  ```
- **Réponse**: `201 Created` avec l'ID du message.

### Lister tous les messages
- **URL**: `GET /api/messages`
- **Description**: Récupère tous les messages.
- **Réponse**: `200 OK` avec la liste des messages.

### Récupérer un message par ID
- **URL**: `GET /api/messages/{id}`
- **Description**: Récupère un message spécifique.
- **Réponse**: `200 OK` avec les détails du message ou `404 Not Found`.

## Exemples d'utilisation

### Formulaire de contact
```json
{
  "type": "contact",
  "name": "Jean Dupont",
  "email": "jean@example.com",
  "subject": "Question générale",
  "message": "Bonjour, j'ai une question..."
}
```

### Formulaire de rendez-vous
```json
{
  "type": "appointment",
  "firstName": "Jean",
  "lastName": "Dupont",
  "email": "jean@example.com",
  "phone": "+223123456789",
  "date": "2023-12-31",
  "message": "Rendez-vous pour une consultation"
}
```

## Configuration e-mail (notifications automatiques)

Les soumissions `contact` et `appointment` declenchent un e-mail de notification.

### Variables d'environnement

Definir dans `.env.local` (recommande) :

```dotenv
MAILER_DSN=gmail+smtp://VOTRE_ADRESSE_GMAIL%40gmail.com:VOTRE_APP_PASSWORD@smtp.gmail.com
MAIL_SENDER_ADDRESS=votre.adresse@gmail.com
MAIL_SENDER_NAME="CDOS COM"
MAIL_NOTIFICATION_RECIPIENTS=dest1@gmail.com,dest2@gmail.com
```

### Comportement

- `MAIL_NOTIFICATION_RECIPIENTS` accepte plusieurs adresses separees par virgules.
- L'en-tete `From` utilise `MAIL_SENDER_ADDRESS` (adresse Gmail authentifiee conseillee).
- L'en-tete `Reply-To` est automatiquement defini avec l'e-mail du visiteur.
- En cas d'echec SMTP, le statut `statutEnvoiMail` passe a `failed` et peut etre relance via l'API de retry.

### Gmail

Pour Gmail SMTP, activer la double authentification puis creer un mot de passe d'application (App Password).