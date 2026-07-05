#!/bin/sh
set -e

if [ "$1" = 'frankenphp' ] || [ "$1" = 'php' ] || [ "$1" = 'bin/console' ]; then
	if [ -n "$DATABASE_URL" ]; then
		echo 'Waiting for database to be ready...'
		ATTEMPTS_LEFT_TO_REACH_DATABASE=60
		until [ $ATTEMPTS_LEFT_TO_REACH_DATABASE -eq 0 ] || DATABASE_ERROR=$(php bin/console dbal:run-sql -q "SELECT 1" 2>&1); do
			sleep 1
			ATTEMPTS_LEFT_TO_REACH_DATABASE=$((ATTEMPTS_LEFT_TO_REACH_DATABASE - 1))
			echo "Still waiting for database... $ATTEMPTS_LEFT_TO_REACH_DATABASE attempts left."
		done

		if [ $ATTEMPTS_LEFT_TO_REACH_DATABASE -eq 0 ]; then
			echo 'The database is not up or not reachable:'
			echo "$DATABASE_ERROR"
			exit 1
		fi

		echo 'The database is now ready and reachable'

		if [ "$1" = 'frankenphp' ] && [ "$(find ./migrations -iname '*.php' -print -quit)" ]; then
			php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration
		fi
	fi

	if [ "$1" = 'frankenphp' ]; then
		php bin/console cache:clear --env=prod --no-warmup
		php bin/console cache:warmup --env=prod
		echo 'PHP app ready!'
	fi
fi

exec docker-php-entrypoint "$@"
