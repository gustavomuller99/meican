#!/bin/sh
set -e

echo "Compiling contracts..."
npx hardhat compile

echo "Starting hardhat node..."
npx hardhat node --hostname 0.0.0.0 &
NODE_PID=$!

echo "Waiting for node to be ready..."
until curl -sf -X POST --data '{"jsonrpc":"2.0","method":"net_version","id":1}' \
      -H 'Content-Type: application/json' \
      http://localhost:8545 > /dev/null 2>&1; do
  sleep 1
done

echo "Deploying contracts..."
DEPLOY_OUTPUT=$(npx hardhat ignition deploy ignition/modules/CircuitLifecycle.ts --network localhost --reset 2>&1)
echo "$DEPLOY_OUTPUT"

CONTRACT_ADDRESS=$(echo "$DEPLOY_OUTPUT" | grep -oE '0x[0-9a-fA-F]{40}' | tail -1)
echo "BLOCKCHAIN_CONTRACT_ADDRESS=$CONTRACT_ADDRESS" > /shared/blockchain.env
echo "Contract deployed at $CONTRACT_ADDRESS"

wait $NODE_PID
