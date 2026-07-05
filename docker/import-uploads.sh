#!/bin/sh
# Importe les uploads dans le conteneur app (volume ../files/uploads).
# Usage: sh docker/import-uploads.sh /chemin/vers/uploads
set -e

SOURCE="${1:?Indiquez le dossier source, ex: ./uploads ou /tmp/uploads}"

COMPOSE_FILE="${COMPOSE_FILE:-compose.prod.yaml}"
CONTAINER=$(docker compose -f "$COMPOSE_FILE" ps -q app)

if [ -z "$CONTAINER" ]; then
	echo "Conteneur app introuvable. Le stack est-il démarré ?"
	exit 1
fi

echo "Copie de $SOURCE vers le conteneur $CONTAINER:/app/public/uploads/"
docker cp "$SOURCE/." "$CONTAINER:/app/public/uploads/"

echo "Permissions..."
docker exec "$CONTAINER" sh -c 'chown -R www-data:www-data /app/public/uploads && chmod -R u=rwX,g=rX,o=rX /app/public/uploads'

echo "Fichiers présents dans le volume :"
docker exec "$CONTAINER" find /app/public/uploads -type f | head -20
COUNT=$(docker exec "$CONTAINER" find /app/public/uploads -type f | wc -l)
echo "Total : $COUNT fichier(s)"
