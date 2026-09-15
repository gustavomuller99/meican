import express from "express";
import { ethers } from "ethers";
import { contractOwner, contractView, sendTx } from "./contract.js";

const app = express();
app.use(express.json());

function wrap(fn) {
  return async (req, res) => {
    try {
      await fn(req, res);
    } catch (err) {
      const message = err?.shortMessage ?? err?.message ?? String(err);
      res.status(500).json({ error: message });
    }
  };
}

// ── health ────────────────────────────────────────────────────────────────────

app.get("/health", (_req, res) => res.json({ status: "ok" }));

// ── circuit observability (on-chain) ─────────────────────────────────────────

app.post("/circuit/status", wrap(async (req, res) => {
  const { externalId, userName, reservationName, bandwidth, status,
          resourcesStatus, dataplaneStatus, authStatus, start, finish } = req.body;
  res.json(await sendTx(() => contractOwner.setConnectionStatus(externalId, {
    userName, reservationName, bandwidth, status,
    resourcesStatus, dataplaneStatus, authStatus, start, finish,
  })));
}));

app.post("/circuit/auth", wrap(async (req, res) => {
  const { externalId, domain, status } = req.body;
  res.json(await sendTx(() => contractOwner.setConnectionAuth(externalId, domain, status)));
}));

app.post("/circuit/event", wrap(async (req, res) => {
  const { externalId, eventType, status } = req.body;
  res.json(await sendTx(() => contractOwner.setConnectionCircuit(externalId, eventType, status)));
}));

app.get("/circuit/state/:externalId", wrap(async (req, res) => {
  const [cs, ca, cc] = await contractView.getCircuitState(req.params.externalId);
  res.json({
    connectionStatus: {
      userName: cs.userName, reservationName: cs.reservationName,
      bandwidth: cs.bandwidth, status: cs.status,
      resourcesStatus: cs.resourcesStatus, dataplaneStatus: cs.dataplaneStatus,
      authStatus: cs.authStatus, start: cs.start, finish: cs.finish,
    },
    connectionAuth:    { domain: ca.domain, status: ca.status },
    connectionCircuit: { eventType: cc.eventType, status: cc.status },
  });
}));

// ── circuit observability (IPFS CID pointers) ────────────────────────────────

app.post("/circuit/status-ipfs", wrap(async (req, res) => {
  const { externalId, cid } = req.body;
  res.json(await sendTx(() => contractOwner.setConnectionStatusIPFS(externalId, cid)));
}));

app.post("/circuit/auth-ipfs", wrap(async (req, res) => {
  const { externalId, cid } = req.body;
  res.json(await sendTx(() => contractOwner.setConnectionAuthIPFS(externalId, cid)));
}));

app.post("/circuit/event-ipfs", wrap(async (req, res) => {
  const { externalId, cid } = req.body;
  res.json(await sendTx(() => contractOwner.setConnectionCircuitIPFS(externalId, cid)));
}));

app.get("/circuit/state-ipfs/:externalId", wrap(async (req, res) => {
  const [statusCid, authCid, circuitCid] = await contractView.getCircuitStateIPFS(req.params.externalId);
  res.json({ statusCid, authCid, circuitCid });
}));

// ── workflow authorization ────────────────────────────────────────────────────

app.post("/workflow/request", wrap(async (req, res) => {
  const { externalId, requiredApprovers } = req.body;
  res.json(await sendTx(() => contractOwner.requestAuthorization(externalId, requiredApprovers)));
}));

// submitAuthorization is sent from the approver's own wallet, not the owner.
app.post("/workflow/submit", wrap(async (req, res) => {
  const { externalId, approve, signerPrivateKey } = req.body;
  console.log("[workflow/submit] key type:", typeof signerPrivateKey, "key length:", signerPrivateKey?.length, "key:", signerPrivateKey);
  const approverWallet = new ethers.Wallet(signerPrivateKey, contractOwner.runner.provider);
  const receipt = await contractOwner.connect(approverWallet).submitAuthorization(externalId, approve).then((tx) => tx.wait());
  res.json({ txHash: receipt.hash, blockNumber: receipt.blockNumber });
}));

app.get("/workflow/state/:externalId", wrap(async (req, res) => {
  const [requiredApprovers, approver, statusInt] =
    await contractView.getWorkflowAuthorization(req.params.externalId);
  const statusMap = { 0: "Pending", 1: "Approved", 2: "Rejected" };
  res.json({ requiredApprovers, approver, status: statusMap[Number(statusInt)] ?? "Unknown" });
}));

// ── start ─────────────────────────────────────────────────────────────────────

const PORT = process.env.PORT ?? 3000;
app.listen(PORT, () => console.log(`Blockchain sidecar listening on :${PORT}`));
