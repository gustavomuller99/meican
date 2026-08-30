// SPDX-License-Identifier: UNLICENSED
pragma solidity ^0.8.28;

contract CircuitLifecycle {

    event ConnectionStatusEvent(
        string indexed externalId,
        string userName,
        string reservationName,
        string bandwidth,
        string status,
        string resourcesStatus,
        string dataplaneStatus,
        string authStatus,
        string start,
        string finish
    );

    event ConnectionAuthEvent(
        string indexed externalId,
        string domain,
        string status
    );

    struct ConnectionStatusParams {
        string externalId;
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

    function logConnectionAuthEvent(
        string calldata externalId,
        string calldata domain,
        string calldata status
    ) external {
        emit ConnectionAuthEvent(externalId, domain, status);
    }

    function logConnectionStatusEvent(
        ConnectionStatusParams memory p
    ) external {
        emit ConnectionStatusEvent(p.externalId, p.userName, p.reservationName, p.bandwidth, p.status, p.resourcesStatus, p.dataplaneStatus, p.authStatus, p.start, p.finish);
    }
}