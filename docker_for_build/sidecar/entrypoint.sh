#!/bin/sh
set -e

# BLOCKCHAIN_CONTRACT_ADDRESS arrives late via the shared volume written by the hardhat container
if [ -f /shared/blockchain.env ]; then
  export $(cat /shared/blockchain.env | xargs)
fi

exec node src/index.js
