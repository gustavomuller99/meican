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

    mapping(bytes32 => ConnectionStatus) private connectionStatus;
    mapping(bytes32 => ConnectionAuth) private connectionAuth;
    mapping(bytes32 => ConnectionCircuit) private connectionCircuit;
    mapping(bytes32 => WorkflowAuthorization) private workflowAuth;

    function setConnectionStatus(
        string calldata externalId,
        ConnectionStatus calldata data
    ) external {
        connectionStatus[keccak256(bytes(externalId))] = data;
    }

    function setConnectionAuth(
        string calldata externalId,
        string calldata domain,
        string calldata status
    ) external {
        bytes32 key = keccak256(bytes(externalId));
        connectionAuth[key] = ConnectionAuth(domain, status);
    }

    function setConnectionCircuit(
        string calldata externalId,
        string calldata eventType,
        string calldata status
    ) external {
        bytes32 key = keccak256(bytes(externalId));
        connectionCircuit[key] = ConnectionCircuit(eventType, status);
    }

    function requestAuthorization(
        string calldata externalId,
        address[] calldata requiredApprovers
    ) external {
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

    function getCircuitState(string calldata externalId)
        external view
        returns (
            ConnectionStatus memory,
            ConnectionAuth memory,
            ConnectionCircuit memory
        )
    {
        bytes32 key = keccak256(bytes(externalId));
        return (connectionStatus[key], connectionAuth[key], connectionCircuit[key]);
    }

    function getWorkflowAuthorization(string calldata externalId)
        external view
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
