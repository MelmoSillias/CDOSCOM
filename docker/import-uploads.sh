#!/bin/sh
# Importe les uploads dans le volume Dokploy (../files/uploads).
#
# Usage sur le serveur Dokploy :
#   sh docker/import-uploads.sh
#   sh docker/import-uploads.sh /var/www/cdoscom/public/uploads
#
set -e

SOURCE="${1:-/var/www/cdoscom/public/uploads}"
COMPOSE_FILE="${COMPOSE_FILE:-compose.prod.yaml}"

if [ ! -d "$SOURCE" ]; then
	echo "Dossier source introuvable : $SOURCE"
	exit 1
fi

FILE_COUNT=$(find "$SOURCE" -type f | wc -l)
if [ "$FILE_COUNT" -eq 0 ]; then
	echo "Aucun fichier dans $SOURCE"
	exit 1
fi

echo "Source : $SOURCE ($FILE_COUNT fichier(s))"

# Copie directe vers le bind mount Dokploy (méthode recommandée)
COMPOSE_DIR=$(dirname "$(readlink -f "$COMPOSE_FILE" 2>/dev/null || echo "$COMPOSE_FILE")")
UPLOADS_MOUNT="$COMPOSE_DIR/../files/uploads"

if [ -d "$UPLOADS_MOUNT" ] || mkdir -p "$UPLOADS_MOUNT" 2>/dev/null; then
	echo "Copie vers $UPLOADS_MOUNT"
	rsync -av --delete "$SOURCE/" "$UPLOADS_MOUNT/"
	chown -R 33:33 "$UPLOADS_MOUNT" 2>/dev/null || true
	chmod -R u=rwX,g=rX,o=rX "$UPLOADS_MOUNT"
else
	CONTAINER=$(docker compose -f "$COMPOSE_FILE" ps -q app)
	if [ -z "$CONTAINER" ]; then
		echo "Conteneur app introuvable et bind mount inaccessible."
		exit 1
	fi
	echo "Copie vers le conteneur $CONTAINER:/app/public/uploads/"
	docker cp "$SOURCE/." "$CONTAINER:/app/public/uploads/"
	docker exec "$CONTAINER" sh -c 'chown -R www-data:www-data /app/public/uploads && chmod -R u=rwX,g=rX,o=rX /app/public/uploads'
fi

CONTAINER=$(docker compose -f "$COMPOSE_FILE" ps -q app 2>/dev/null || true)
if [ -n "$CONTAINER" ]; then
	echo "Verification dans le conteneur :"
	docker exec "$CONTAINER" find /app/public/uploads -type f | head -20
	COUNT=$(docker exec "$CONTAINER" find /app/public/uploads -type f | wc -l)
	echo "Total dans le conteneur : $COUNT fichier(s)"
fi
