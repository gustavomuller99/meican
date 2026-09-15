export const ABI = [
  // Circuit observability
  "function setConnectionStatus(string externalId, (string userName, string reservationName, string bandwidth, string status, string resourcesStatus, string dataplaneStatus, string authStatus, string start, string finish) data)",
  "function setConnectionAuth(string externalId, string domain, string status)",
  "function setConnectionCircuit(string externalId, string eventType, string status)",
  "function getCircuitState(string externalId) view returns ((string userName, string reservationName, string bandwidth, string status, string resourcesStatus, string dataplaneStatus, string authStatus, string start, string finish), (string domain, string status), (string eventType, string status))",

  // Circuit observability
  "function setConnectionStatusIPFS(string externalId, string cid)",
  "function setConnectionAuthIPFS(string externalId, string cid)",
  "function setConnectionCircuitIPFS(string externalId, string cid)",
  "function getCircuitStateIPFS(string externalId) view returns (string, string, string)",

  // Workflow authorization
  "function requestAuthorization(string externalId, address[] requiredApprovers)",
  "function submitAuthorization(string externalId, bool approved)",
  "function getWorkflowAuthorization(string externalId) view returns (address[] requiredApprovers, address approver, uint8 status)",
];
