#!/bin/bash

# K-Systems Documentation Deployment Script
# This script builds the documentation and copies it to the frontend public directory

set -e  # Exit on error

echo "================================================"
echo "  K-Systems Documentation Deployment"
echo "================================================"
echo ""

# Colors for output
GREEN='\033[0;32m'
BLUE='\033[0;34m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Check if we're in the docs directory
if [ ! -f "package.json" ] || [ ! -d ".vitepress" ]; then
    echo -e "${RED}Error: This script must be run from the docs/ directory${NC}"
    exit 1
fi

# Step 1: Install dependencies (if needed)
if [ ! -d "node_modules" ]; then
    echo -e "${BLUE}📦 Installing dependencies...${NC}"
    npm install
    echo ""
fi

# Step 2: Build documentation
echo -e "${BLUE}🔨 Building documentation...${NC}"
npm run docs:build
echo ""

# Step 3: Copy to frontend public directory
echo -e "${BLUE}📋 Copying to frontend...${NC}"

# Create docs directory if it doesn't exist
mkdir -p ../frontend/public/docs

# Remove old docs
rm -rf ../frontend/public/docs/*

# Copy new docs
cp -r .vitepress/dist/* ../frontend/public/docs/

echo -e "${GREEN}✓ Documentation deployed successfully!${NC}"
echo ""
echo "================================================"
echo "  Documentation is now available at:"
echo -e "  ${GREEN}https://your-domain.com/docs${NC}"
echo "================================================"
echo ""
echo "To test locally:"
echo "  1. Start frontend: cd frontend && npm run dev"
echo "  2. Visit: http://localhost:5173/docs"
echo ""
