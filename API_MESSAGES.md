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