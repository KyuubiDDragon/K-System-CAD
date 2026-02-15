#!/bin/bash
# setup-wiki.sh - Push wiki pages to GitHub Wiki
#
# Usage:
#   ./wiki/setup-wiki.sh
#
# Prerequisites:
#   - GitHub wiki must be enabled on the repository
#     (Go to Settings → Features → Wikis → Enable)
#   - You need push access to the repository
#
# This script clones the wiki repository, copies all .md files
# from the wiki/ directory, commits and pushes them.

set -e

REPO_URL="https://github.com/KyuubiDDragon/K-Systems.wiki.git"
WIKI_DIR="$(cd "$(dirname "$0")" && pwd)"
TEMP_DIR=$(mktemp -d)

echo "=== K-Systems Wiki Setup ==="
echo ""
echo "Wiki source:  $WIKI_DIR"
echo "Temp dir:     $TEMP_DIR"
echo ""

# Step 1: Clone wiki repo
echo "[1/4] Cloning wiki repository..."
if ! git clone "$REPO_URL" "$TEMP_DIR/wiki" 2>/dev/null; then
    echo ""
    echo "Could not clone the wiki repo. This usually means:"
    echo "  1. The wiki is not yet enabled on GitHub"
    echo "     → Go to: https://github.com/KyuubiDDragon/K-Systems/settings"
    echo "     → Features → Wikis → Enable"
    echo "  2. Create the first page manually on GitHub"
    echo "     → Go to: https://github.com/KyuubiDDragon/K-Systems/wiki"
    echo "     → Click 'Create the first page' → Save"
    echo "  3. Then run this script again"
    echo ""
    rm -rf "$TEMP_DIR"
    exit 1
fi

# Step 2: Copy wiki pages
echo "[2/4] Copying wiki pages..."
cp "$WIKI_DIR"/*.md "$TEMP_DIR/wiki/"

# Count files
FILE_COUNT=$(ls -1 "$TEMP_DIR/wiki/"*.md 2>/dev/null | wc -l)
echo "       Copied $FILE_COUNT pages"

# Step 3: Commit
echo "[3/4] Committing changes..."
cd "$TEMP_DIR/wiki"
git add -A
if git diff --cached --quiet; then
    echo "       No changes to commit (wiki is already up to date)"
    rm -rf "$TEMP_DIR"
    exit 0
fi
git commit -m "Update wiki pages from repository"

# Step 4: Push
echo "[4/4] Pushing to GitHub..."
git push origin master

echo ""
echo "=== Done! ==="
echo "Wiki is live at: https://github.com/KyuubiDDragon/K-Systems/wiki"
echo ""

# Cleanup
rm -rf "$TEMP_DIR"
