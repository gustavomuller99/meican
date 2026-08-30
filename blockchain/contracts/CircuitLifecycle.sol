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

    mapping(bytes32 => ConnectionStatus) private connectionStatus;
    mapping(bytes32 => ConnectionAuth)   private connectionAuth;
    mapping(bytes32 => ConnectionCircuit) private connectionCircuit;

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
}
