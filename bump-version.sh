#!/bin/bash

# WP Blog Agent Version Bump Script
# This script automates the process of bumping the plugin version

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Function to display usage
usage() {
    echo "Usage: $0 <new_version>"
    echo "Example: $0 1.0.2"
    echo ""
    echo "This script will:"
    echo "  1. Update version in wp-blog-agent.php (header and constant)"
    echo "  2. Update CHANGELOG.md with new version section"
    echo "  3. Create a git tag for the new version"
    echo ""
    exit 1
}

# Check if version argument is provided
if [ -z "$1" ]; then
    echo -e "${RED}Error: Version number required${NC}"
    usage
fi

NEW_VERSION=$1

# Validate version format (semantic versioning)
if ! [[ $NEW_VERSION =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
    echo -e "${RED}Error: Invalid version format. Use semantic versioning (e.g., 1.0.2)${NC}"
    exit 1
fi

# Get current directory
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
cd "$SCRIPT_DIR"

# Check if we're in a git repository
if ! git rev-parse --git-dir > /dev/null 2>&1; then
    echo -e "${RED}Error: Not in a git repository${NC}"
    exit 1
fi

# Check if working directory is clean
if ! git diff-index --quiet HEAD --; then
    echo -e "${YELLOW}Warning: Working directory has uncommitted changes${NC}"
    read -p "Do you want to continue? (y/n) " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        exit 1
    fi
fi

# Get current version from wp-blog-agent.php
CURRENT_VERSION=$(grep "define('WP_BLOG_AGENT_VERSION'" wp-blog-agent.php | sed -E "s/.*'([0-9]+\.[0-9]+\.[0-9]+)'.*/\1/")

echo -e "${GREEN}Current version: ${CURRENT_VERSION}${NC}"
echo -e "${GREEN}New version: ${NEW_VERSION}${NC}"
echo ""

# Confirm before proceeding
read -p "Proceed with version bump? (y/n) " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "Cancelled"
    exit 1
fi

# Update version in wp-blog-agent.php header comment
echo "Updating version in wp-blog-agent.php header..."
sed -i.bak "s/\* Version: [0-9][0-9]*\.[0-9][0-9]*\.[0-9][0-9]*/* Version: ${NEW_VERSION}/" wp-blog-agent.php

# Update version in wp-blog-agent.php constant
echo "Updating WP_BLOG_AGENT_VERSION constant..."
sed -i.bak "s/define('WP_BLOG_AGENT_VERSION', '[0-9][0-9]*\.[0-9][0-9]*\.[0-9][0-9]*')/define('WP_BLOG_AGENT_VERSION', '${NEW_VERSION}')/" wp-blog-agent.php

# Remove backup file
rm -f wp-blog-agent.php.bak

# Update CHANGELOG.md
echo "Updating CHANGELOG.md..."
TODAY=$(date +%Y-%m-%d)

# Create new version section in CHANGELOG.md
if grep -q "## \[Unreleased\]" CHANGELOG.md; then
    # Insert new version section after [Unreleased]
    # Using a more portable sed approach
    awk -v version="${NEW_VERSION}" -v date="${TODAY}" '
        /## \[Unreleased\]/ {
            print
            print ""
            print "## [" version "] - " date
            print ""
            print "### Added"
            print ""
            print "### Changed"
            print ""
            print "### Fixed"
            print ""
            next
        }
        { print }
    ' CHANGELOG.md > CHANGELOG.md.tmp && mv CHANGELOG.md.tmp CHANGELOG.md
else
    echo -e "${YELLOW}Warning: [Unreleased] section not found in CHANGELOG.md${NC}"
    echo -e "${YELLOW}Please update CHANGELOG.md manually${NC}"
fi

echo ""
echo -e "${GREEN}Version updated successfully!${NC}"
echo ""
echo "Next steps:"
echo "  1. Review the changes:"
echo "     git diff wp-blog-agent.php CHANGELOG.md"
echo ""
echo "  2. Update CHANGELOG.md with actual changes for this version"
echo ""
echo "  3. Commit the changes:"
echo "     git add wp-blog-agent.php CHANGELOG.md"
echo "     git commit -m \"Bump version to ${NEW_VERSION}\""
echo ""
echo "  4. Create and push the tag:"
echo "     git tag -a v${NEW_VERSION} -m \"Version ${NEW_VERSION}\""
echo "     git push origin v${NEW_VERSION}"
echo ""
echo "  5. Push the changes:"
echo "     git push"
echo ""
