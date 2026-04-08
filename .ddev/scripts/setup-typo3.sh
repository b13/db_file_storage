#!/usr/bin/env bash
#
# Idempotent bootstrap for the TYPO3 dev installation under Build/.
# Run automatically as a DDEV post-start hook. Safe to re-run at any time.
#
# Steps (each guarded by a marker so repeated runs are no-ops):
#   1. composer install inside Build/
#   2. typo3 setup (non-interactive) against the DDEV database
#
# Environment inside the DDEV web container:
#   /var/www/html/           -> project root (the extension)
#   /var/www/html/Build/     -> full TYPO3 installation
#   db service:              host=db user=db pass=db dbname=db port=3306

set -euo pipefail

BUILD_DIR="/var/www/html/Build"
SETTINGS_FILE="${BUILD_DIR}/config/system/settings.php"

cd "${BUILD_DIR}"

if [ ! -f "vendor/autoload.php" ] || [ ! -x "vendor/bin/typo3" ]; then
    echo "[db_file_storage] Running composer install in Build/ ..."
    composer install --no-interaction --no-progress
else
    echo "[db_file_storage] composer dependencies already installed, skipping."
fi

if [ ! -f "${SETTINGS_FILE}" ]; then
    echo "[db_file_storage] Running 'typo3 setup' against DDEV database ..."
    vendor/bin/typo3 setup \
        --driver=mysqli \
        --host=db \
        --port=3306 \
        --dbname=db \
        --username=db \
        --password=db \
        --admin-username=admin \
        --admin-user-password='Password.1' \
        --admin-email=admin@example.com \
        --project-name='db_file_storage dev' \
        --server-type=other \
        --create-site='https://db-file-storage.ddev.site/' \
        --force \
        --no-interaction
    echo "[db_file_storage] TYPO3 is ready."
    echo "[db_file_storage] Backend:  https://db-file-storage.ddev.site/typo3"
    echo "[db_file_storage] Login:    admin / Password.1"
else
    echo "[db_file_storage] TYPO3 already set up (${SETTINGS_FILE} exists), skipping."
fi
