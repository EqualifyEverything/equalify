#!/bin/bash
set -e

# Export DB schema and Hasura metadata
# Usage: ./dump.sh staging    or    ./dump.sh production

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
ENV="${1:-staging}"

# Load environment-specific .env file
ENV_FILE="$SCRIPT_DIR/.env.$ENV"
if [ -f "$ENV_FILE" ]; then
    set -a
    source "$ENV_FILE"
    set +a
else
    echo "Error: $ENV_FILE not found"
    echo "Usage: ./dump.sh [staging|production]"
    exit 1
fi

# Validate required env vars
for var in DB_HOST DB_USER DB_NAME DB_PASSWORD; do
    if [ -z "${!var}" ]; then
        echo "Error: $var is not set in $ENV_FILE"
        exit 1
    fi
done

# Determine Hasura endpoint
if [ "$ENV" = "staging" ]; then
    HASURA_ENDPOINT="https://graphql-staging.equalifyapp.com"
else
    HASURA_ENDPOINT="https://graphql.equalifyapp.com"
fi

# Add Homebrew PostgreSQL to PATH if needed
if ! command -v pg_dump &> /dev/null; then
    PG_PATH=$(find /opt/homebrew/Cellar/postgresql* -name "pg_dump" 2>/dev/null | head -1)
    if [ -n "$PG_PATH" ]; then
        export PATH="$(dirname "$PG_PATH"):$PATH"
    else
        echo "Error: pg_dump not found. Install with: brew install postgresql"
        exit 1
    fi
fi

echo "Dumping $ENV..."

# Export DB schema (no data)
echo "Dumping database schema..."
PGPASSWORD="$DB_PASSWORD" pg_dump \
    --schema-only \
    --no-owner \
    --no-privileges \
    -h "$DB_HOST" \
    -U "$DB_USER" \
    "$DB_NAME" > "$SCRIPT_DIR/schema.sql"
echo "  -> db/schema.sql"

# Export Hasura metadata
echo "Exporting Hasura metadata..."
curl -s -X POST "$HASURA_ENDPOINT/v1/metadata" \
    -H "X-Hasura-Admin-Secret: $DB_PASSWORD" \
    -H "Content-Type: application/json" \
    -d '{"type":"export_metadata","version":2,"args":{}}' > "$SCRIPT_DIR/hasura-metadata.json"
echo "  -> db/hasura-metadata.json"

echo "Done!"
