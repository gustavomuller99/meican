// SPDX-License-Identifier: UNLICENSED
pragma solidity ^0.8.28;

contract CircuitLifecycle {

    struct ConnectionStatus {
        string userName;
        string reservationName;
        string bandwidth;
        string status;
        string resourcesStatus;
        string dataplaneStatus;
        string authStatus;
        string start;
        string finish;
    }

    struct ConnectionAuth {
        string domain;
        string status;
    }

    struct ConnectionCircuit {
        string eventType;
        string status;
    }

    struct WorkflowAuthorization {
        address[] requiredApprovers;
        address approver;
        WorkflowAuthorizationStatus status;
    }

    enum WorkflowAuthorizationStatus { Pending, Approved, Rejected }

    address owner;

    constructor() {
        owner = msg.sender;
    }

    modifier onlyOwner {
        require(msg.sender == owner, "Not the contract owner");
        _;
    }

    /* Circuit observability */
    mapping(bytes32 => ConnectionStatus) private connectionStatus;
    mapping(bytes32 => ConnectionAuth) private connectionAuth;
    mapping(bytes32 => ConnectionCircuit) private connectionCircuit;

    function setConnectionStatus(string calldata externalId, ConnectionStatus calldata data) external onlyOwner {
        connectionStatus[keccak256(bytes(externalId))] = data;
    }

    function setConnectionAuth(string calldata externalId, string calldata domain, string calldata status) external onlyOwner {
        bytes32 key = keccak256(bytes(externalId));
        connectionAuth[key] = ConnectionAuth(domain, status);
    }

    function setConnectionCircuit(string calldata externalId, string calldata eventType, string calldata status) external onlyOwner {
        bytes32 key = keccak256(bytes(externalId));
        connectionCircuit[key] = ConnectionCircuit(eventType, status);
    }

    function getCircuitState(string calldata externalId) external view onlyOwner 
        returns (
            ConnectionStatus memory,
            ConnectionAuth memory,
            ConnectionCircuit memory
        )
    {
        bytes32 key = keccak256(bytes(externalId));
        return (connectionStatus[key], connectionAuth[key], connectionCircuit[key]);
    }

    /* Circuit observability IPFS */
    mapping(bytes32 => string) private connectionStatusIPFS;
    mapping(bytes32 => string) private connectionAuthIPFS;
    mapping(bytes32 => string) private connectionCircuitIPFS;

    function setConnectionStatusIPFS(string calldata externalId, string calldata cid) external onlyOwner {
        connectionStatusIPFS[keccak256(bytes(externalId))] = cid;
    }

    function setConnectionAuthIPFS(string calldata externalId, string calldata cid) external onlyOwner {
        bytes32 key = keccak256(bytes(externalId));
        connectionAuthIPFS[key] = cid;
    }

    function setConnectionCircuitIPFS(string calldata externalId, string calldata cid) external onlyOwner {
        bytes32 key = keccak256(bytes(externalId));
        connectionCircuitIPFS[key] = cid;
    }

    function getCircuitStateIPFS(string calldata externalId) external view onlyOwner
        returns (
            string memory,
            string memory,
            string memory
        )
    {
        bytes32 key = keccak256(bytes(externalId));
        return (connectionStatusIPFS[key], connectionAuthIPFS[key], connectionCircuitIPFS[key]);
    }

    /* Workflow authorization */
    mapping(bytes32 => WorkflowAuthorization) private workflowAuth;

    function requestAuthorization(string calldata externalId, address[] calldata requiredApprovers) external onlyOwner {
        require(requiredApprovers.length > 0, "At least one approver required");
        bytes32 key = keccak256(bytes(externalId));
        workflowAuth[key] = WorkflowAuthorization(requiredApprovers, address(0), WorkflowAuthorizationStatus.Pending);
    }

    function submitAuthorization(string calldata externalId, bool approved) external {
        bytes32 key = keccak256(bytes(externalId));
        WorkflowAuthorization storage auth = workflowAuth[key];

        require(auth.status == WorkflowAuthorizationStatus.Pending, "No pending authorization");

        bool isAllowed = false;
        for (uint i = 0; i < auth.requiredApprovers.length; i++) {
            if (auth.requiredApprovers[i] == msg.sender) {
                isAllowed = true;
                break;
            }
        }
        require(isAllowed, "Not an authorized approver");

        auth.approver = msg.sender;
        auth.status = approved
            ? WorkflowAuthorizationStatus.Approved
            : WorkflowAuthorizationStatus.Rejected;
    }

    function getWorkflowAuthorization(string calldata externalId) external view onlyOwner 
        returns (
            address[] memory requiredApprovers,
            address approver,
            WorkflowAuthorizationStatus status
        )
    {
        bytes32 key = keccak256(bytes(externalId));
        WorkflowAuthorization storage auth = workflowAuth[key];
        return (auth.requiredApprovers, auth.approver, auth.status);
    }
}
