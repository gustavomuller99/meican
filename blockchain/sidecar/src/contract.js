import { ethers } from "ethers";
import { ABI } from "./abi.js";

const {
  BLOCKCHAIN_RPC_URL,
  BLOCKCHAIN_CONTRACT_ADDRESS,
  BLOCKCHAIN_SIGNER_PRIVATE_KEY,
} = process.env;

if (!BLOCKCHAIN_RPC_URL || !BLOCKCHAIN_CONTRACT_ADDRESS || !BLOCKCHAIN_SIGNER_PRIVATE_KEY) {
  console.error("Missing required env vars: BLOCKCHAIN_RPC_URL, BLOCKCHAIN_CONTRACT_ADDRESS, BLOCKCHAIN_SIGNER_PRIVATE_KEY");
  process.exit(1);
}

const provider = new ethers.JsonRpcProvider(BLOCKCHAIN_RPC_URL);
const wallet   = new ethers.Wallet(BLOCKCHAIN_SIGNER_PRIVATE_KEY, provider);
const signer   = new ethers.NonceManager(wallet);

export const contractOwner = new ethers.Contract(BLOCKCHAIN_CONTRACT_ADDRESS, ABI, signer);
export const contractView  = new ethers.Contract(BLOCKCHAIN_CONTRACT_ADDRESS, ABI, provider);

let txQueue = Promise.resolve();

export async function sendTx(fn) {
  const next = txQueue.then(async () => {
    const tx = await fn();
    const receipt = await tx.wait();
    return { txHash: receipt.hash, blockNumber: receipt.blockNumber };
  });
  txQueue = next.then(() => {}, () => {});
  return next;
}
