// SPDX-License-Identifier: UNLICENSED
pragma solidity ^0.8.28;

contract CircuitLifecycle {

    event ConnectionAuthEvent(
        string indexed externalId,
        string domain,
        string status
    );

    function logConnectionAuthEvent(
        string calldata externalId,
        string calldata domain,
        string calldata status
    ) external {
        emit ConnectionAuthEvent(externalId, domain, status);
    }
}