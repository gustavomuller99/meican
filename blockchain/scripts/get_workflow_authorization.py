"""
Usage:
  python get_workflow_authorization.py <rpc_url> <contract_address> <external_id>
"""
import sys
from web3 import Web3

STATUSES = {0: "Pending", 1: "Approved", 2: "Rejected"}

def main():
    if len(sys.argv) != 4:
        print(__doc__)
        sys.exit(1)

    rpc_url, contract_address, external_id = sys.argv[1:]

    w3 = Web3(Web3.HTTPProvider(rpc_url))

    abi = [
        {
            "name": "getWorkflowAuthorization",
            "type": "function",
            "inputs": [{"name": "externalId", "type": "string"}],
            "outputs": [
                {"name": "requiredApprovers", "type": "address[]"},
                {"name": "approver",          "type": "address"},
                {"name": "status",            "type": "uint8"},
            ],
            "stateMutability": "view",
        }
    ]

    contract = w3.eth.contract(address=Web3.to_checksum_address(contract_address), abi=abi)
    required_approvers, approver, status = contract.functions.getWorkflowAuthorization(external_id).call()

    print("WorkflowAuthorization:")
    print(f"  requiredApprovers: {required_approvers}")
    print(f"  approver:          {approver}")
    print(f"  status:            {STATUSES.get(status, status)}")

if __name__ == "__main__":
    main()
