#!/bin/bash
# Script to upgrade Node.js to version 22

echo "Setting up NodeSource repository for Node.js 22..."
curl -fsSL https://deb.nodesource.com/setup_22.x | sudo -E bash -

echo "Installing Node.js 22..."
sudo apt-get install -y nodejs

echo "Checking installed version..."
node --version
npm --version

echo "Node.js upgrade complete!"
